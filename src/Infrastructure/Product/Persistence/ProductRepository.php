<?php

namespace App\Infrastructure\Product\Persistence;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final readonly class ProductRepository implements ProductRepositoryInterface
{
    /** @var EntityRepository<Product> */
    private EntityRepository $repository;
    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Product::class);
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function findByStripeId(string $stripePriceId): ?Product
    {
        return $this->repository->findOneBy(['stripePriceId' => $stripePriceId]);
    }

    public function findOneBySlug(string $slug): ?Product
    {
        return $this->repository->findOneBy(['slugId' => $slug]);
    }

    public function findAllSortedByCredits(): array
    {
        return $this->repository->findBy([], ['credits' => 'ASC']);
    }
}
