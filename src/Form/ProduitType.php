<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('prix')
            ->add('stock_quantite')
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',  // Important pour accepter un DateTimeInterface
                'required' => false,    // Permet de ne pas rendre ce champ obligatoire
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de l\'événement est obligatoire.',
                    ]),
                ],
                'data' => $options['data']->getDate() ?? new \DateTime(),  // Utilisation de 'data' depuis les options
                
            ])
            ->add('images', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'mapped' => false, // This field is not mapped to the entity
                'required' => true,
                
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
