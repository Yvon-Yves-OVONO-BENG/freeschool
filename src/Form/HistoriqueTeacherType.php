<?php

namespace App\Form;

use App\Entity\Day;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\HistoriqueTeacher;
use App\Entity\Sequence;
use App\Entity\Subject;
use App\Repository\DayRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SubjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class HistoriqueTeacherType extends AbstractType
{
    public function __construct(
        protected RequestStack $request, 
        protected TranslatorInterface $translator)
    {}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('heureDebut', TextType::class, [
                'label' => $this->translator->trans('Start time'),
                'attr' => [
                    'placeholder' => '09h30',
                ]
            ])
            ->add('heureFin', TextType::class, [
                'label' => $this->translator->trans('End time'),
                'attr' => [
                    'placeholder' => '09h30',
                ]
            ])
            ->add('nombreHeure', NumberType::class, [
                'label' => $this->translator->trans('Number of hours')
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
            ->add('day', EntityType::class, [
                'label' => $this->translator->trans('Day'),
                'class' => Day::class,
                'placeholder' => '---',
                'choice_label' => 'day'
            ])
            ->add('sequence', EntityType::class, [
                'label' => $this->translator->trans('Sequence'),
                'class' => Sequence::class,
                'placeholder' => '---',
                'choice_label' => 'sequence'
            ])
            ->add('subject', EntityType::class, [
                'label' => $this->translator->trans('Subject'),
                'class' => Subject::class,
                'placeholder' => '---',
                'choice_label' => 'subject',
                'query_builder' => function(SubjectRepository $subjectRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $subjectRepository->findForForm($schoolYear, $subSystem);
                },
                
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriqueTeacher::class,
        ]);
    }
}
