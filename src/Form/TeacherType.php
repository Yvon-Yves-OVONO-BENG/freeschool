<?php

namespace App\Form;

use App\Entity\ConstantsClass;
use App\Entity\Sex;
use App\Entity\Duty;
use App\Entity\Grade;
use App\Entity\Region;
use App\Entity\Status;
use App\Entity\Diploma;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Department;
use App\Entity\Division;
use App\Entity\MatrimonialStatus;
use App\Entity\Subdivision;
use App\Repository\DutyRepository;
use App\Repository\GradeRepository;
use App\Repository\RegionRepository;
use App\Repository\SubjectRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Repository\DepartmentRepository;
use App\Repository\DivisionRepository;
use App\Repository\SubdivisionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TeacherType extends AbstractType
{
    public function __construct(protected RequestStack $request, protected TranslatorInterface $translator, protected TokenStorageInterface $tokenStorage)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => $this->translator->trans('Full name'),
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('administrativeNumber', TextType::class, [
                'label' => $this->translator->trans('Matricule')
            ])
            ->add('grade', EntityType::class, [
                'label' => $this->translator->trans('Rank'),
                'class' => Grade::class,
                'query_builder' => function(GradeRepository $gradeRepository){
                    return $gradeRepository->createQueryBuilder('g')->orderBy('g.grade');
                },
                'choice_label' => 'grade'
            ])
            ->add('sex', EntityType::class, [
                'label' => $this->translator->trans('Gender'),
                'class' => Sex::class,
                'choice_label' => 'sex'
            ])
            ->add('duty', EntityType::class, [
                'label' => $this->translator->trans('Duty'),
                'class' => Duty::class,
                'query_builder' => function(DutyRepository $dutyRepository){
                    return $dutyRepository->createQueryBuilder('d')->orderBy('d.duty');
                },
                'choice_label' => 'duty'
            ])
           
            ->add('department', EntityType::class, [
                'label' => $this->translator->trans('Department'),
                'class' => Department::class,
                'choice_label' => 'department',
                'placeholder' => '---',
                'query_builder' => function(DepartmentRepository $departmentRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $departmentRepository->findForForm($schoolYear, $subSystem);
                }
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => $this->translator->trans('Phone number')
            ])
            
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event)
        {
            $teacherForm = $event->getForm();
            $teacher = $event->getData();
            
            /**
             * @var User
             */
            $user = $this->tokenStorage->getToken()->getUser();
            $role = $user->getRoles();
            if(in_array(ConstantsClass::ROLE_CENSOR, $role) || in_array(ConstantsClass::ROLE_ACCOUNTER, $role) || in_array(ConstantsClass::ROLE_ECONOME, $role) || in_array(ConstantsClass::ROLE_COUNSELLOR , $role) || in_array(ConstantsClass::ROLE_MEDICAL, $role) || in_array(ConstantsClass::ROLE_SECRETARY, $role) || in_array(ConstantsClass::ROLE_SOCIAL, $role) || in_array(ConstantsClass::ROLE_SUPERVISOR, $role) || in_array(ConstantsClass::ROLE_TEACHER, $role) || in_array(ConstantsClass::ROLE_TREASURER, $role))
            // if($this->request->getSession()->get('mySession', ['pe']) == 1)
            {
                $teacherForm
                    ->add('integrationDate', DateType::class, [
                        'label' => $this->translator->trans('Integration date'),
                        'widget' => 'single_text'
                    ])
                    ->add('birthday', DateType::class, [
                        'label' => $this->translator->trans('Birthday'),
                        'widget' => 'single_text'
                    ])
                    ->add('birthplace', TextType::class, [
                        'label' => $this->translator->trans('Birthplace')
                    ])
                    ->add('matrimonialStatus', EntityType::class, [
                        'label' => $this->translator->trans('Matrimonial status'),
                        'placeholder' => '---',
                        'class' => MatrimonialStatus::class,
                        'choice_label' => 'matrimonialStatus'
                    ])
                    ->add('status', EntityType::class, [
                        'label' => $this->translator->trans('Professional status'),
                        'placeholder' => '---',
                        'class' => Status::class,
                        'choice_label' => 'status'
                    ])
                    ->add('diploma', EntityType::class, [
                        'label' => $this->translator->trans('Professional diploma'),
                        'placeholder' => '---',
                        'class' => Diploma::class,
                        'choice_label' => 'diploma'
                    ])
                    ->add('affectationDate', DateType::class, [
                        'label' => $this->translator->trans('Date of affectation in actual post'),
                        'widget' => 'single_text'
                    ])
                    ->add('region', EntityType::class, [
                        'label' => $this->translator->trans('Region of origin'),
                        'placeholder' => '---',
                        'class' => Region::class,
                        'choice_label' => 'region',
                        'query_builder' =>function(RegionRepository $regionRepository){
                            return $regionRepository->createQueryBuilder('r')->orderBy('r.region');
                        }
                    ])
                    ->add('division', EntityType::class, [
                        'label' => $this->translator->trans('Division of origin'),
                        'placeholder' => '---',
                        'class' => Division::class,
                        'choice_label' => 'division',
                        'query_builder' =>function(DivisionRepository $divisionRepository){
                            return $divisionRepository->createQueryBuilder('d')->orderBy('d.division');
                        }
                    ])
                    ->add('subdivision', EntityType::class, [
                        'label' => $this->translator->trans('Subdivision of origin'),
                        'placeholder' => '---',
                        'class' => Subdivision::class,
                        'choice_label' => 'subdivision',
                        'query_builder' =>function(SubdivisionRepository $subdivisionRepository){
                            return $subdivisionRepository->createQueryBuilder('sb')->orderBy('sb.subdivision');
                        }
                    ])
                    ->add('previousPost', TextType::class, [
                        'label' => $this->translator->trans('Previous post')
                    ])
                    ->add('affectationNote', TextType::class, [
                        'label' => $this->translator->trans('Order/Decision service note')
                    ])
                    ->add('takeFunctiondate', DateType::class, [
                        'label' => $this->translator->trans('Date of assumption/resumption of duty at this year'),
                        'widget' => 'single_text'
                    ])
                    ->add('firstDateFunction', DateType::class, [
                        'label' => $this->translator->trans('Date of first assumption of duty in public administration'),
                        'widget' => 'single_text'
                    ])
                    ->add('firstDateActualFunction', DateType::class, [
                        'label' => $this->translator->trans('Date of first assumption of duty in actual post'),
                        'widget' => 'single_text'
                    ])
                    ->add('speciality', EntityType::class, [
                        'label' => $this->translator->trans('Speciality'),
                        'placeholder' => '---',
                        'class' => Subject::class,
                        'choice_label' => 'subject',
                        'query_builder' => function(SubjectRepository $subjectRepository){
                            $mySession = $this->request->getSession();
                            $schoolYear = $mySession->get('schoolYear');
                            $subSystem = $mySession->get('subSystem');
                            return $subjectRepository->findForForm($schoolYear, $subSystem);
                        }
                    ])
                    ->add('teachingSubject', EntityType::class, [
                        'label' => $this->translator->trans('Subject effectively taught'),
                        'placeholder' => '---',
                        'class' => Subject::class,
                        'choice_label' => 'subject',
                        'query_builder' => function(SubjectRepository $subjectRepository){
                            $mySession = $this->request->getSession();
                            $schoolYear = $mySession->get('schoolYear');
                            $subSystem = $mySession->get('subSystem');
                            return $subjectRepository->findForForm($schoolYear, $subSystem);
                        }
                    ])
                ;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Teacher::class,
        ]);
    }
}
