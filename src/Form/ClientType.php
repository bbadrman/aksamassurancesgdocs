<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class , [
            'label' => 'First Name',
            'attr' => ['placeholder' => 'First name'],
        ])
            ->add('prenom', TextType::class , [
            'label' => 'Last Name',
            'attr' => ['placeholder' => 'Last name'],
        ])
            ->add('email', EmailType::class , [
            'label' => 'Email',
            'required' => false,
            'attr' => ['placeholder' => 'email@example.com'],
        ])
            ->add('phone', TelType::class , [
            'label' => 'Phone',
            'required' => false,
            'attr' => ['placeholder' => '+33 6 00 00 00 00'],
        ])
            ->add('adress', TextareaType::class , [
            'label' => 'Address',
            'required' => false,
            'attr' => ['rows' => 3, 'placeholder' => 'Street, City, Zip'],
        ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class ,
        ]);
    }
}
