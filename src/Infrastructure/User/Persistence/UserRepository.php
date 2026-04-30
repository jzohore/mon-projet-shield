<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Persistence;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Uid\Uuid;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class UserRepository implements UserRepositoryInterface
{
    /** @var EntityRepository<User> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(User::class);
    }

    public function save(User $user): void
    {
        $em = $this->entityManager;
        $em->persist($user);
        $em->flush();
    }

    public function getById(Uuid $id): User
    {
        $user =  $this->repository->find($id);

        if (null === $user) {
            throw UserNotFoundException::withId($id);
        }
        return $user;
    }

    public function findByEmail(?string $email): ?User
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    /**
     * @param string $email
     * @return User
     */
    public function getByEmail(string $email): User
    {
        $user =  $this->repository->findOneBy(['email' => $email]);

        if (null === $user) {
            throw UserNotFoundException::withEmail($email);
        }
        return $user;
    }

    /**
     * @param string|null $slug
     * @return User|null
     */
    public function findBySlug(?string $slug): ?User
    {
        return $this->repository->findOneBy(['slugId' => $slug]);
    }

    public function getBySlug(string $slug): User
    {
        $user =  $this->repository->findOneBy(['slugId' => $slug]);

        if (null === $user) {
            throw UserNotFoundException::withSlug($slug);
        }
        return $user;
    }

    public function delete(User $user): void
    {
        $em = $this->entityManager;
        $em->remove($user);
        $em->flush();
    }

    public function findByMagicLink(string $magicLink): ?User
    {
        return $this->repository->findOneBy(['magicLinkToken' => $magicLink]);
    }

    /**
     * @return array<int, User>
     */
    public function findUsersNeedingReminder(\DateTimeInterface $twoHoursAgo): array
    {
        return $this->repository->createQueryBuilder('u')
            ->where('u.onboardingStatus IN (:onboarding_status)')
            ->setParameter('onboarding_status', [
                OnboardingStatus::PENDING,
                OnboardingStatus::WORKSPACE_SETUP,
            ])
            ->andWhere('u.createdAt <= :twoHoursAgo')
            ->setParameter('twoHoursAgo', $twoHoursAgo)
            ->andWhere('u.onboardingReminderSentAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Pagerfanta<User>
     */
    public function findMembersForList(Workspace $workspace, ?string $search = null, ?bool $queryEnabled = null): Pagerfanta
    {
        $qb = $this->repository->createQueryBuilder('u')
            ->andWhere('u.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('u.firstName', 'ASC'); // Tri alphabétique direct en SQL !

        // Si l'utilisateur tape un truc dans la barre de recherche
        if ($search) {
            $qb->andWhere('u.email LIKE :search OR u.firstName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($queryEnabled === true || $queryEnabled === false) {
            $qb = $qb
                ->andWhere('u.isActif = :is_actif')
                ->setParameter('is_actif', $queryEnabled);
        }

        return new Pagerfanta(new QueryAdapter($qb));
    }
}
