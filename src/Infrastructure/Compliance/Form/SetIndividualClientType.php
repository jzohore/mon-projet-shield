<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Form;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetIndividualClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Jean', 'autocomplete' => 'given-name'],
                'empty_data' => '',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Dupont', 'autocomplete' => 'family-name'],
                'empty_data' => '',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de contact',
                'attr' => ['placeholder' => 'jean.dupont@email.com', 'autocomplete' => 'email'],
                'empty_data' => '',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SetIndividualClientRequest::class,
        ]);
    }
}
