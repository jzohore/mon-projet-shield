<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Form;

use App\Application\Workspace\DTO\Request\EditWorkspaceRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditWorkspaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Raison Sociale',
                'empty_data' => '',
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro de SIRET',
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EditWorkspaceRequest::class,
            'csrf_protection' => true,
        ]);
    }
}
