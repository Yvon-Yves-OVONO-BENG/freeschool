<?php

namespace App\Form;

use App\Entity\Decision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DecisionType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator)
    {}
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('decision', TextType::class, [
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
            'data_class' => Decision::class,
        ]);
    }
}
