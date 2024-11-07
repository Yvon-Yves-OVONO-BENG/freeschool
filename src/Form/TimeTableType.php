<?php

namespace App\Form;

use App\Entity\Day;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\TimeTable;
use App\Repository\DayRepository;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class TimeTableType extends AbstractType
{
    public function __construct(protected TranslatorInterface $translator, protected ClassroomRepository $classroomRepository, protected RequestStack $request, protected TeacherRepository $teacherRepository, protected SubjectRepository $subjectRepository, protected DayRepository $dayRepository)
    {
        
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('classroom', EntityType::class, [
                'label' => $this->translator->trans('Classroom'),
                'attr' => [
                    'class' => 'form-control select2-show-search'
                ],
                'class' => Classroom::class,
                'query_builder' => function(ClassroomRepository $classroomRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $classroomRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'classroom',
                'placeholder' => '---',
                'required' => true,
            ])
            ->add('teacher', EntityType::class, [
                'label' => $this->translator->trans('Teacher'),
                'attr' => [
                    'class' => 'form-control select2-show-search'
                ],
                'class' => Teacher::class,
                'placeholder' => '---',
                'required' => false,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findTeacherForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'fullName'
            ])
            ->add('subject', EntityType::class, [
                'label' => $this->translator->trans('Subject'),
                'attr' => [
                    'class' => 'form-control select2-show-search'
                ],
                'class' => Subject::class,
                'placeholder' => '---',
                'required' => false,
                'query_builder' => function(SubjectRepository $subjectRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $subjectRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'subject'
            ])
            ->add('day', EntityType::class, [
                'label' => $this->translator->trans('Day'),
                'attr' => [
                    'class' => 'form-control select2-show-search'
                ],
                'class' => Day::class,
                'placeholder' => '---',
                'required' => true,
                'query_builder' => function(DayRepository $dayRepository){
                    return $dayRepository->createQueryBuilder('d');
                },
                'choice_label' => 'day'
            ])
            // ->add('startTime', TimeType::class, [
            //     'label' => $this->translator->trans('Start time'),
            //     'widget' => 'single_text'
            // ])
            // ->add('endTime', TimeType::class, [
            //     'label' => $this->translator->trans('end time'),
            //     'widget' => 'single_text'
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeTable::class,
        ]);
    }
}
