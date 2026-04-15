<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Form;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkspaceInvitationType extends AbstractType
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
            ->add('email', EmailType::class, [
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'jean.dupont@cabinet.fr',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateWorkspaceInvitationRequest::class,
        ]);
    }
}
