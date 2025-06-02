<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'label' => 'form.registration.username'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.registration.email',
                'required' => true,
            ])
            ->add('phone', TelType::class, [
                'label' => 'form.registration.phone',
                'required' => true,
                'attr'=>['placeholder' => '+336123475678']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'first_options' => [
                    'label' => 'form.profile.password'
                ],
                'second_options' => [
                    'label' => 'form.profile.confirm_password'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.registration.password_required',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'form.registration.password_min_length',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'forms',
        ]);
    }
}
