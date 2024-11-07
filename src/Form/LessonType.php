<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\SubjectGroup;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\Form\AbstractType;
use App\Repository\SubjectGroupRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LessonType extends AbstractType
{
    public function __construct(
        protected RequestStack $request, 
        protected TranslatorInterface $translator)
    {}
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('coefficient', NumberType::class, [
                'label' => $this->translator->trans('Coefficient')
            ])
            ->add('teacher', EntityType::class, [
                'label' => $this->translator->trans('Teacher'),
                'class' => Teacher::class,
                'placeholder' => '---',
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
                'class' => Subject::class,
                'placeholder' => '---',
                'query_builder' => function(SubjectRepository $subjectRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $subjectRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'subject'
            ])
            ->add('classroom', EntityType::class, [
                'label' => $this->translator->trans('Classroom'),
                'placeholder' => '---',
                'class' => Classroom::class,
                'query_builder' => function(ClassroomRepository $classroomRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $classroomRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'classroom'
            ])
            ->add('weekHours', IntegerType::class, [
                'label' => $this->translator->trans('Weekly hours'),
            ])
            ->add('subjectGroup', EntityType::class, [
                'label' => $this->translator->trans('Group'),
                'class' => SubjectGroup::class,
                'choice_label' => 'subjectGroup'
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
