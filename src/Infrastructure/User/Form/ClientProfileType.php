<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Form;

use App\Application\Portal\DTO\Request\UpdateClientProfileRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'given-name'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'empty_data' => '',
                'attr' => ['autocomplete' => 'family-name'],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['autocomplete' => 'tel', 'placeholder' => '06 12 34 56 78'],
                'help' => 'Utilisé par votre conseiller pour vous joindre.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateClientProfileRequest::class,
        ]);
    }
}
