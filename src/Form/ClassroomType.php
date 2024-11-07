<?php

namespace App\Form;

use App\Entity\Level;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Repository\LevelRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ClassroomType extends AbstractType
{
    public function __construct(protected RequestStack $request, protected TranslatorInterface $translator)
    {
    }
   
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('classroom', TextType::class, [
                'label' => $this->translator->trans('Classroom'),
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('level', EntityType::class, [
                'label' => $this->translator->trans('Level'),
                'class' => Level::class,
                'query_builder' => function(LevelRepository $levelRepository){
                    $mySession = $this->request->getSession();
                    $subSystem = $mySession->get('subSystem');
                    if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                    {
                        return $levelRepository->findLevelForFormFr();
                    } else 
                    {
                        return $levelRepository->findLevelForFormEn();
                    }
                    
                    
                },
                'choice_label' => 'level'
            ])
            ->add('principalTeacher', EntityType::class, [
                'label' => $this->translator->trans('Principal teacher'),
                'class' => Teacher::class,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findTeacherForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'fullName'
            ])
            ->add('censor', EntityType::class, [
                'label' => $this->translator->trans('Attached Vice Principal'),
                'class' => Teacher::class,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findCensorForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'fullName'
            ])
            ->add('supervisor', EntityType::class, [
                'label' => $this->translator->trans('Attached supervisor'),
                'class' => Teacher::class,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findSupervisorForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'fullName'
            ])
            ->add('counsellor', EntityType::class, [
                'label' => $this->translator->trans('Attached counsillor'),
                'class' => Teacher::class,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findCounsellorForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'fullName'
            ])
            ->add('actionSociale', EntityType::class, [
                'label' => $this->translator->trans('Social Action'),
                'class' => Teacher::class,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findSocialActionForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'fullName'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Classroom::class,
        ]);
    }
}
