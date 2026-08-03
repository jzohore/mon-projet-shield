<?php

declare(strict_types=1);

namespace App\Infrastructure\Employees\Twig;

use App\Application\Workspace\DTO\Response\WorkspaceMemberDetailsResponse;
use App\Application\Workspace\UseCase\WorkspaceMember\GetWorkspaceMemberDetailsUseCase;
use App\Application\Workspace\UseCase\WorkspaceMember\RevokeWorkspaceMemberAccessUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(
    name: 'RevokeWorkspaceMemberAccessComponent',
    template: 'components/Workspace/RevokeWorkspaceMemberAccessComponent.html.twig',
)]
class RevokeWorkspaceMemberAccessComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public string $targetUserSlugId;

    public function __construct(
        private readonly RevokeWorkspaceMemberAccessUseCase $revokeUseCase,
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly LoggerInterface $logger,
        private readonly GetWorkspaceMemberDetailsUseCase $getMemberDetailsUseCase,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[ExposeInTemplate]
    public function getMember(): WorkspaceMemberDetailsResponse
    {
        $user = $this->currentUserProvider->getUser();

        return ($this->getMemberDetailsUseCase)($this->targetUserSlugId, $user);
    }

    #[LiveAction]
    public function revokeAccess(): ?RedirectResponse
    {
        $this->validate();
        try {
            ($this->revokeUseCase)($this->targetUserSlugId);

            $this->addLiveFlash('success', 'L\'accès du collaborateur a été révoqué avec succès.');

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'success',
                message: 'L\'accès du collaborateur a été révoqué avec succès.'
            );

            return new RedirectResponse($this->urlGenerator->generate('app_employees_list'));
        } catch (AbstractDomainException|\DomainException $e) {
            $this->logger->warning('Échec de la révocation du collaborateur (Erreur Métier)', [
                'target_slug' => $this->targetUserSlugId,
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la révocation d\'un collaborateur', [
                'target_slug' => $this->targetUserSlugId,
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: $e->getMessage(),
            );
        }

        return null;
    }
}
