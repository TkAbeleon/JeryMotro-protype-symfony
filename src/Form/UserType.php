<?php
// src/Form/UserType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'attr'  => ['placeholder' => 'Ex : Ranger Menabe', 'class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr'  => ['placeholder' => 'ranger@eaux-forets.mg', 'class' => 'form-control'],
            ])
            ->add('phone', TelType::class, [
                'label'    => 'Téléphone (optionnel)',
                'required' => false,
                'attr'     => ['placeholder' => '+261340000000', 'class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
