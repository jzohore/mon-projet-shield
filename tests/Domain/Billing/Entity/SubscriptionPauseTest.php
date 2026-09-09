<?php

declare(strict_types=1);

namespace App\Tests\Domain\Billing\Entity;

use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class SubscriptionPauseTest extends TestCase
{
    use ReflectionHelperTrait;

    private function subscription(): Subscription
    {
        return $this->createEntityState(Subscription::class, [
            'status' => SubscriptionStatus::ACTIVE,
            'currentPeriodEnd' => new \DateTimeImmutable('+20 days'),
            'cancelAtPeriodEnd' => false,
        ]);
    }

    public function testPausedSubscriptionIsNotValid(): void
    {
        $subscription = $this->subscription();
        self::assertTrue($subscription->isValid());

        $subscription->pause();

        self::assertTrue($subscription->isPaused());
        self::assertFalse($subscription->isValid());

        $subscription->resume();

        self::assertFalse($subscription->isPaused());
        self::assertTrue($subscription->isValid());
    }

    public function testRetentionOfferCanBeClaimedOnlyOnce(): void
    {
        $subscription = $this->subscription();
        self::assertTrue($subscription->canClaimRetentionOffer());

        $subscription->claimRetentionOffer();

        self::assertFalse($subscription->canClaimRetentionOffer());

        $this->expectException(\DomainException::class);
        $subscription->claimRetentionOffer();
    }

    public function testClaimingRetentionOfferCancelsAScheduledCancellation(): void
    {
        $subscription = $this->createEntityState(Subscription::class, [
            'status' => SubscriptionStatus::ACTIVE,
            'currentPeriodEnd' => new \DateTimeImmutable('+20 days'),
            'cancelAtPeriodEnd' => true,
            'reason' => 'too_expensive',
        ]);

        $subscription->claimRetentionOffer();

        self::assertFalse($subscription->cancelAtPeriodEnd);
        self::assertNull($subscription->reason);
    }
}
