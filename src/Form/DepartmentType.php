<?php

namespace App\Form;

use App\Entity\Teacher;
use App\Entity\Department;
use App\Repository\TeacherRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DepartmentType extends AbstractType
{

    public function __construct(protected RequestStack $request, protected TranslatorInterface $translator)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('department', TextType::class, [
                'label' => $this->translator->trans('Department'),
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('educationalFacilitator', EntityType::class, [
                'label' => $this->translator->trans('Educational facilitator'),
                'class' => Teacher::class,
                'choice_label' => 'fullName',
                'required' => false,
                'query_builder' => function(TeacherRepository $teacherRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $teacherRepository->findTeacherForForm($schoolYear, $subSystem);
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Department::class,
        ]);
    }
}
