<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Validator;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\Kyc\Enum\KycFolderStatus;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Webmozart\Assert\Assert;

final class UniqueKycAwaitingClientValidator extends ConstraintValidator
{
    public function __construct(
        private readonly KycFolderRepositoryInterface $kycFolderRepository,
        private readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        private readonly UserRepositoryInterface $userRepository,
        private readonly Security $security,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueKycAwaitingClient) {
            throw new UnexpectedTypeException($constraint, UniqueKycAwaitingClient::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $currentUserEmail = $this->security->getUser();
        Assert::notNull($currentUserEmail);
        $user = $this->userRepository->findByEmail($currentUserEmail->getUserIdentifier());

        Assert::isInstanceOf($user, User::class);
        $userId = $user->id;
        Assert::notNull($userId, "L'utilisateur doit avoir un ID pour récupérer le workspace.");
        $workspace = ($this->getCurrentWorkspaceInfo)($userId);
        Assert::notNull($workspace->slugId);
        $existingFolder = $this->kycFolderRepository->findFirstByEmailAndStatuses(
            $value,
            KycFolderStatus::getActiveStatuses(),
            $workspace->slugId
        );
        if ($existingFolder instanceof \App\Domain\Kyc\Entity\KycFolder) {
            if (null !== $constraint->ignoreSlugId && $existingFolder->slugId === $constraint->ignoreSlugId) {
                return;
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
