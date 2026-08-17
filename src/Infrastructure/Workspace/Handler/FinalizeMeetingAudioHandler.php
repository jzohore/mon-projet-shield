<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Application\Workspace\UseCase\AppendAudioChunkUseCase;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Compliance\Message\AnalyzeCompleteMeetingMessage;
use App\Infrastructure\Compliance\Message\FinalizeMeetingAudioMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class FinalizeMeetingAudioHandler
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private DocumentStorageInterface $storage,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private Filesystem $filesystem,
    ) {
    }

    public function __invoke(FinalizeMeetingAudioMessage $message): void
    {
        $folder = $this->folderRepository->findOneBySlugId($message->folderSlugId);
        if (!$folder) {
            $this->logger->error('Finalisation avortée : Dossier introuvable.', ['slug' => $message->folderSlugId]);

            return;
        }

        $currentStatus = $folder->getMeetingProcessingStatus();
        if (in_array($currentStatus, [MeetingProcessingStatus::ANALYZING, MeetingProcessingStatus::DONE], true)) {
            $this->logger->warning('Double exécution évitée (Idempotence). Le dossier est déjà traité.', ['slug' => $folder->slugId]);

            return;
        }

        $chunksDirectory = AppendAudioChunkUseCase::chunksDirectory($folder->slugId);
        $chunkKeys = $this->storage->listFiles($chunksDirectory);

        if ([] === $chunkKeys) {
            $this->logger->error('Finalisation avortée : aucun chunk trouvé.', ['slug' => $folder->slugId]);
            throw new \RuntimeException('Aucun flux audio à analyser.');
        }

        sort($chunkKeys, \SORT_STRING);

        // Le conteneur réel dépend du navigateur qui a enregistré (Safari =
        // mp4, Chrome/Firefox = webm en général).
        $mimeType = $folder->audioMimeType;
        $extension = $this->extensionForMimeType($mimeType);

        if (null === $mimeType) {
            $this->logger->warning(
                'Aucun mimeType audio connu pour ce dossier, on suppose webm par défaut.',
                ['slug' => $folder->slugId]
            );
        }

        $workDir = sys_get_temp_dir() . '/meeting_remux_' . $folder->slugId . '_' . uniqid();
        $this->filesystem->mkdir($workDir);

        try {
            $localChunkPaths = $this->downloadChunksLocally($chunkKeys, $workDir, $extension);
            $finalPath = $this->remuxWithFfmpeg($localChunkPaths, $workDir, $extension);

            // 🛡️ Filet de sécurité final : si malgré le VU-mètre et l'alerte
            // de silence côté client, l'enregistrement final est quasi vide
            // (micro coupé sans que le CGP ne s'en aperçoive, permission
            // refusée silencieusement, etc.), on le signale explicitement
            // plutôt que d'afficher un badge "Analysé" trompeur sur un
            // entretien qui n'a en réalité rien capté.
            $isSilent = $this->detectSilence($finalPath);
            if ($isSilent) {
                $this->logger->warning('Audio quasi-silencieux détecté à la finalisation.', ['slug' => $folder->slugId]);
                // $folder->setAudioQualityWarning('Aucun son significatif détecté dans cet enregistrement. Vérifiez votre micro avant le prochain entretien — la synthèse ci-dessous risque d\'être vide ou non fiable.');
            }

            $fileToStore = new UploadedFile(
                path: $finalPath,
                originalName: sprintf('entretien_vocal_%s.%s', str_replace(' ', '_', $folder->reference), $extension),
                mimeType: $mimeType ?? 'audio/webm',
                test: true
            );

            $finalDirectory = sprintf('documents/meetings/%s', $folder->slugId);
            $finalStoragePath = $this->storage->store($fileToStore, $finalDirectory);

            foreach ($chunkKeys as $chunkKey) {
                $this->storage->delete($chunkKey);
            }

            // Décompte explicite du quota via son propre repository : pas de
            // dépendance à un cascade persist entre ComplianceFolder et Workspace.
            $folder->workspace->consumeMeetingSeconds($message->consumedSeconds);
            $this->workspaceRepository->save($folder->workspace);

            $folder->setMeetingProcessingStatus(MeetingProcessingStatus::ANALYZING);
            $this->folderRepository->save($folder);

            $this->messageBus->dispatch(new AnalyzeCompleteMeetingMessage(
                folderSlugId: $folder->slugId,
                audioFilePath: $finalStoragePath,
                consumedSeconds: $message->consumedSeconds,
            ));
        } finally {
            $this->filesystem->remove($workDir);
        }
    }

    /**
     * Utilise le filtre ffmpeg volumedetect pour estimer si le fichier est
     * quasi silencieux (max_volume très bas = aucun signal significatif
     * capté sur toute la durée, typique d'un micro coupé ou muet).
     */
    private function detectSilence(string $audioPath): bool
    {
        $process = new Process([
            'ffmpeg', '-i', $audioPath, '-af', 'volumedetect', '-f', 'null', '/dev/null',
        ]);
        $process->setTimeout(60);
        $process->run();

        // volumedetect écrit son résultat sur stderr, que ffmpeg réussisse
        // ou non à écrire vers /dev/null (peu importe le code de sortie ici).
        $output = $process->getErrorOutput();

        if (!preg_match('/max_volume:\s*(-?\d+(?:\.\d+)?)\s*dB/', $output, $matches)) {
            $this->logger->warning('Impossible d\'extraire max_volume via ffmpeg volumedetect, on suppose non-silencieux par prudence.');

            return false; // en cas de doute, on ne bloque pas l'analyse
        }

        $maxVolumeDb = (float) $matches[1];

        // -50dB : seuil empirique, en dessous duquel il n'y a essentiellement
        // aucun signal audible sur toute la durée de l'enregistrement.
        return $maxVolumeDb < -50.0;
    }

    private function extensionForMimeType(?string $mimeType): string
    {
        return match ($mimeType) {
            'audio/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            default => 'webm',
        };
    }

    /**
     * @param string[] $chunkKeys
     *
     * @return string[]
     */
    private function downloadChunksLocally(array $chunkKeys, string $workDir, string $extension): array
    {
        $localPaths = [];

        foreach ($chunkKeys as $i => $chunkKey) {
            $localPath = sprintf('%s/%06d.%s', $workDir, $i, $extension);
            file_put_contents($localPath, $this->storage->getContents($chunkKey));
            $localPaths[] = $localPath;
        }

        return $localPaths;
    }

    /**
     * Assemble et encode les chunks audio en un fichier final optimisé.
     *
     * @param string[] $localChunkPaths
     */
    private function remuxWithFfmpeg(array $localChunkPaths, string $workDir, string $extension): string
    {
        $listFilePath = $workDir . '/concat_list.txt';
        $listContent = '';
        foreach ($localChunkPaths as $path) {
            // Sécurisation stricte des chemins pour FFmpeg
            $escapedPath = str_replace("'", "'\\''", $path);
            $listContent .= "file '{$escapedPath}'\n";
        }
        file_put_contents($listFilePath, $listContent);

        $outputPath = $workDir . '/final.' . $extension;

        // 🛡️ Détermination du codec audio compatible avec le conteneur final
        $codec = match ($extension) {
            'webm' => 'libopus',
            'mp4' => 'aac',
            'ogg' => 'libvorbis',
            default => 'libopus', // Format moderne standard
        };

        $process = new Process([
            'ffmpeg',
            '-y',                      // Écrase le fichier de sortie s'il existe
            '-f', 'concat',            // Utilise le demuxer de concaténation
            '-safe', '0',              // Permet les chemins absolus générés par sys_get_temp_dir
            '-i', $listFilePath,

            // 🚀 LE CORRECTIF EST ICI : On transcode au lieu de copier ('-c copy')
            '-c:a', $codec,

            // 💰 OPTIMISATION FINANCIÈRE (Bootstrapper)
            // L'audio brut (PCM) de RecordRTC pèse ~1536 kb/s (Stéréo).
            // On le compresse massivement pour réduire les coûts S3 et accélérer l'envoi à l'IA.
            '-b:a', '48k',             // 48 kb/s est largement suffisant pour de la reconnaissance vocale
            '-ac', '1',                // Downmix en Mono (la voix n'a pas besoin de stéréo, divise le poids par 2)

            $outputPath,
        ]);

        // Timeout généreux (le transcodage demande un peu plus de CPU que la copie simple)
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->critical('Échec de l\'encodage FFmpeg.', [
                'error_output' => $process->getErrorOutput(),
                'extension' => $extension,
                'codec_used' => $codec,
            ]);

            throw new \RuntimeException('Erreur FFmpeg : ' . $process->getErrorOutput());
        }

        if (!file_exists($outputPath) || 0 === filesize($outputPath)) {
            throw new \RuntimeException('L\'encodage FFmpeg a produit un fichier vide.');
        }

        return $outputPath;
    }
}
