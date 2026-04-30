<?php

namespace App\Infrastructure\User\Form;

use App\Application\User\DTO\Request\CreateUserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => [
                    'autocomplete' => 'given-name',
                    'placeholder' => 'Ex: Jean',
                ],
                'empty_data' => '',
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'autocomplete' => 'family-name',
                    'placeholder' => 'Ex: Dupont',
                ],
                'empty_data' => '',
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'jean.dupont@cabinet.fr',
                ],
                'empty_data' => '',
                'help' => 'Astuce : utilisez votre adresse e-mail professionnelle, si vous en avez une, afin de permettre à votre équipe de vous retrouver plus facilement.',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateUserRequest::class,
        ]);
    }
}
