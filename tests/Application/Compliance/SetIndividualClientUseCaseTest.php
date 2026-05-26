<?php

namespace App\Tests\Application\Compliance;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Application\Compliance\UseCase\SetIndividualClientUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

class SetIndividualClientUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface $repositoryMock;
    private SetIndividualClientUseCase $useCase;

    protected function setUp(): void
    {
        // On initialise le mock du repository avant chaque test
        $this->repositoryMock = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->useCase = new SetIndividualClientUseCase($this->repositoryMock);
    }

    public function test_it_updates_and_saves_individual_client_successfully(): void
    {
        // 1. Arrange (Préparation des données)
        $request = new SetIndividualClientRequest();
        $request->reference = 'REF-123';
        $request->firstName = 'Jean';
        $request->lastName = 'Dupont';
        $request->email = 'jean@test.com';

        // On mock un IndividualFolder pour vérifier qu'on appelle bien ses méthodes
        $folderMock = $this->createMock(IndividualFolder::class);

        // On s'attend à ce que le repo cherche la référence et retourne notre dossier mocké
        $this->repositoryMock->expects($this->once())
            ->method('findByReference')
            ->with('REF-123')
            ->willReturn($folderMock);

        // On s'attend à ce que les infos du client soient mises à jour
        $folderMock->expects($this->once())
            ->method('setClientInfo')
            ->with('Jean', 'Dupont', 'jean@test.com');

        // On s'attend à ce que le dossier soit sauvegardé
        $this->repositoryMock->expects($this->once())
            ->method('save')
            ->with($folderMock);

        // 2. Act (Exécution)
        ($this->useCase)($request);

        // 3. Assert (Les vérifications sont gérées implicitement par les "expects" ci-dessus)
    }

    public function test_it_throws_exception_if_folder_is_not_an_individual_folder(): void
    {
        // 1. Arrange
        $request = new SetIndividualClientRequest();
        $request->reference = 'REF-456';

        // On simule la situation où le repository retourne un dossier de type Business
        $businessFolder = $this->createMock(BusinessFolder::class);

        $this->repositoryMock->method('findByReference')
            ->willReturn($businessFolder);

        // 2. Assert (On prévient PHPUnit qu'on s'attend à un crash contrôlé)
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Ce dossier n\'est pas un dossier physique (IndividualFolder).');

        // 3. Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_it_throws_exception_if_folder_is_not_found(): void
    {
        $request = new SetIndividualClientRequest();
        $request->reference = 'REF-789';

        // On dit au Mock de lancer une exception (comme le ferait ton vrai Repo)
        $this->repositoryMock->method('findByReference')
            ->willThrowException(new \Exception('Dossier introuvable dans le repo'));

        // On s'attend à ce que le UseCase laisse remonter l'erreur
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dossier introuvable dans le repo');

        ($this->useCase)($request);
    }
}
