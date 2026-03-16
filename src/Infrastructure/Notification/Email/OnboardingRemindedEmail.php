<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Webmozart\Assert\Assert;

class OnboardingRemindedEmail extends TemplatedEmail
{
    public function __construct(?string $email, ?string $firstname, string $dashboardUrl)
    {
        parent::__construct();
        Assert::notNull($email);
        $this
            ->to(new Address(address: $email))
            ->subject('Terminez votre inscription')
            ->htmlTemplate('emails/onboarding/reminder.html.twig')
            ->context([
                'first_name' => $firstname,
                'action_url' => $dashboardUrl,
            ]);
    }
}
