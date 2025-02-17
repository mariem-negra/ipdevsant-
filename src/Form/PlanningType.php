<?php

// src/Form/PlanningType.php

namespace App\Form;

use App\Entity\Planning;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('jour', ChoiceType::class, [
            'choices' => [
                'Lundi' => 'Lundi',
                'Mardi' => 'Mardi',
                'Mercredi' => 'Mercredi',
                'Jeudi' => 'Jeudi',
                'Vendredi' => 'Vendredi',
                'Samedi' => 'Samedi',
                'Dimanche' => 'Dimanche',
            ],
            'label' => 'Jour de la semaine',
        ])
            ->add('heuredebut', TimeType::class, [
                'widget' => 'single_text',  // For time input
                'input' => 'datetime',     // To handle DateTimeInterface
            ])
            ->add('heurefin', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('save', SubmitType::class, ['label' => 'Save Planning']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
        ]);
    }
}