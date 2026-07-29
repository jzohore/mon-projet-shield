<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

#[AsCommand(
    name: 'app:test-slack',
    description: 'Envoie un message de test sur les canaux Slack RegTech'
)]
class TestSlackCommand extends Command
{
    public function __construct(
        private readonly ChatterInterface $chatter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('⏳ Envoi du message sur Slack...');

        try {
            // Création du message
            $message = new ChatMessage('🚨 <!channel> *ALERTE CRITIQUE* : Test de notification push mobile !');
            // 🚨 Si tu as mis "compliance" dans ton yaml, utilise "compliance"
            // Sinon, remplace par "slack"
            $message->transport('workspace_failed_verify');

            // Envoi via le Notifier
            $this->chatter->send($message);

            $output->writeln('✅ Message envoyé avec succès sur le canal !');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('❌ Erreur lors de l\'envoi : ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
