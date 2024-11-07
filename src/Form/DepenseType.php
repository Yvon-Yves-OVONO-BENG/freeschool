<?php

namespace App\Form;

use App\Entity\Depense;
use App\Entity\Rubrique;
use App\Repository\RubriqueRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DepenseType extends AbstractType
{
    public function __construct(protected TranslatorInterface $translator, protected RubriqueRepository $rubriqueRepository)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montant', IntegerType::class, [
                'label' => $this->translator->trans('Montant')
            ])
            ->add('motif', TextareaType::class, [
                'label' => $this->translator->trans('Motif'),
            ])
            ->add('createdAt', DateType::class, [
                'label' => $this->translator->trans('Date de la dépense'),
                'widget' => 'single_text'
            ])
            ->add('rubrique', EntityType::class, [
                'label' => $this->translator->trans('Rubrique'),
                'class' => Rubrique::class,
                'query_builder' => function(RubriqueRepository $rubriqueRepository){
                    return $rubriqueRepository->createQueryBuilder('r');
                },
                'choice_label' => 'rubrique'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Depense::class,
        ]);
    }
}
