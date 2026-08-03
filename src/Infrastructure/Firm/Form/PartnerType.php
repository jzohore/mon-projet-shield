<?php

declare(strict_types=1);

namespace App\Infrastructure\Firm\Form;

use App\Application\Firm\DTO\Request\PartnerDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du partenaire',
                'attr' => ['placeholder' => 'ex: Generali, SwissLife...'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse du siège',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de contact',
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PartnerDTO::class,
        ]);
    }
}
