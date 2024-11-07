<?php

namespace App\Form;

use App\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ProgressLessonFaiteType extends AbstractType
{
    public function __construct(protected TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nbreLessonTheoriqueFaiteSeq1', NumberType::class, [
                'label' => $this->translator->trans("Theoretical Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq2', NumberType::class, [
                'label' => $this->translator->trans("Theoretical Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq3', NumberType::class, [
                'label' => $this->translator->trans("Theoretical Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq4', NumberType::class, [
                'label' => $this->translator->trans("Theoretical Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq5', NumberType::class, [
                'label' => $this->translator->trans("Theoretical Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq6', NumberType::class, [
                'label' => $this->translator->trans("Theoretical Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq1', NumberType::class, [
                'label' => $this->translator->trans("Practice Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq2', NumberType::class, [
                'label' => $this->translator->trans("Practice Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq3', NumberType::class, [
                'label' => $this->translator->trans("Practice Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq4', NumberType::class, [
                'label' => $this->translator->trans("Practice Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq5', NumberType::class, [
                'label' => $this->translator->trans("Practice Done"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq6', NumberType::class, [
                'label' => $this->translator->trans("Practice Done"),
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
