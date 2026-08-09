<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Form;

use App\Application\User\DTO\Request\CreateUserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Setter neutre : empêche PropertyAccessor de tenter d'écrire sur le DTO readonly
        $noOpSetter = static fn (): null => null;

        $builder
            ->add('firstName', TextType::class, [
                'attr' => [
                    'autocomplete' => 'given-name',
                    'placeholder' => 'Ex: Jean',
                ],
                'empty_data' => '',
                'setter' => $noOpSetter,
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'autocomplete' => 'family-name',
                    'placeholder' => 'Ex: Dupont',
                ],
                'empty_data' => '',
                'setter' => $noOpSetter,
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'jean.dupont@cabinet.fr',
                ],
                'empty_data' => '',
                'help' => 'Astuce : utilisez votre adresse e-mail professionnelle afin de permettre à votre équipe de vous retrouver facilement.',
                'setter' => $noOpSetter,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateUserRequest::class,
            'csrf_protection' => true,
            'empty_data' => static fn (FormInterface $form): CreateUserRequest => new CreateUserRequest(
                email: (string) $form->get('email')->getData(),
                firstName: (string) $form->get('firstName')->getData(),
                lastName: (string) $form->get('lastName')->getData(),
            ),
        ]);
    }
}
