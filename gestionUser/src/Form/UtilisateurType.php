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

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('motDePasse')
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Admin' => UserRole::ADMIN,
                    'Médecin' => UserRole::MEDECIN,
                    'Patient' => UserRole::PATIENT,
                ],
                'choice_value' => function (?UserRole $role) {
                    return $role?->value; // Use the enum's value for the form
                },
                'choice_label' => function (UserRole $role) {
                    return $role->value; // Use the enum's value for the label
                },
            ])
            ->add('dateNaissance', null, [
                'widget' => 'single_text',
            ])
            ->add('specialite')
            ->add('telephone');
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
