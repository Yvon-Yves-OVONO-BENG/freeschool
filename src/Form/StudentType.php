<?php

namespace App\Form;

use App\Entity\Sex;
use App\Entity\Student;
use App\Entity\Country;
use App\Entity\Handicap;
use App\Entity\Movement;
use App\Entity\Repeater;
use App\Entity\Classroom;
use App\Entity\EthnicGroup;
use App\Entity\HandicapType;
use App\Entity\ModeAdmission;
use App\Entity\Operateur;
use App\Entity\Responsability;
use App\Repository\SexRepository;
use App\Repository\CountryRepository;
use App\Repository\HandicapRepository;
use App\Repository\MovementRepository;
use App\Repository\RepeaterRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\Form\AbstractType;
use App\Repository\EthnicGroupRepository;
use App\Repository\HandicapTypeRepository;
use App\Repository\ModeAdmissionRepository;
use App\Repository\OperateurRepository;
use App\Repository\ResponsabilityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StudentType extends AbstractType
{
    public function __construct(
        protected RequestStack $request,
        protected SexRepository $sexRepository, 
        protected TranslatorInterface $translator, 
        protected CountryRepository $countryRepository, 
        protected HandicapRepository $handicapRepository, 
        protected MovementRepository $movementRepository, 
        protected RepeaterRepository $repeaterRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EthnicGroupRepository $ethnicGroupRepository, 
        protected HandicapTypeRepository $handicapTypeRepository, 
        protected ResponsabilityRepository $responsabilityRepository, 
        )
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => $this->translator->trans('Full name'),
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('birthday', DateType::class, [
                'label' => $this->translator->trans('Birthday'),
                'widget' => 'single_text'
            ])
            ->add('birthplace', TextType::class, [
                'label' => $this->translator->trans('Birthplace')
            ])
            ->add('registrationNumber', TextType::class, [
                'label' => $this->translator->trans('NIU'),
                'required' => true
            ])
            
            ->add('classroom', EntityType::class, [
                'label' => $this->translator->trans('Classroom'),
                'class' => Classroom::class,
                'query_builder' => function(ClassroomRepository $classroomRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $classroomRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'classroom'
            ])
            ->add('sex', EntityType::class, [
                'label' => $this->translator->trans('Gender'),
                'class' => Sex::class,
                'query_builder' => function(SexRepository $sexRepository){
                    return $sexRepository->createQueryBuilder('s');
                },
                'choice_label' => 'sex'
            ])
            ->add('repeater', EntityType::class, [
                'label' => $this->translator->trans('Repeater'),
                'class' => Repeater::class,
                'query_builder' => function(RepeaterRepository $repeaterRepository){
                    return $repeaterRepository->createQueryBuilder('r');
                },
                'choice_label' => 'repeater'
            ])
            ->add('telephonePere', TextType::class, [
                'label' => $this->translator->trans("Father's phone"),
                'required' => false,
                'attr' => [
                    'pattern' => '[0-9]+'
                ]
            ])
            ->add('telephoneMere', TextType::class, [
                'label' => $this->translator->trans("Mother's phone"),
                'required' => false,
                'attr' => [
                    'pattern' => '[0-9]+'
                ]
            ])
            ->add('responsability', EntityType::class, [
                'label' => $this->translator->trans('Responsability'),
                'required' => false,
                'class' => Responsability::class,
                'choice_label' => 'responsability',
                'query_builder' => function(ResponsabilityRepository $responsabilityRepository){
                    return $responsabilityRepository->createQueryBuilder('r')->orderBy('r.responsability');
                }
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => false,
                'required' => false,
                'allow_delete' => true,
                'delete_label' => $this->translator->trans("Delete"),
                'download_uri' => false,
                'download_label' => $this->translator->trans("Download"),
                'image_uri' => true,
                'attr' => [
                    'class' => 'dropify'
                ]
            ])
            ->add('fatherName', TextType::class, [
                'label' => $this->translator->trans("Father's name"),
                'required' => false
            ])
            ->add('motherName', TextType::class, [
                'label' => $this->translator->trans("Mother's name"),
                'required' => false
            ])
            ->add('ethnicGroup', EntityType::class, [
                'label' => $this->translator->trans('Ethnic group'),
                'class' => EthnicGroup::class,
                'choice_label' => 'ethnicGroup',
                'required' => false,
                'query_builder' => function(EthnicGroupRepository $ethnicGroupRepository){
                    return $ethnicGroupRepository->createQueryBuilder('e')->orderBy('e.ethnicGroup');
                }
            ])
            ->add('movement', EntityType::class, [
                'label' => $this->translator->trans('Movement'),
                'class' => Movement::class,
                'choice_label' => 'movement',
                'placeholder' => '',
                'required' => false,
                'query_builder' => function(MovementRepository $movementRepository){
                    return $movementRepository->createQueryBuilder('m')->orderBy('m.movement');
                }
            ])
            ->add('handicap', EntityType::class, [
                'label' => $this->translator->trans('Handicapped'),
                'class' => Handicap::class,
                'choice_label' => 'handicap',
                'placeholder' => '',
                'required' => false,
                'query_builder' => function(HandicapRepository $handicapRepository){
                    return $handicapRepository->createQueryBuilder('h')->orderBy('h.handicap', 'ASC');
                }
            ])
            ->add('handicapType', EntityType::class, [
                'label' => $this->translator->trans('Handicap type'),
                'class' => HandicapType::class,
                'choice_label' => 'handicapType',
                'placeholder' => '',
                'required' => false,
                'query_builder' => function(HandicapTypeRepository $handicapTypeRepository){
                    return $handicapTypeRepository->createQueryBuilder('ht')->orderBy('ht.handicapType');
                }
            ])
            ->add('country', EntityType::class, [
                'label' => $this->translator->trans('Country of origin'),
                'class' => Country::class,
                'choice_label' => 'country',
                'query_builder' => function(CountryRepository $countryRepository){
                    return $countryRepository->createQueryBuilder('c')->orderBy('c.id');
                }
            ])
            ->add('numeroHcr', TextType::class, [
                'label' => $this->translator->trans('UNHCR number'),
                'required' => false,
                'attr' => [
                    'pattern' => '[0-9]+'
                ]
            ])
            ->add('professionPere', TextType::class, [
                'label' => $this->translator->trans("Father's profession"),
                'required' => false,
            ])
            ->add('professionMere', TextType::class, [
                'label' => $this->translator->trans("Mother's profession"),
                'required' => false,
            ])
            ->add('personneAContacterEnCasUergence', TextType::class, [
                'label' => $this->translator->trans("Person to contact in case of emergency"),
                'required' => false
            ])
            ->add('telephonePersonneEnCasUrgence', TextType::class, [
                'label' => $this->translator->trans('Telephone of the person to contact in case of emergency'),
                'required' => false,
                'attr' => [
                    'pattern' => '[0-9]+'
                ]
            ])
            ->add('tuteur', TextType::class, [
                'label' => $this->translator->trans("Tutor"),
                'required' => false
            ])
            ->add('telephoneTuteur', TextType::class, [
                'label' => $this->translator->trans("Tutor's telephon"),
                'required' => false,
                'attr' => [
                    'pattern' => '[0-9]+'
                ]
            ])
            
            ->add('datePremiereEntreeEtablissementAt', DateType::class, [
                'label' => $this->translator->trans('Date First Entry Establishment'),
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('classeEntree', EntityType::class, [
                'label' => $this->translator->trans('Classroom'),
                'required' => false,
                'class' => Classroom::class,
                'query_builder' => function(ClassroomRepository $classroomRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $classroomRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'classroom'
            ])
            ->add('etablisementFrequenteAnDernier', TextType::class, [
                'label' => $this->translator->trans("Establishment Frequent Last Year"),
                'required' => false
            ])
            ->add('operateur', EntityType::class, [
                'label' => $this->translator->trans('Operator'),
                'required' => false,
                'class' => Operateur::class,
                'query_builder' => function(OperateurRepository $operateurRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    return $operateurRepository->findForForm($schoolYear);
                },
                'choice_label' => 'operateur'
            ])
            ->add('modeAdmission', EntityType::class, [
                'label' => $this->translator->trans('Intake mode'),
                'required' => false,
                'class' => ModeAdmission::class,
                'query_builder' => function(ModeAdmissionRepository $modeAdmissionRepository){
                    return $modeAdmissionRepository->createQueryBuilder('m');
                },
                'choice_label' => 'modeAdmission'
            ])
            ->add('siOuiAllergie', TextType::class, [
                'label' => $this->translator->trans("If so, to what ?"),
                'required' => false
            ])
            ->add('groupeSanguin', TextType::class, [
                'label' => $this->translator->trans("Blood group"),
                'required' => false
            ])
            ->add('rhesus', TextType::class, [
                'label' => $this->translator->trans("Rhesus"),
                'required' => false
            ])
            ->add('autresMaladies', TextType::class, [
                'label' => $this->translator->trans("Other diseases"),
                'required' => false
            ])
            ->add('autreClub', TextType::class, [
                'label' => $this->translator->trans("Other club"),
                'required' => false
            ])
            ->add('classeFrereSoeur', EntityType::class, [
                'label' => $this->translator->trans('Class of brother or sister'),
                'required' => false,
                'class' => Classroom::class,
                'placeholder' => $this->translator->trans('Choose classroom'),
                'query_builder' => function(ClassroomRepository $classroomRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $classroomRepository->findForForm($schoolYear, $subSystem);
                },
                'choice_label' => 'classroom'
            ])
            ->add('autreConnaisanceEtablissement', TextType::class, [
                'label' => $this->translator->trans("Other acquaintance in the establishment"),
                'required' => false
            ])
            ->add('nomPersonneEtablissement', TextType::class, [
                'label' => $this->translator->trans("name of Person in Establishment"),
                'required' => false
            ])
            ->add('telephonePersonneEtablissement', TextType::class, [
                'label' => $this->translator->trans("Phone number of the Person in the Establishment"),
                'required' => false
            ])
            ->add('numeroWhatsapp', TextType::class, [
                'label' => $this->translator->trans("Whatsapp number"),
                'required' => false
            ])
            // ->add('club', ChoiceType::class, [
            //     'label' => $this->translator->trans("Clubs"),
            //     'choices'   => [
            //         $this->translator->trans("MULTICULTURAL CLUB")   => 'multiculturel',
            //         $this->translator->trans("SCIENTIFIC CLUB")   => 'scientifique',
            //         $this->translator->trans("NEWSPAPER CLUB")   => 'journal',
            //         $this->translator->trans("ENVIRONMENT CLUB")   => 'environnement',
            //         $this->translator->trans("HEALTH CLUB AND RED CROSS")   => 'sante',
            //         $this->translator->trans("RHETORIC CLUB")   => 'rethorique',
                    
            //     ],
            //     'multiple'  => true,
            //     'mapped'  => false,
            //     'expanded' => true, 
            //     'attr' => [
            //         'class' => 'selectgroup selectgroup-pills selectgroup-button'
            //     ]  

            // ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
        ]);
    }
}
