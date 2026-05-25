<?php

namespace App\Infrastructure\User\Form;

use App\Application\User\DTO\Request\UpdateNameRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => [
                    'autocomplete' => 'given-name',
                    'placeholder' => 'Ex: Jean',
                ],
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'autocomplete' => 'family-name',
                    'placeholder' => 'Ex: Dupont',
                ],
            ])
            ->add('number', TextType::class, [
                'attr' => [
                    'autocomplete' => 'family-name',
                    'placeholder' => 'Ex: Dupont',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateNameRequest::class,
        ]);
    }
}
