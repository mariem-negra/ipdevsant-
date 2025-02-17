<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;

<<<<<<< HEAD
=======

>>>>>>> ff5014e (third commit)
class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nomreserv', TextType::class, [
                'label' => 'Nom de la réservation',
            ])
            ->add('mail', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('nbrpersonne', IntegerType::class, [
                'label' => 'Nombre de personnes',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Réserver',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> ff5014e (third commit)
