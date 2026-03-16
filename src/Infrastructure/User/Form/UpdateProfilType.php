<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Form;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Domain\User\Enum\JobRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobTitle', ChoiceType::class, [
                'label' => 'Quel est votre rôle principal ?',
                'placeholder' => 'Sélectionnez votre métier...',
                'choices' => JobRole::getGroupedChoices(), // Notre Enum magique avec les catégories
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Numéro de téléphone portable',
                'attr' => [
                    'placeholder' => '+33 6 12 34 56 78',
                    'maxlength' => 10,
                ],
                'help' => 'Nécessaire pour la double authentification (MFA) lors d\'actions sensibles.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfilRequest::class,
        ]);
    }
}
