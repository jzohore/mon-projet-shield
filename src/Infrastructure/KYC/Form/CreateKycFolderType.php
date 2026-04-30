<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Form;

use App\Application\Kyc\DTO\Request\CreateKycFolderRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateKycFolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contactEmail', EmailType::class, [
                'label' => 'Adresse email professionnelle',
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'jean.dupont@entreprise.com',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('contactFirstName', TextType::class, [
                'label' => 'Prénom du contact',
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex: Jean',
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('contactLastName', TextType::class, [
                'label' => 'Nom du contact',
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex: Dupont',
                    'autocomplete' => 'family-name',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateKycFolderRequest::class,
        ]);
    }
}
