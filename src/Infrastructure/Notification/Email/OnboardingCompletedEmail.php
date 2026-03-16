<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Webmozart\Assert\Assert;

class OnboardingCompletedEmail extends TemplatedEmail
{
    public function __construct(?string $email, ?string $firstname, ?string $workspaceName, string $dashboardUrl)
    {
        parent::__construct();
        Assert::notNull($email);
        $this
            ->to(new Address(address: $email))
            ->subject('Bienvenue sur Shield - Votre espace est prêt')
            ->htmlTemplate('emails/onboarding/completed.html.twig')
            ->context([
                'first_name' => $firstname,
                'workspace_name' => $workspaceName,
                'action_url' => $dashboardUrl,
            ]);
    }
}
