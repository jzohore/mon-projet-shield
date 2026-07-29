<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Form;

use App\Application\User\DTO\Request\LoginUserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $noOpSetter = static fn (): null => null;

        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'jean.dupont@cabinet.fr',
                ],
                'empty_data' => '',
                'help' => 'Optez pour une adresse e-mail de votre organisation afin de fluidifier vos échanges avec vos collègues.',
                'setter' => $noOpSetter,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LoginUserRequest::class,
            'empty_data' => static fn (FormInterface $form): LoginUserRequest => new LoginUserRequest(
                email: (string) $form->get('email')->getData(),
            ),
        ]);
    }
}
