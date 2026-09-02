<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ExternalApi;

use App\Domain\Workspace\Enum\OriasCheckOutcome;
use App\Infrastructure\ExternalApi\OriasChecker;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OriasCheckerTest extends TestCase
{
    private const string INSCRIT_HTML = <<<'HTML'
        <div id="result">
          <h1 class="bigTitle">WILLIS TOWERS WATSON FRANCE</h1>
          <ul class="listCritere">
            <li><span>Sigle, Enseigne, Nom commercial</span><span>WILLIS TOWERS WATSON FRANCE</span></li>
            <li><span>Etat &amp; Inscriptions</span><span>Inscrit</span></li>
            <li><span>N° Orias</span><span>07001707</span></li>
            <li><span>N° SIREN</span><span>311 248 637</span></li>
          </ul>
          <div class="mainResult">
            <h2 class="mainTitle">Catégories d’inscription</h2>
            <div class="typeInterm"><div class="leftSide">
              <div class="infoBulle">Courtier d'assurance ou de réassurance (COA)</div>
            </div></div>
            <div class="typeInterm"><div class="leftSide">
              <div class="infoBulle">Mandataire d'intermédiaire en opérations de banque et en services de paiement</div>
            </div></div>
          </div>
        </div>
        HTML;

    private const string RADIE_HTML = <<<'HTML'
        <div id="result">
          <h1 class="bigTitle">RegardBTP</h1>
          <ul class="listCritere">
            <li><span>Sigle, Enseigne, Nom commercial</span><span>RegardBTP</span></li>
            <li><span>Etat &amp; Inscriptions</span><span>Radié 0</span></li>
            <li><span>N° SIREN</span><span>451 292 312</span></li>
          </ul>
        </div>
        HTML;

    public function testRejectsANonSirenWithoutAnyHttpCall(): void
    {
        $http = new MockHttpClient();

        $result = $this->checker($http)->checkNumber('07001707'); // n° ORIAS (8 chiffres), pas un SIREN

        self::assertSame(OriasCheckOutcome::NOT_REGISTERED, $result->outcome);
        self::assertSame(0, $http->getRequestsCount());
    }

    public function testReturnsValidForAnInscribedIntermediary(): void
    {
        $result = $this->checker(new MockHttpClient(new MockResponse(self::INSCRIT_HTML)))
            ->checkNumber('311 248 637');

        self::assertSame(OriasCheckOutcome::VALID, $result->outcome);
        self::assertTrue($result->isValid());
        self::assertSame('311248637', $result->oriasNumber);
        self::assertSame('WILLIS TOWERS WATSON FRANCE', $result->legalName);
        self::assertSame('Inscrit', $result->registrationStatus);
        self::assertSame('07001707', $result->registeredOriasNumber);
        self::assertSame([
            "Courtier d'assurance ou de réassurance (COA)",
            'Mandataire d\'intermédiaire en opérations de banque et en services de paiement',
        ], $result->categories);
    }

    public function testReturnsNotRegisteredForARadiatedIntermediary(): void
    {
        $result = $this->checker(new MockHttpClient(new MockResponse(self::RADIE_HTML)))
            ->checkNumber('451292312');

        self::assertSame(OriasCheckOutcome::NOT_REGISTERED, $result->outcome);
        self::assertStringContainsString('radié', mb_strtolower((string) $result->errorMessage));
    }

    public function testReturnsNotRegisteredOnJsonError(): void
    {
        $json = '{"error":"Aucun enregistrement avec le numero de SIREN 999999999"}';
        $result = $this->checker(new MockHttpClient(new MockResponse($json)))->checkNumber('999999999');

        self::assertSame(OriasCheckOutcome::NOT_REGISTERED, $result->outcome);
        self::assertSame('Aucun enregistrement avec le numero de SIREN 999999999', $result->errorMessage);
    }

    public function testReturnsNotRegisteredOnHttp404(): void
    {
        $result = $this->checker(new MockHttpClient(new MockResponse('Not Found', ['http_code' => 404])))
            ->checkNumber('311248637');

        self::assertSame(OriasCheckOutcome::NOT_REGISTERED, $result->outcome);
    }

    public function testReturnsUnavailableOnServerError(): void
    {
        $result = $this->checker(new MockHttpClient(new MockResponse('oops', ['http_code' => 503])))
            ->checkNumber('311248637');

        self::assertSame(OriasCheckOutcome::UNAVAILABLE, $result->outcome);
        self::assertFalse($result->isConclusive());
    }

    public function testReturnsUnavailableOnTransportError(): void
    {
        $result = $this->checker(new MockHttpClient(new MockResponse('', ['error' => 'Connection timed out'])))
            ->checkNumber('311248637');

        self::assertSame(OriasCheckOutcome::UNAVAILABLE, $result->outcome);
    }

    public function testReturnsUnavailableWhenPageStructureIsUnrecognised(): void
    {
        $result = $this->checker(new MockHttpClient(new MockResponse('<html><body><p>Site refait à neuf</p></body></html>')))
            ->checkNumber('311248637');

        self::assertSame(OriasCheckOutcome::UNAVAILABLE, $result->outcome);
    }

    private function checker(MockHttpClient $http): OriasChecker
    {
        return new OriasChecker($http, new NullLogger());
    }
}
