<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Form;

use App\Application\Workspace\DTO\Request\SuspendWorkspaceRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuspendedWorkspaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('suspensionReason', TextType::class, [
                'label' => 'Raison de la suspension',
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SuspendWorkspaceRequest::class,
            'csrf_protection' => true,
        ]);
    }
}
