<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',
                ChoiceType::class,
                [
                    'label' => 'Type Document ',
                    'required' => false,
                    'disabled' => false,
                    'placeholder' => '--Merci de selectie-- ',
                    'choices' => [
                        'Atest Non Sinistre' =>  'atestNonSinistre',
                        'Atest Non Assure' => 'atestNonAssure',
                        'Carte Grise Definitive' => 'carteGriseDefinitive',
                        'Devi Signe' => 'deviSigne',
                        'Kbis' => 'kbis',
                        'Permis' => 'permis',
                        'Devoir Conseil' => 'devoirConseil',
                        'Condition Générale' => 'conditionGeneral',
                        'Mondat Sepa' => 'mondatSepa',
                        'Bulletin d\'Adhésion' => 'bulletinAdhesion',

                    ],
                    'expanded' => false,
                    'multiple' => false,
                ]
            )
            ->add('description', TextareaType::class , [
            'label' => 'Description',
            'required' => false,
            'attr' => ['rows' => 3, 'placeholder' => 'Optional description'],
        ])
            ->add('file', FileType::class , [
            'label' => 'File (PDF, image, etc.)',
            'mapped' => true,
            'required' => $options['is_new'],
            'constraints' => [
                new File([
                    'maxSize' => '10M',
                    'mimeTypes' => [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'text/plain',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid document (PDF, Word, Excel, image, or text file).',
                ]),
            ],
        ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class ,
            'is_new' => true,
        ]);
    }
}
