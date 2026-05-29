<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'formulario.etiqueta.nombre',
                'attr'  => ['maxlength' => 100],
                'constraints' => [
                    new NotBlank(['message' => 'validation.name.required']),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('apellidos', TextType::class, [
                'label' => 'formulario.etiqueta.apellidos',
                'attr'  => ['maxlength' => 150],
                'constraints' => [
                    new NotBlank(['message' => 'validation.surname.required']),
                    new Length(['max' => 150]),
                ],
            ])
            ->add('telefono', TelType::class, [
                'label' => 'formulario.etiqueta.telefono',
                'attr'  => ['maxlength' => 20],
                'constraints' => [
                    new NotBlank(['message' => 'validation.phone.required']),
                    new Length(['max' => 20]),
                    new Regex([
                        'pattern' => '/^\+?[\d\s\-()]{6,20}$/',
                        'message' => 'validation.phone.invalid',
                        'match'   => true,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'formulario.etiqueta.correo',
                'attr'  => ['autocomplete' => 'email'],
                'constraints' => [
                    new NotBlank(['message' => 'validation.email.required']),
                    new Email(['message' => 'validation.email.invalid']),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'formulario.etiqueta.contrasena',
                'attr'  => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'validation.password.required']),
                    new Length([
                        'min'        => 8,
                        'minMessage' => 'validation.password.min',
                    ]),
                ],
            ]);

        if ($options['is_admin']) {
            $builder->add('role', ChoiceType::class, [
                'label'   => 'formulario.etiqueta.rol',
                'choices' => [
                    'contabilidad.role.logistica'    => 'ROLE_LOGISTICA',
                    'contabilidad.role.contabilidad' => 'ROLE_CONTABILIDAD',
                ],
                'placeholder' => 'formulario.rol.placeholder',
                'constraints' => [
                    new NotBlank(['message' => 'validation.role.required']),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin' => false,
        ]);
    }
}
