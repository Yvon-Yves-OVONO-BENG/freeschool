<?php

namespace App\Form;

use App\Entity\Education;
use App\Entity\School;
use App\Entity\Teacher;
use App\Repository\TeacherRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;

class SchoolType extends AbstractType
{
    public function __construct(protected RequestStack $request)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('frenchName', TextType::class, [
                'label' => 'Nom établissement'
            ])
            ->add('englishName', TextType::class, [
                'label' => 'School name'
            ])
            ->add('frenchMotto', TextType::class, [
                'label' => 'Dévise'
            ])
            ->add('englishMotto', TextType::class, [
                'label' => 'Motto'
            ])
            ->add('pobox', TextType::class, [
                'label' => 'B.P'
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone'
            ])
            ->add('place', TextType::class, [
                'label' => 'Lieu'
            ])
            ->add('logoFile', VichImageType::class, [
                'label' => 'Logo',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->add('filigreeFile', VichImageType::class, [
                'label' => 'Logo en filigrane',
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'E-mail'
            ])
            ->add('frenchCountry', TextType::class, [
                'label' => 'Pays'
            ])
            ->add('englishCountry', TextType::class, [
                'label' => 'Country'
            ])
            ->add('frenchMinister', TextType::class, [
                'label' => 'Ministère'
            ])
            ->add('englishMinister', TextType::class, [
                'label' => 'Minister'
            ])
            ->add('frenchRegion', TextType::class, [
                'label' => 'Région'
            ])
            ->add('englishRegion', TextType::class, [
                'label' => 'Region'
            ])
            ->add('frenchDivision', TextType::class, [
                'label' => 'Département'
            ])
            ->add('englishDivision', TextType::class, [
                'label' => 'Division'
            ])
            ->add('frenchSubDivision', TextType::class, [
                'label' => 'Arrondissement'
            ])
            ->add('englishSubDivision', TextType::class, [
                'label' => 'Subdivision'
            ])
            ->add('frenchCountryMotto', TextType::class, [
                'label' => 'Dévise du pays'
            ])
            ->add('englishCountryMotto', TextType::class, [
                'label' => 'Country motto'
            ])
            ->add('serviceNote', TextType::class, [
                'label' => 'Service note'
            ])
            ->add('headmaster', EntityType::class, [
                'label' => "Chef d'établissement",
                'class' => Teacher::class,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    return $teacherRepository->findHeadmasterForForm( $schoolYear);
                },
                'choice_label' => 'fullName'
            ])
            // ->add('education', EntityType::class, [
            //     'label' => "Enseignement générale ou technique ?",
            //     'class' => Education::class,
            //     'choice_label' => 'education',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => School::class,
        ]);
    }
}
