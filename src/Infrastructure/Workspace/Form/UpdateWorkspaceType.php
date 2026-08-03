<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Form;

use App\Application\Workspace\DTO\Request\UpdateWorkspaceRequest;
use App\Domain\Workspace\Enum\Industry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateWorkspaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Raison Sociale',
                'empty_data' => '',
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'empty_data' => '',
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro de SIRET',
                'empty_data' => '',
            ])
            ->add('siren', TextType::class, [
                'label' => 'Numéro de SIREN',
                'empty_data' => '',
            ])
            ->add('workspaceIndustry', EnumType::class, [
                'class' => Industry::class,
                'choice_label' => static fn (Industry $choice): string => $choice->getLabel(),
                'label' => 'Secteur',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateWorkspaceRequest::class,
        ]);
    }
}
