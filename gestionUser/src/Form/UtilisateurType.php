<?php
namespace App\Form;

use App\Enum\UserRole;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('prenom', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('motDePasse', PasswordType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Médecin' => UserRole::MEDECIN,
                    'Patient' => UserRole::PATIENT,
                ],
                'choice_value' => function (?UserRole $role) {
                    return $role?->value;
                },
                'choice_label' => function (UserRole $role) {
                    return $role->value;
                },
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'required' => false, // This allows the field to be optional
            ])
            ->add('telephone', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ]);

        // Add the "specialite" field dynamically for MEDECIN or ADMIN role
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $utilisateur = $event->getData();
            $form = $event->getForm();

            if ($utilisateur && in_array($utilisateur->getRole(), [UserRole::MEDECIN, UserRole::ADMIN])) {
                $form->add('specialite', TextType::class, [
                    'attr' => ['class' => 'form-control'],
                    'required' => true, // Make the field required for MEDECIN and ADMIN
                ]);
            }
            if ($utilisateur && in_array($utilisateur->getRole(), [UserRole::MEDECIN, UserRole::ADMIN])) {
                $form->add('diploma', TextType::class, [
                    'attr' => ['class' => 'form-control'],
                    'required' => true, // Make the field required for MEDECIN and ADMIN
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['role']) && in_array($data['role'], [UserRole::MEDECIN->value, UserRole::ADMIN->value])) {
                $form->add('specialite', TextType::class, [
                    'attr' => ['class' => 'form-control'],
                    'required' => true, // Make the field required for MEDECIN and ADMIN
                ]);
            }
            if (isset($data['role']) && in_array($data['role'], [UserRole::MEDECIN->value, UserRole::ADMIN->value])) {
                $form->add('diploma', FileType::class, [
                    'attr' => ['class' => 'form-control'],
                    'required' => true,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'validation_groups' => function ($form) {
                $data = $form->getData();
                $groups = ['Default'];

                if ($data->getRole() === UserRole::MEDECIN) {
                    $groups[] = 'medecin';
                }

                return $groups;
            },
        ]);
    }
}