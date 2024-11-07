<?php

namespace App\Form;

use App\Entity\Classroom;
use App\Entity\Lesson;
use App\Entity\Subject;
use App\Repository\LessonRepository;
use App\Repository\SubjectRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ProgressLessonPrevuType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected ClassroomRepository $classroomRepository, 
        protected SubjectRepository $subjectRepository, 
        protected LessonRepository $lessonRepository, 
        protected TokenStorageInterface $tokenStorage
        )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var User
         */
        $user = $this->tokenStorage->getToken()->getUser();
        $teacher = $user->getTeacher();

        $classrooms = $this->lessonRepository->findTeacherLessons($teacher);
        
        // foreach ($classrooms as $classroom) 
        // {
        //     dump($classroom->getClassroom()->getClassroom());
        // }
        // dd('');
        $builder
            ->add('classroom', EntityType::class, [
                'label' => $this->translator->trans("Classroom"),
                'attr' =>  [
                    'class' => 'form-control select2-show-search'
                ],
                'class' => Classroom::class,
                'choice_label' => 'classroom',
                'required' => false,
                'query_builder' => function(ClassroomRepository $classroomRepository){
                    /**
                     * @var User
                     */
                    $user = $this->tokenStorage->getToken()->getUser();
                    $teacher = $user->getTeacher();

                    return $classroomRepository->findClassroomPerTeacher($teacher)
                    ;
                }

            ])
            ->add('subject', EntityType::class, [
                'label' => $this->translator->trans("Subject"),
                'attr' =>  [
                    'class' => 'form-control select2-show-search'
                ],
                'class' => Subject::class,
                'choice_label' => 'subject',
                'required' => false,
                'query_builder' => function(SubjectRepository $subjectRepository)
                {
                    /**
                     * @var User
                     */
                    $user = $this->tokenStorage->getToken()->getUser();
                    $teacher = $user->getTeacher();

                    return $subjectRepository->findSubjectPerTeacher($teacher)
                    ;
                }

            ])
            ->add('nbreLessonTheoriquePrevueSeq1', NumberType::class, [
                'label' => $this->translator->trans("Theoretical"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriquePrevueSeq2', NumberType::class, [
                'label' => $this->translator->trans("Theoretical"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriquePrevueSeq3', NumberType::class, [
                'label' => $this->translator->trans("Theoretical"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriquePrevueSeq4', NumberType::class, [
                'label' => $this->translator->trans("Theoretical"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriquePrevueSeq5', NumberType::class, [
                'label' => $this->translator->trans("Theoretical"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriquePrevueSeq6', NumberType::class, [
                'label' => $this->translator->trans("Theoretical"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiquePrevueSeq1', NumberType::class, [
                'label' => $this->translator->trans("Practice"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiquePrevueSeq2', NumberType::class, [
                'label' => $this->translator->trans("Practice"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiquePrevueSeq3', NumberType::class, [
                'label' => $this->translator->trans("Practice"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiquePrevueSeq4', NumberType::class, [
                'label' => $this->translator->trans("Practice"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiquePrevueSeq5', NumberType::class, [
                'label' => $this->translator->trans("Practice"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiquePrevueSeq6', NumberType::class, [
                'label' => $this->translator->trans("Practice"),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq1', NumberType::class, [
                'label' => $this->translator->trans("Theo. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq2', NumberType::class, [
                'label' => $this->translator->trans("Theo. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq3', NumberType::class, [
                'label' => $this->translator->trans("Theo. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq4', NumberType::class, [
                'label' => $this->translator->trans("Theo. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq5', NumberType::class, [
                'label' => $this->translator->trans("Theo. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonTheoriqueFaiteSeq6', NumberType::class, [
                'label' => $this->translator->trans("Theo. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq1', NumberType::class, [
                'label' => $this->translator->trans("Pra. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq2', NumberType::class, [
                'label' => $this->translator->trans("Pra. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq3', NumberType::class, [
                'label' => $this->translator->trans("Pra. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq4', NumberType::class, [
                'label' => $this->translator->trans("Pra. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq5', NumberType::class, [
                'label' => $this->translator->trans("Pra. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])
            ->add('nbreLessonPratiqueFaiteSeq6', NumberType::class, [
                'label' => $this->translator->trans("Pra. without Res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            /////

            ->add('nbreLessonTheoriqueFaiteAvecRessourceSeq1', NumberType::class, [
                'label' => $this->translator->trans("Theo. with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonTheoriqueFaiteAvecRessourceSeq2', NumberType::class, [
                'label' => $this->translator->trans("Theo. with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonTheoriqueFaiteAvecRessourceSeq3', NumberType::class, [
                'label' => $this->translator->trans("Theo. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonTheoriqueFaiteAvecRessourceSeq4', NumberType::class, [
                'label' => $this->translator->trans("Theo. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonTheoriqueFaiteAvecRessourceSeq5', NumberType::class, [
                'label' => $this->translator->trans("Theo. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonTheoriqueFaiteAvecRessourceSeq6', NumberType::class, [
                'label' => $this->translator->trans("Theo. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ///////////////

            ->add('nbreLessonPratiqueFaiteAvecRessourceSeq1', NumberType::class, [
                'label' => $this->translator->trans("Prat. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonPratiqueFaiteAvecRessourceSeq2', NumberType::class, [
                'label' => $this->translator->trans("Prat. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonPratiqueFaiteAvecRessourceSeq3', NumberType::class, [
                'label' => $this->translator->trans("Prat. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonPratiqueFaiteAvecRessourceSeq4', NumberType::class, [
                'label' => $this->translator->trans("Prat. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonPratiqueFaiteAvecRessourceSeq5', NumberType::class, [
                'label' => $this->translator->trans("Prat. done with res."),
                'required' => false,
                'attr' =>  [
                    'class' => 'form-control'
                ],
            ])

            ->add('nbreLessonPratiqueFaiteAvecRessourceSeq6', NumberType::class, [
                'label' => $this->translator->trans("Prat. done with res."),
                'required' => false,
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
