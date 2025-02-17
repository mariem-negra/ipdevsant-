<?php

// src/Form/RendezVousType.php

// src/Form/RendezVousType.php

namespace App\Form;

use App\Entity\Planning;
use App\Entity\RendezVous;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RendezVousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('dateheure', DateTimeType::class, [
            'widget' => 'single_text',
            'label' => 'Date et Heure du rendez-vous',
            'constraints' => [
                new Assert\NotBlank(['message' => 'Veuillez sélectionner une date et une heure.']),
                new Assert\GreaterThan(['value' => 'today', 'message' => 'La date doit être dans le futur.'])
            ]
        ])
        ->add('statut', ChoiceType::class, [
            'choices' => [
                'Confirmé' => 'Confirmé',
                'Annulé' => 'Annulé',
                'En attente' => 'En attente',
            ],
            'expanded' => false, // Utilise un menu déroulant
            'multiple' => false,
            'label' => 'Statut',
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le statut est obligatoire.']),
                new Assert\Choice([
                    'choices' => ['Confirmé', 'Annulé', 'En attente'],
                    'message' => 'Statut invalide. Veuillez sélectionner "Confirmé", "Annulé" ou "En attente".'
                ]),
            ]
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'constraints' => [
                new Assert\NotBlank(['message' => 'Veuillez entrer une description.']),
                new Assert\Length(['min' => 10, 'minMessage' => 'La description doit contenir au moins 10 caractères.'])
            ]
        ])
        ->add('planning', EntityType::class, [
            'class' => Planning::class,
            'choice_label' => 'jour',
            'label' => 'Planning du médecin',
            'constraints' => [
                new Assert\NotNull(['message' => 'Veuillez sélectionner un planning.']),
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RendezVous::class,
        ]);
    }
}
