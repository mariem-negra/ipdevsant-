<?php

namespace App\Form;

use App\Entity\Reminder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReminderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => false,
            ])
            ->add('dateTime', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('notifyBefore', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    '5 minutes' => '5',
                    '15 minutes' => '15',
                    '30 minutes' => '30',
                    '1 hour' => '60',
                    '1 day' => '1440',
                ],
            ])
            ->add('repeatType', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'None' => 'none',
                    'Daily' => 'daily',
                    'Weekly' => 'weekly',
                    'Monthly' => 'monthly',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reminder::class,
        ]);
    }
}