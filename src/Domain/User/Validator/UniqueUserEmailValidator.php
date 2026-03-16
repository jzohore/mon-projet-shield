<?php

namespace App\Domain\User\Validator;

use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueUserEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserEmail) {
            throw new UnexpectedTypeException($constraint, UniqueUserEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // On cherche s'il existe déjà un cabinet avec ce nom exact
        $existingUser = $this->userRepository->findOneBy(['email' => $value]);

        // S'il existe...
        if ($existingUser !== null) {

            // 💡 LA NOUVELLE LOGIQUE D'EXCLUSION
            // Si on a fourni un ID à ignorer, et que cet ID correspond au cabinet trouvé,
            // c'est qu'on est juste en train de sauvegarder son propre nom. Tout va bien !
            if ($constraint->ignoreSlugId !== null && $existingUser->slugId === $constraint->ignoreSlugId) {
                return;
            }

            // Sinon, c'est que le nom est vraiment pris par quelqu'un d'autre
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }
    }
}
