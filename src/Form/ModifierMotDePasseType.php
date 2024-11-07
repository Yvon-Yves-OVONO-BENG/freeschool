<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModifierMotDePasseType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator)
    {}


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('password', PasswordType::class, [
            'label' => $this->translator->trans('Nouveau mot de passe'),
            'attr' => [
                'class' => 'form-control',
            ]
        ])
        // ->add('confirmPassword', PasswordType::class, [
        //     'label' =>  $this->translator->trans('Confirmer le mot de passe')
        // ])
        // 
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
