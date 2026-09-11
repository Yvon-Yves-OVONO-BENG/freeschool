<?php

namespace App\Form;

use App\Entity\Subject;
use App\Entity\Category;
use App\Entity\Department;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Repository\CategoryRepository;
use App\Repository\DepartmentRepository;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectType extends AbstractType
{
    public function __construct(protected RequestStack $request, protected TranslatorInterface $translator, 
    protected SchoolRepository $schoolRepository)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var School $school
         */
        $school = $options['school'];
        // dd($school);
        $school = $this->schoolRepository->find($school->getId());
        
        $education = $school->getEducation();
        // dd($education);

        $builder
            ->add('subject', TextType::class, [
                'label' => $this->translator->trans('Subject'),
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('category', EntityType::class, [
                'label' => $this->translator->trans('Group'),
                'placeholder' => '---',
                'class' => Category::class,
                // 'query_builder' => function(CategoryRepository $categoryRepository)
                // {
                //     return $categoryRepository->createQueryBuilder('c')->orderBy('c.category', 'DESC');
                // },
                'query_builder' => function(EntityRepository $er) use ($education)
                {
                    return $er->createQueryBuilder('c')
                    ->where('c.education = :education')
                    ->setParameter('education', $education);
                },
                'choice_label' => 'category'
            ])
            ->add('department', EntityType::class, [
                'label' => $this->translator->trans('Department'),
                'placeholder' => '---',
                'class' => Department::class, 
                'choice_label' => 'department',
                'query_builder' => function(DepartmentRepository $departmentRepository){
                    $mySession = $this->request->getSession();
                    $schoolYear = $mySession->get('schoolYear');
                    $subSystem = $mySession->get('subSystem');
                    return $departmentRepository->findForForm($schoolYear, $subSystem);
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subject::class,
            'school' => null,
        ]);
    }
}
