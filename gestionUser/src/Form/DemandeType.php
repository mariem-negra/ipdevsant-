<?php

namespace App\Form;

use App\Entity\Demande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Eau', NumberType::class, [
                'label' => 'Eau consommée (L)',
            ])
            ->add('Nbr_Repas', ChoiceType::class, [
                'label' => 'Nombre de repas',
                'choices' => [
                    '1 repas' => 1,
                    '2 repas' => 2,
                    '3 repas' => 3,
                ],
                'placeholder' => 'Sélectionnez le nombre de repas',
                'expanded' => true,
            ])
            ->add('Snacks', ChoiceType::class, [
                'label' => 'Snacks consommés ?',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
            ])
            ->add('Calories', ChoiceType::class, [
                'label' => 'Calories consommées',
                'choices' => [
                    '1500-1700 kcal' => 1600,
                    '1800-2000 kcal' => 1900,
                    '2100-2300 kcal' => 2200,
                    '2400-2600 kcal' => 2500,
                    '2700-3000 kcal' => 2850,
                ],
                'placeholder' => 'Sélectionnez une plage calorique',
            ])
            ->add('Activity', ChoiceType::class, [
                'label' => 'Activités journalières',
                'choices' => [
                    'Aucune' => 'aucune',
                    'Marche' => 'marche',
                    'Course à pied' => 'course à pied',
                    'Cyclisme' => 'cyclisme',
                    'Natation' => 'natation',
                    'Yoga' => 'yoga',
                    'Musculation' => 'musculation',
                    'Danse' => 'danse',
                    'Escalade' => 'escalade',
                    'Gymnastique' => 'gymnastique',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Sélectionnez une activité',
            ])
            ->add('Sommeil', ChoiceType::class, [
                'label' => 'Qualité du sommeil',
                'choices' => [
                    'Très bon' => 'Très bon',
                    'Bon' => 'Bon',
                    'Moyen' => 'Moyen',
                    'Mauvais' => 'Mauvais',
                ],
            ])
            ->add('Duree_Activite', NumberType::class, [
                'label' => 'Durée d\'activité (heures)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
        ]);
    }
}
