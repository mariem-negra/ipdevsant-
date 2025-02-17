<?php

namespace App\Form;

use App\Entity\Recommandation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecommandationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get the related Demande from options, or default to null
        $demande = $options['demande'] ?? null;
        
        // Default values if demande is missing
        $nbrRepas = $demande ? $demande->getNbrRepas() : 3;
        $activityValue = $demande ? $demande->getActivity() : 'aucune';
        $sommeilValue = $demande ? $demande->getSommeil() : 'bon';

        // Define separate meal options for each meal type.
        $petitDejOptions = [
            'Croissant et café' => 'Croissant et café',
            'Pain complet et confiture' => 'Pain complet et confiture',
            'Omelette et jus d\'orange' => 'Omelette et jus d\'orange',
            'Yaourt et fruits' => 'Yaourt et fruits',
            'Céréales et lait' => 'Céréales et lait',
            'Smoothie aux fruits' => 'Smoothie aux fruits',
            'Toast avocat' => 'Toast avocat',
            'Porridge aux baies' => 'Porridge aux baies',
            'Muesli et yaourt' => 'Muesli et yaourt',
            'Pancakes légers' => 'Pancakes légers',
        ];

        $dejeunerOptions = [
            'Salade de quinoa' => 'Salade de quinoa',
            'Poulet grillé et légumes' => 'Poulet grillé et légumes',
            'Poisson et riz complet' => 'Poisson et riz complet',
            'Sandwich au poulet' => 'Sandwich au poulet',
            'Soupe de légumes' => 'Soupe de légumes',
            'Wrap végétarien' => 'Wrap végétarien',
            'Pâtes intégrales' => 'Pâtes intégrales',
            'Bol de poke' => 'Bol de poke',
            'Salade César' => 'Salade César',
            'Riz sauté aux légumes' => 'Riz sauté aux légumes',
        ];

        $dinerOptions = [
            'Steak et légumes' => 'Steak et légumes',
            'Saumon et quinoa' => 'Saumon et quinoa',
            'Ragoût de légumes' => 'Ragoût de légumes',
            'Curry de poulet' => 'Curry de poulet',
            'Pâtes à la bolognaise' => 'Pâtes à la bolognaise',
            'Pizza aux légumes' => 'Pizza aux légumes',
            'Tacos de poisson' => 'Tacos de poisson',
            'Boeuf bourguignon' => 'Boeuf bourguignon',
            'Risotto aux champignons' => 'Risotto aux champignons',
            'Gratin de courgettes' => 'Gratin de courgettes',
        ];

        // Predefined options for activity, supplements, and calories remain as before.
        $activityOptions = [
            'Marche rapide' => 'Marche rapide',
            'Natation' => 'Natation',
            'Cyclisme' => 'Cyclisme',
            'Yoga' => 'Yoga',
            'Musculation légère' => 'Musculation légère',
            'Pilates' => 'Pilates',
            'Danse' => 'Danse',
            'Tennis' => 'Tennis',
            'Course à pied' => 'Course à pied',
            'Aviron' => 'Aviron',
        ];

        $supplementOptions = [
            'Mélatonine' => 'Mélatonine',
            'Thé à la camomille' => 'Thé à la camomille',
            'Magnésium' => 'Magnésium',
            'Glycine' => 'Glycine',
            'Ashwagandha' => 'Ashwagandha',
        ];

        $calorieOptions = [
            '1500-1700 kcal' => '1500-1700 kcal',
            '1800-2000 kcal' => '1800-2000 kcal',
            '2100-2300 kcal' => '2100-2300 kcal',
            '2400-2600 kcal' => '2400-2600 kcal',
            '2700-3000 kcal' => '2700-3000 kcal',
        ];

        // Duration options depend on the activity value.
        $durationOptions = ($activityValue === 'aucune') ? [15, 30, 45] : [60, 75, 90, 120];

        // Add meal fields based on NbrRepas
        if ($nbrRepas == 1) {
            $builder->add('Dejeuner', ChoiceType::class, [
                'label' => 'Déjeuner Recommandé',
                'choices' => $dejeunerOptions,
                'placeholder' => 'Sélectionnez un repas',
            ]);
        }
        if ($nbrRepas == 2) {
            $builder->add('Dejeuner', ChoiceType::class, [
                'label' => 'Déjeuner Recommandé',
                'choices' => $dejeunerOptions,
                'placeholder' => 'Sélectionnez un repas',
            ]);
            $builder->add('Diner', ChoiceType::class, [
                'label' => 'Dîner Recommandé',
                'choices' => $dinerOptions,
                'placeholder' => 'Sélectionnez un repas',
            ]);
        }
        if ($nbrRepas === 3) {
            $builder->add('Petit_Dejeuner', ChoiceType::class, [
                'label' => 'Petit Déjeuner Recommandé',
                'choices' => $petitDejOptions,
                'placeholder' => 'Sélectionnez un repas',
            ]);
            $builder->add('Dejeuner', ChoiceType::class, [
                'label' => 'Déjeuner Recommandé',
                'choices' => $dejeunerOptions,
                'placeholder' => 'Sélectionnez un repas',
            ]);
            $builder->add('Diner', ChoiceType::class, [
                'label' => 'Dîner Recommandé',
                'choices' => $dinerOptions,
                'placeholder' => 'Sélectionnez un repas',
            ]);
        }

        // Add activity and duration fields
        $builder
            ->add('Activity', ChoiceType::class, [
                'label' => 'Activité Sportive Recommandée',
                'choices' => $activityOptions,
                'placeholder' => 'Sélectionnez une activité',
            ])
            ->add('Duree', ChoiceType::class, [
                'label' => 'Durée d\'activité recommandée',
                'choices' => array_combine($durationOptions, $durationOptions),
                'placeholder' => 'Sélectionnez une durée',
            ])
            ->add('Calories', ChoiceType::class, [
                'label' => 'Calories Recommandées',
                'choices' => $calorieOptions,
                'placeholder' => 'Sélectionnez une plage calorique',
            ]);

        // Add Supplements field only if sleep quality is "moyen" or "mauvais"
        if (in_array(strtolower($sommeilValue), ['moyen', 'mauvais'])) {
            $builder->add('Supplements', ChoiceType::class, [
                'label' => 'Suppléments Recommandés',
                'choices' => $supplementOptions,
                'placeholder' => 'Sélectionnez un supplément',
            ]);
        }

        // Optionally, add a submit button if not added in your Twig template.
        //$builder->add('save', SubmitType::class, [
        //    'label' => 'Enregistrer la Recommandation',
        //    'attr' => ['class' => 'btn btn-primary mt-3'],
        //]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recommandation::class,
            'demande' => null, // Expect a Demande object to adjust the form fields
        ]);
    }
}
