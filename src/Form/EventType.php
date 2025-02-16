<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints as Assert;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('titre', TextType::class, [
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Le titre ne peut pas être vide.'
                ]),
                new Assert\Length([
                    'min' => 4,
                    'minMessage' => 'Le titre doit contenir au moins 4 caractères.',
                    'max' => 255,
                    'maxMessage' => 'Le titre ne peut pas dépasser 255 caractères.'
                ]),
            ],
        ])
        ->add('dateevent', DateType::class, [
            'widget' => 'single_text',
            'input' => 'datetime',
            'required' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'La date de l\'événement est obligatoire.',
                ]),
            ],
            'data' => $options['data']->getDateevent() ?? new \DateTime(),
        ])
        ->add('lieu', TextType::class, [
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Le lieu ne peut pas être vide.'
                ]),
                new Assert\Length([
                    'min' => 4,
                    'minMessage' => 'Le lieu doit contenir au moins 4 caractères.',
                    'max' => 255,
                    'maxMessage' => 'Le lieu ne peut pas dépasser 255 caractères.'
                ]),
            ],
        ])
        ->add('discription', TextType::class, [
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'La description ne peut pas être vide.'
                ]),
                new Assert\Length([
                    'min' => 4,
                    'minMessage' => 'La description doit avoir au moins 4 caractères.',
                    'max' => 255,
                    'maxMessage' => 'La description ne peut pas dépasser 255 caractères.'
                ]),
            ],
        ])
        ->add('nbplace', IntegerType::class, [
            'constraints' => [
                new Assert\Positive(['message' => 'Le nombre de places doit être un chiffre positif.']),
            ],
            'attr' => ['min' => 1, 'class' => 'form-input'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
