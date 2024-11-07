<?php

namespace App\Form;

use App\Entity\TimeDivision;
use App\Entity\TimeDivisionAction;
use App\Entity\TimeDivisionNumber;
use App\Repository\TimeDivisionNumberRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class TimeDivisionType extends AbstractType
{
    protected $tanslator;

    public function __construct(TranslatorInterface $tanslator)
    {
        $this->tanslator = $tanslator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startTime', TimeType::class, [
                'label' => $this->tanslator->trans('Start time'),
                'widget' => 'single_text',
                'attr' => [
                    'autofocus' => true
                ]
                
            ])
            ->add('endTime', TimeType::class, [
                'label' => $this->tanslator->trans('End time'),
                'widget' => 'single_text'
            ])
            ->add('timeDivisionNumber', EntityType::class, [
                'label' => $this->tanslator->trans('Division N°'),
                'class' => TimeDivisionNumber::class,
                'choice_label' => 'timeDivisionNumber',
                'query_builder' => function(TimeDivisionNumberRepository $timeDivisionNumberRepository){
                    return $timeDivisionNumberRepository->createQueryBuilder('t')
                        ->andWhere('t.isUsed = :isUsed')
                        ->setParameter('isUsed', false)
                        ->orderBy('t.timeDivisionNumber')
                    ;
                }
            ])
            ->add('timeDivisionAction', EntityType::class, [
                'label' => $this->tanslator->trans('Action'),
                'class' => TimeDivisionAction::class,
                'choice_label' => 'timeDivisionAction'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimeDivision::class,
        ]);
    }
}
