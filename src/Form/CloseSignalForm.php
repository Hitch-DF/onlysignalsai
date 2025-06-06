<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\TradingSignal;

class CloseSignalForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('exitPrice', NumberType::class, [
                'label' => 'Prix Sortie',
                'required' => true,
            ])
            ->add('pips', NumberType::class, [
                'label' => 'Pips',
                'required' => true,
            ])
            ->add('result', ChoiceType::class, [
                'label' => 'Résultat',
                'choices' => [
                    'Win' => 'Win',
                    'Loss' => 'Loss',
                    'Break Even' => 'Break Even',
                ],
                'required' => true,
            ])
            ->add('closedAt', DateTimeType::class, [
                'label' => 'Date Clôture',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('screenshot', FileType::class, [
                'label' => 'Screenshot',
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradingSignal::class,
        ]);
    }
}
