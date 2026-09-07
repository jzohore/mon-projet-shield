<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\DTO\Request\LoginUserRequest;
use App\Application\User\UseCase\Register\CreateUserUseCase;
use App\Application\User\UseCase\SendLoginUserUseCase;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Infrastructure\User\Form\CreateUserType;
use Psr\Log\LoggerInterface;
use Random\RandomException;

use function Symfony\Component\Clock\now;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'CreateUserFormComponent',
    template: 'components/User/CreateUserFormComponent.html.twig',
)]
final class CreateUserFormComponent
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * Délai minimum, en secondes, entre l'affichage du formulaire et sa
     * soumission. Un humain met plusieurs secondes à saisir 3 champs ;
     * un bot poste instantanément.
     */
    private const int MIN_FILL_SECONDS = 3;

    #[LiveProp]
    public bool $isSuccessful = false;

    #[LiveProp]
    public ?string $message = null;

    /**
     * Horodatage (timestamp Unix) du premier rendu du composant. Non
     * writable : protégé par l'empreinte LiveComponent, le client ne peut
     * pas le rejouer sans invalider la signature.
     */
    #[LiveProp]
    public int $renderedAt = 0;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CreateUserUseCase $createUser,
        private readonly LoggerInterface $logger,
        private readonly SendLoginUserUseCase $sendLoginUser,
        private readonly RateLimiterFactory $registrationLimiter,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function mount(): void
    {
        $this->renderedAt = now()->getTimestamp();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateUserType::class);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        $form = $this->getForm();

        if (!$form->isValid()) {
            return;
        }

        /** @var CreateUserRequest $userDTO */
        $userDTO = $form->getData();

        // 🍯 1. Honeypot : le champ leurre a été rempli → robot. On simule un
        // succès pour ne pas lui apprendre qu'il est repéré, et on ne fait rien.
        $honeypot = (string) $form->get('website')->getData();
        if ('' !== trim($honeypot)) {
            $this->logger->warning('Inscription rejetée : honeypot rempli.', [
                'ip' => $this->requestStack->getCurrentRequest()?->getClientIp(),
            ]);
            $this->markAsGenericSuccess();

            return;
        }

        // ⏱️ 2. Time-trap : soumission trop rapide pour être humaine.
        $elapsed = now()->getTimestamp() - $this->renderedAt;
        if ($this->renderedAt > 0 && $elapsed < self::MIN_FILL_SECONDS) {
            $this->logger->warning('Inscription rejetée : soumission trop rapide (time-trap).', [
                'elapsed_seconds' => $elapsed,
                'ip' => $this->requestStack->getCurrentRequest()?->getClientIp(),
            ]);
            $this->markAsGenericSuccess();

            return;
        }

        // 🚦 3. Rate limiting par IP : borne le bombing d'e-mails.
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        if (!$this->registrationLimiter->create($ip)->consume()->isAccepted()) {
            $this->logger->warning('Inscription rejetée : quota par IP dépassé.', ['ip' => $ip]);
            $this->message = 'Trop de tentatives depuis votre connexion. Merci de réessayer dans quelques minutes.';

            return;
        }

        try {
            ($this->createUser)($userDTO);
        } catch (UserAlreadyExistsException) {
            $this->logger->info('Tentative d\'inscription sur un compte existant (User Enumeration Defense).', [
                'email' => $userDTO->email,
            ]);
            $this->sendLoginUser(
                $userDTO->email,
            );
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'inscription', [
                'email' => $userDTO->email,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de l\'inscription', [
                'error' => $e->getMessage(),
            ]);
            $this->message = 'Une erreur technique est survenue, veuillez réessayer plus tard.';

            return;
        }

        $this->markAsGenericSuccess();
    }

    private function markAsGenericSuccess(): void
    {
        $this->isSuccessful = true;
        $this->message = 'Si cette adresse est valide, un lien vous a été envoyé.';
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function sendLoginUser(string $email): void
    {
        $request = new LoginUserRequest(
            email: $email,
        );

        ($this->sendLoginUser)($request);
    }
}
