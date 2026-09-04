<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Form;

use App\Application\Compliance\DTO\Request\DeclineDerRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DerDeclineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reason', TextareaType::class, [
            'label' => 'Motif (facultatif)',
            'required' => false,
            'attr' => ['rows' => 3, 'placeholder' => 'Précisez si vous le souhaitez la raison de votre refus.'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeclineDerRequest::class,
        ]);
    }
}
