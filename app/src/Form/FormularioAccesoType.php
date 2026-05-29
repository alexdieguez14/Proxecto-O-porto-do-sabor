<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormularioAccesoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'formulario.etiqueta.correo',
                'attr'  => [
                    'autocomplete' => 'email',
                    'autofocus'    => true,
                    'placeholder'  => 'oportodosabor@gmail.com',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'formulario.etiqueta.contrasena',
                'attr'  => ['autocomplete' => 'current-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // El bundle de seguridad gestiona su propio CSRF (_csrf_token / authenticate)
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
