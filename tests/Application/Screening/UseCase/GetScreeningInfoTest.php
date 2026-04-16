<?php

declare(strict_types=1);

namespace App\Tests\Application\Screening\UseCase;

use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Application\Screening\UseCase\GetScreeningInfo;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\Industry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GetScreeningInfoTest extends TestCase
{
    #[Test]
    public function it_returns_screening_response_when_audit_exists(): void
    {
        $repository = $this->createMock(ScreeningAuditRepositoryInterface::class);
        $audit = $this->createScreeningAudit();

        $repository->expects(self::once())
            ->method('findOneBySlug')
            ->with($audit->slugId)
            ->willReturn($audit);

        $useCase = new GetScreeningInfo($repository);

        $response = $useCase($audit->slugId);

        self::assertInstanceOf(ScreeningResponse::class, $response);
        self::assertSame($audit->slugId, $response->slugId);
        self::assertSame($audit->query, $response->query);
        self::assertSame($audit->totalMatches, $response->totalMatches);
        self::assertSame($audit->results, $response->results);
        self::assertSame($audit->status->value, $response->status);
        self::assertSame($audit->status->getLabel(), $response->statusLabel);
    }

    #[Test]
    public function it_throws_when_audit_does_not_exist(): void
    {
        $repository = $this->createMock(ScreeningAuditRepositoryInterface::class);

        $repository->expects(self::once())
            ->method('findOneBySlug')
            ->with('scr_aud_missing')
            ->willReturn(null);

        $useCase = new GetScreeningInfo($repository);

        $this->expectException(\Webmozart\Assert\InvalidArgumentException::class);

        $useCase('scr_aud_missing');
    }

    private function createScreeningAudit(): ScreeningAudit
    {
        $workspace = Workspace::create(
            name: 'Cabinet Test',
            siret: '12345678901234',
            legalName: 'Cabinet Test SARL',
            address: '1 rue de Paris',
            industry: Industry::LAWYER
        );

        $user = User::create(
            email: 'user@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isVerified: true,
            roles: ['ROLE_USER'],
            isActif: true,
        );

        return ScreeningAudit::create(
            workspace: $workspace,
            ower: $user,
            query: 'John Doe',
            results: [
                ['name' => 'John Doe', 'score' => 95],
            ],
            totalMatches: 1,
        );
    }
}
