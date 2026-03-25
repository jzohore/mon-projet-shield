<?php

namespace App\Domain\Kyc\Validator;

use App\Domain\Kyc\Enum\KycFolderStatus;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueKycAwaitingClientValidator extends ConstraintValidator
{
    public function __construct(
        private readonly KycFolderRepositoryInterface $kycFolderRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueKycAwaitingClient) {
            throw new UnexpectedTypeException($constraint, UniqueKycAwaitingClient::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $existingWorkspace = $this->kycFolderRepository->findOneByEmailAndStatus($value, KycFolderStatus::AWAITING_CLIENT);
        if ($existingWorkspace !== null) {
            if ($constraint->ignoreSlugId !== null && $existingWorkspace->slugId === $constraint->ignoreSlugId) {
                return;
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }

    }
}
