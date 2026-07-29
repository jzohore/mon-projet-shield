<?php

declare(strict_types=1);

namespace App\Infrastructure\Firm\Form;

use App\Application\Firm\DTO\Request\UpdateRegulatoryProfileRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegulatoryProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oriasNumber', TextType::class, [
                'label' => 'Numéro ORIAS',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex: 12345678',
                    'maxlength' => 8,
                ],
                'help' => 'Votre numéro d\'immatriculation à 8 chiffres.',
            ])
            // 🪄 ASTUCE UX : On bloque les choix pour éviter les fautes de frappe !
            ->add('professionalAssociation', ChoiceType::class, [
                'label' => 'Association Professionnelle',
                'required' => true,
                'placeholder' => 'Choisissez votre association...',
                'choices' => [
                    'CNCGP' => 'CNCGP',
                    'ANACOFI' => 'ANACOFI',
                    'CNCIF' => 'CNCIF',
                    'La Compagnie des CGP' => 'LA_COMPAGNIE_DES_CGP',
                    'Autre' => 'AUTRE',
                ],
            ])
            ->add('rcProInsurer', TextType::class, [
                'label' => 'Assureur RC Pro',
                'required' => true,
                'attr' => ['placeholder' => 'ex: CGPA, AIG, Zurich...'],
            ])
            ->add('rcProPolicyNumber', TextType::class, [
                'label' => 'Numéro de police RC Pro',
                'required' => true,
            ])
            ->add('isIndependent', CheckboxType::class, [
                'label' => 'Je déclare que le capital du cabinet est indépendant',
                'required' => false, // false car une case non cochée est valide en HTML
                'help' => 'Cochez cette case si aucune banque ou compagnie d\'assurance ne détient de parts dans votre cabinet.',
            ])
            // 🪄 LA MAGIE : La collection de partenaires
            ->add('partners', CollectionType::class, [
                'label' => false,
                'entry_type' => PartnerType::class,
                'allow_add' => true,      // Autorise l'ajout dynamique via JS
                'allow_delete' => true,   // Autorise la suppression via JS
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // On lie le formulaire à notre DTO
            'data_class' => UpdateRegulatoryProfileRequest::class,
        ]);
    }
}
