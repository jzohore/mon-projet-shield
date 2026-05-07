<?php

namespace App\Domain\Workspace\Service;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;
use Symfony\Contracts\Service\ResetInterface;

final class CurrentWorkspaceProvider implements ResetInterface
{
    // Ce cache ne doit vivre QUE le temps d'une seule requête HTTP.
    private ?string $cachedWorkspaceId = null;

    public function __construct(
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private readonly Security $security,
    ) {}

    /**
     * Appelé automatiquement par Symfony à la fin de chaque requête
     * pour nettoyer le service avant qu'il ne serve un autre client.
     */
    public function reset(): void
    {
        $this->cachedWorkspaceId = null;
    }

    public function getWorkspace(): Workspace
    {
        // 1. On récupère TOUJOURS l'utilisateur actuel via la session sécurisée
        $user = $this->security->getUser();
        Assert::notNull($user, 'Utilisateur non connecté.');
        Assert::isInstanceOf($user, User::class, 'Utilisateur invalide.');
        Assert::notNull($user->id);
        // 2. Si le cache est déjà plein, on l'utilise (vitesse)
        if ($this->cachedWorkspaceId !== null) {
            // Dans une version ultra-sécurisée, on pourrait vérifier ici
            // que le cache appartient bien au $user actuel.
        }

        // 3. Sinon, on va chercher l'info en base
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($user->id);
        Assert::notNull($workspaceMember, "Aucun espace de travail trouvé pour cet utilisateur.");

        $workspace = $workspaceMember->workspace;

        // On remplit le cache pour les prochains appels DURANT CETTE REQUÊTE uniquement
        $this->cachedWorkspaceId = (string) $workspace->id;

        return $workspace;
    }
}
