<?php

namespace App\Form;

use App\Entity\Diploma;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

class DiplomaType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('diploma', TextType::class, [
            'label' => $this->translator->trans('Diploma'),
            'attr' => [
                'autofocus' => true
            ]
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Diploma::class,
        ]);
    }
}
