<?php

namespace App\Form;

use App\Entity\RegistrationHistory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class RegistrationHistoryType extends AbstractType
{
    
    protected $session; 
    protected $translator; 

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('schoolFees', IntegerType::class, [
                'label' => $this->translator->trans("Frais exigibles"),
            ])
            ->add('apeeFees', IntegerType::class, [
                'label' => $this->translator->trans("Frais APEE"),
            ])
            ->add('computerFees', IntegerType::class, [
                'label' => $this->translator->trans("Frais informatique"),
            ])
            ->add('medicalBookletFees', IntegerType::class, [
                'label' => $this->translator->trans("Livret médical"),
            ])
            ->add('cleanSchoolFees', IntegerType::class, [
                'label' => $this->translator->trans("Clean school"),
            ])
            ->add('photoFees', IntegerType::class, [
                'label' => $this->translator->trans("Photo"),
            ])
            ->add('stampFees', IntegerType::class, [
                'label' => $this->translator->trans("Timbre"),
            ])
            ->add('examFees', IntegerType::class, [
                'label' => $this->translator->trans("Frais d'examen"),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegistrationHistory::class,
        ]);
    }
}
