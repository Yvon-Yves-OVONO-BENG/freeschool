<?php

namespace App\Form;

use App\Entity\Fees;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeesType extends AbstractType
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('schoolFees1', IntegerType::class, [
                'label' => $this->translator->trans('School fees cycle 1')
            ])
            ->add('apeeFees1', IntegerType::class, [
                'label' => $this->translator->trans('PTA cycle 1')
            ])
            ->add('computerFees1', IntegerType::class, [
                'label' => $this->translator->trans('IT Costs cycle 1')
            ])
            ->add('schoolFees2', IntegerType::class, [
                'label' => $this->translator->trans('School fees 2')
            ])
            ->add('apeeFees2', IntegerType::class, [
                'label' => $this->translator->trans('PTA cycle 2')
            ])
            ->add('computerFees2', IntegerType::class, [
                'label' => $this->translator->trans('IT Costs cycle 2')
            ])
            ->add('medicalBookletFees', IntegerType::class, [
                'label' => $this->translator->trans('Médical booklet')
            ])
            ->add('cleanSchoolFees', IntegerType::class, [
                'label' => $this->translator->trans('Clean school')
            ])
            ->add('photoFees', IntegerType::class, [
                'label' => $this->translator->trans('Photo')
            ])
            ->add('stampFees3eme', IntegerType::class, [
                'label' => $this->translator->trans('Stamp 3ème')
            ])
            ->add('stampFees1ere', IntegerType::class, [
                'label' => $this->translator->trans('Stamp 1ère')
            ])
            ->add('stampFeesTle', IntegerType::class, [
                'label' => $this->translator->trans('Stamp Tle')
            ])
            ->add('examFees3eme', IntegerType::class, [
                'label' => $this->translator->trans("Exam fees 3ème")
            ])
            ->add('examFees1ere', IntegerType::class, [
                'label' => $this->translator->trans("Exam fees 1ère")
            ])
            ->add('examFeesTle', IntegerType::class, [
                'label' => $this->translator->trans("Exam fees Tle")
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Fees::class,
        ]);
    }
}
