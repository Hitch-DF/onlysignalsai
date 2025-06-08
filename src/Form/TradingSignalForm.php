<?php

namespace App\Form;

use App\Entity\TradingSignal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TradingSignalForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('symbol', TextType::class, [
                'label' => 'Asset',
            ])
            ->add('signalType', ChoiceType::class, [
                'label' => 'Signal Type',
                'choices' => array_combine(TradingSignal::ALLOWED_TYPES, TradingSignal::ALLOWED_TYPES),
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Choose...',
            ])
            ->add('category', TextType::class, [
                'label' => 'Category',
            ])
            ->add('timeFrame', TextType::class, [
                'label' => 'Time Frame',
                'required' => false,
            ])
            ->add('entryPrice', NumberType::class, [
                'label' => 'Entry Price',
                'required' => false,
            ])
            ->add('exitPrice', NumberType::class, [
                'label' => 'Exit Price',
                'required' => false,
            ])
            ->add('pips', NumberType::class, [
                'label' => 'Pips',
                'required' => false,
                'scale' => 2,
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
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Created At',
                'widget' => 'single_text',
            ])
            ->add('closedAt', DateTimeType::class, [
                'label' => 'Closed At',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('screenshot', FileType::class, [
                'label' => 'Screenshot (image)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradingSignal::class,
        ]);
    }
}
