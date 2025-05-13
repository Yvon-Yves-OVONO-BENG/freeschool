<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdministratorType extends AbstractType
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => $this->translator->trans('Full name'),
                'attr' => [
                    'autofocus' => true,
                    'class' => "form-control",
                    'placeholder' => $this->translator->trans('Enter full name'),
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Please enter a full name'),
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => $this->translator->trans('Your full name should be at least ').'{{ limit }}'.$this->translator->trans(' characters'),
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                
            
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
