<?php

namespace App\Form;

use App\Entity\HistoriqueTraitement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;


class HistoriqueTraitementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {$builder
        ->add('maladie', TextType::class, [
            'label' => 'Maladie',
        ])
        ->add('description', TextType::class, [
            'label' => 'Description',
        ])
        ->add('type_traitement', TextType::class, [
            'label' => 'Type de traitement',
        ])
        ->add('nom', TextType::class, [
            'label' => 'Nom',
        ])
        ->add('prenom', TextType::class, [
            'label' => 'Prénom',
        ])
        ->add('bilan', FileType::class, [
            'label' => 'Bilan (PDF ou Image)',
            'mapped' => true, // Changer à true
            'required' => false,
            'data_class' => null,
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF ou une image valide.',
                ]),
            ],
            'attr' => [
                'accept' => '.pdf,.jpg,.jpeg,.png'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriqueTraitement::class,
        ]);
    }
}
