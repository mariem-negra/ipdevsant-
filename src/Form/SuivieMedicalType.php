<?php

namespace App\Form;

use App\Entity\HistoriqueTraitement;
use App\Entity\SuivieMedical;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

class SuivieMedicalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date ne peut pas être vide']),
                    new Type([
                        'type' => '\DateTimeInterface',
                        'message' => 'La date n\'est pas valide'
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                    ])
                ]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'constraints' => [
                    new NotBlank(['message' => 'Le commentaire ne peut pas être vide']),
                    
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SuivieMedical::class,
        ]);
    }
}
