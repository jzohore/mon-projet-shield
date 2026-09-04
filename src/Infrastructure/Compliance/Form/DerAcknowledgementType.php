<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Form;

use App\Application\Compliance\DTO\Request\AcknowledgeDerRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DerAcknowledgementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('declaredName', TextType::class, [
                'label' => 'Vos nom et prénom',
                'attr' => ['placeholder' => 'Jean Dupont', 'autocomplete' => 'name'],
                'empty_data' => '',
            ])
            ->add('accepted', CheckboxType::class, [
                'label' => 'Je reconnais avoir reçu ce jour le Document d\'Entrée en Relation (DER), '
                    . 'en avoir pris connaissance, et en conserver une copie sur support durable.',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcknowledgeDerRequest::class,
        ]);
    }
}
