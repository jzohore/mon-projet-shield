<?php

namespace App\Infrastructure\Screening\Form;

use App\Application\Screening\DTO\Request\ScreeningRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScreeningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameToSearch', SearchType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Rechercher une personne ou une entité (ex: Jean Dupont)',
                ],
                'help' => 'Chaque nouvelle recherche débite 1 crédit. Les consultations d\'un même profil sont gratuites pendant 24h.',
            ])
            ->add('schemaToSearch', ChoiceType::class, [
                'choices' => [
                    'Personne' => 'Person',
                    'Entreprise' => 'Company',
                ],
                'label' => false,
                'expanded' => true,
                'data' => 'Person',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ScreeningRequest::class,
        ]);
    }
}
