<?php

namespace App\Infrastructure\KYC\Form;

use App\Application\Kyc\DTO\Request\AddStakeholderRequest;
use App\Domain\Kyc\Enum\StakeholderRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddStakeholderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'data-model' => false,
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'data-model' => false,
                ],
            ])
            ->add('role', EnumType::class, [
                'class' => StakeholderRole::class,
                'choice_label' => fn(StakeholderRole $choice) => $choice->getLabel(),
                'label' => 'Rôle',
            ])
            ->add('percentage', NumberType::class, [
                'label' => '% de détention (Optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 50',
                    'data-model' => false,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddStakeholderRequest::class,
        ]);
    }
}
