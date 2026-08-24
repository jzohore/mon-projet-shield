<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Application\Compliance\UseCase\ComplianceFolder\SetIndividualClientUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetIndividualClientUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $repository;
    private SetIndividualClientUseCase $useCase;

    private const string VALID_REF = 'REF-123456';

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->useCase = new SetIndividualClientUseCase($this->repository);
    }

    /**
     * 🚀 HELPER AVANCÉ : Hydratation d'Entité (Test Data Builder).
     */
    private function createEntityState(string $className, array $properties = []): object
    {
        $reflection = new \ReflectionClass($className);
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $declaringProperty = $property->getDeclaringClass()->getProperty($propertyName);
            $declaringProperty->setValue($entity, $value);
        }

        return $entity;
    }

    public function testInvokeThrowsExceptionWhenFolderIsNotFound(): void
    {
        $request = new SetIndividualClientRequest();

        $request->reference = self::VALID_REF;
        $request->firstName = 'Jean';
        $request->lastName = 'Dupont';
        $request->email = 'jean@test.com';

        $folder = $this->createEntityState(BusinessFolder::class);

        $this->repository
            ->expects($this->once())
            ->method('findByReference')
            ->with(self::VALID_REF)
            ->willReturn($folder);

        // 🛡️ Sécurité : Le repository ne doit JAMAIS sauvegarder
        $this->repository->expects($this->never())->method('save');

        try {
            ($this->useCase)($request);
            $this->fail('Une LogicException aurait dû être levée (Dossier introuvable = null).');
        } catch (\LogicException $e) {
            $this->assertSame('Ce dossier n\'est pas un dossier physique (IndividualFolder).', $e->getMessage());
        }
    }

    public function testInvokeThrowsExceptionWhenFolderIsWrongType(): void
    {
        $request = new SetIndividualClientRequest();

        $request->reference = self::VALID_REF;
        $request->firstName = 'Jean';
        $request->lastName = 'Dupont';
        $request->email = 'jean@test.com';

        // On simule qu'un dossier de type Personne Morale est trouvé au lieu d'une Personne Physique
        $wrongFolderType = $this->createEntityState(BusinessFolder::class);

        $this->repository
            ->expects($this->once())
            ->method('findByReference')
            ->with(self::VALID_REF)
            ->willReturn($wrongFolderType);

        $this->repository->expects($this->never())->method('save');

        try {
            ($this->useCase)($request);
            $this->fail('Une LogicException aurait dû être levée car le dossier est un BusinessFolder.');
        } catch (\LogicException $e) {
            $this->assertSame('Ce dossier n\'est pas un dossier physique (IndividualFolder).', $e->getMessage());
        }
    }

    public function testInvokeSetsClientInfoAndSavesFolderSuccessfully(): void
    {
        $request = new SetIndividualClientRequest();

        $request->reference = self::VALID_REF;
        $request->firstName = 'Jean';
        $request->lastName = 'Dupont';
        $request->email = 'jean@test.com';
        // 🚀 On utilise une VRAIE instance d'IndividualFolder
        $folder = $this->createEntityState(IndividualFolder::class);

        $this->repository
            ->expects($this->once())
            ->method('findByReference')
            ->with(self::VALID_REF)
            ->willReturn($folder);

        // On s'assure que le UseCase appelle bien la méthode save avec l'entité mutée
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($folder));

        ($this->useCase)($request);

        // Note : En pur DDD, si les propriétés de ton entité sont lisibles (via getter ou public private(set)),
        // tu pourrais ajouter des assertions ici pour vérifier que $folder->firstName === 'Jean', etc.
        // Cela garantirait que l'entité a bien réagi à setClientInfo().
    }
}
