<?php

namespace App\Form;

use App\Entity\QuestionSecrete;
use App\Entity\ReponseQuestion;
use App\Repository\QuestionSecreteRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReponseQuestionType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator,
        )
    {}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reponse', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans("Answer of the question"),
                ]
            ])
            ->add('questionSecrete', EntityType::class, [
                'class' => QuestionSecrete::class,
                'choice_label' => 'questionSecrete',
                'required' => true,
                'placeholder' => $this->translator->trans('- - -'),
                'attr' => [
                    'class' => 'form-control',
                ],
                'query_builder' => function(QuestionSecreteRepository $questionSecreteRepository){
                    
                    return $questionSecreteRepository->createQueryBuilder('q')->orderBy('q.questionSecrete');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReponseQuestion::class,
        ]);
    }
}
