<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
            ->add('latitude', NumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => -90,
                        'max' => 90,
                        'notInRangeMessage' => 'La latitude doit être comprise entre -90 et 90 degrés.',
                    ]),
                ],
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Range([
                        'min' => -180,
                        'max' => 180,
                        'notInRangeMessage' => 'La longitude doit être comprise entre -180 et 180 degrés.',
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
            ])
            ->add('images', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'mapped' => false,
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}