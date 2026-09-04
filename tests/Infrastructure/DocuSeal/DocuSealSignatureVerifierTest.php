<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\DocuSeal;

use App\Infrastructure\DocuSeal\DocuSealSignatureVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DocuSealSignatureVerifierTest extends TestCase
{
    private const string SECRET = 'whsec_test_kysure';
    private const string RAW_BODY = '{"event_type":"form.completed","data":{"submission_id":4242,"status":"completed"}}';

    /**
     * On signe exactement les octets que le vérificateur relit (`$request->getContent()`),
     * jamais un tableau ré-encodé.
     */
    private static function sign(string $body, string $secret = self::SECRET): string
    {
        return hash_hmac('sha256', $body, $secret);
    }

    private function verifier(
        string $secrets = self::SECRET,
        string $sharedToken = '',
        bool $requireSignature = true,
    ): DocuSealSignatureVerifier {
        return new DocuSealSignatureVerifier($secrets, new NullLogger(), $sharedToken, $requireSignature);
    }

    public function testAcceptsABodySignedWithTheSharedSecret(): void
    {
        self::assertTrue(
            $this->verifier()->verify(self::RAW_BODY, self::sign(self::RAW_BODY), null)
        );
    }

    public function testAcceptsATimestampedSignatureWithinTolerance(): void
    {
        $ts = (string) time();
        $signature = $ts . '.' . hash_hmac('sha256', $ts . '.' . self::RAW_BODY, self::SECRET);

        self::assertTrue($this->verifier()->verify(self::RAW_BODY, $signature, null));
    }

    public function testRejectsATimestampedSignatureOutOfTolerance(): void
    {
        $ts = (string) (time() - 3600);
        $signature = $ts . '.' . hash_hmac('sha256', $ts . '.' . self::RAW_BODY, self::SECRET);

        self::assertFalse($this->verifier()->verify(self::RAW_BODY, $signature, null));
    }

    public function testRejectsASignatureComputedOnAnotherBody(): void
    {
        self::assertFalse(
            $this->verifier()->verify(self::RAW_BODY, self::sign('{"tampered":true}'), null)
        );
    }

    #[DataProvider('invalidSignatureHeaders')]
    public function testRejectsStructurallyInvalidSignatures(?string $header): void
    {
        self::assertFalse($this->verifier()->verify(self::RAW_BODY, $header, null));
    }

    /**
     * @return iterable<string, array{0: string|null}>
     */
    public static function invalidSignatureHeaders(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'truncated' => [substr(self::sign(self::RAW_BODY), 0, 32)];
        yield 'whitespace padded' => [' ' . self::sign(self::RAW_BODY) . ' '];
        yield 'uppercased hex' => [strtoupper(self::sign(self::RAW_BODY))];
    }

    public function testRejectsEverythingWhenSecretIsMisconfigured(): void
    {
        // Un secret vide ne doit jamais « ouvrir » le webhook.
        self::assertFalse(
            $this->verifier(secrets: '')->verify(self::RAW_BODY, self::sign(self::RAW_BODY, ''), null)
        );
    }

    public function testSupportsSecretRotationViaCsvList(): void
    {
        $verifier = $this->verifier(secrets: 'whsec_old, whsec_new');

        self::assertTrue($verifier->verify(self::RAW_BODY, self::sign(self::RAW_BODY, 'whsec_old'), null));
        self::assertTrue($verifier->verify(self::RAW_BODY, self::sign(self::RAW_BODY, 'whsec_new'), null));
        self::assertFalse($verifier->verify(self::RAW_BODY, self::sign(self::RAW_BODY, 'whsec_unknown'), null));
    }

    public function testFallsBackToStaticSharedTokenHeader(): void
    {
        $verifier = $this->verifier(sharedToken: 'kysure_token');

        self::assertTrue($verifier->verify(self::RAW_BODY, null, 'kysure_token'));
        self::assertFalse($verifier->verify(self::RAW_BODY, null, 'wrong_token'));
    }

    public function testAcceptsUnsignedWebhookOnlyWhenSignatureIsNotRequired(): void
    {
        self::assertTrue(
            $this->verifier(requireSignature: false)->verify(self::RAW_BODY, null, null)
        );
        self::assertFalse(
            $this->verifier(requireSignature: true)->verify(self::RAW_BODY, null, null)
        );
    }

    public function testUsesConstantTimeComparison(): void
    {
        $source = (string) file_get_contents(
            (string) new \ReflectionClass(DocuSealSignatureVerifier::class)->getFileName()
        );

        self::assertStringContainsString('hash_equals', $source, 'La comparaison de signature doit être en temps constant.');
    }
}
