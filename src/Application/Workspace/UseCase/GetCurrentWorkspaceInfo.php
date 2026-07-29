<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Response\WorkspaceInfoResponse;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final class GetCurrentWorkspaceInfo
{
    /**
     * @var array<string, WorkspaceInfoResponse>
     */
    private array $cache = [];

    public function __construct(
        // On garde readonly ici pour protéger le service
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    public function __invoke(Uuid $userSlugId): WorkspaceInfoResponse
    {
        $userIdString = (string) $userSlugId;

        // 1. Si on a déjà calculé la réponse pour cet utilisateur, on la retourne direct !
        if (array_key_exists($userIdString, $this->cache)) {
            return $this->cache[$userIdString];
        }

        // 2. Sinon, on interroge la base de données
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($userSlugId);
        Assert::notNull($workspaceMember, "L'utilisateur n'est membre d'aucun espace de travail.");

        $workspace = $workspaceMember->workspace;
        Assert::notNull($workspace, "L'espace de travail est introuvable.");
        Assert::notNull($workspace->slugId, "L'espace de travail n'a pas de slugId.");
        Assert::notNull($workspace->name, "L'espace de travail n'a pas de nom.");

        $response = WorkspaceInfoResponse::fromEntity($workspace);

        // 3. On sauvegarde la réponse dans le cache avant de la retourner
        $this->cache[$userIdString] = $response;

        return $response;
    }
}
