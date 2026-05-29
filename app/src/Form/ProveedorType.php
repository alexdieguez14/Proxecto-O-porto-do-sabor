<?php

namespace App\Form;

use App\Entity\Proveedor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProveedorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'attr'  => ['maxlength' => 150],
                'constraints' => [
                    new NotBlank(['message' => 'El nombre es obligatorio.']),
                    new Length(['max' => 150]),
                ],
            ])
            ->add('contacto', TextType::class, [
                'label'    => 'Persona de contacto',
                'required' => false,
                'attr'     => ['maxlength' => 150],
                'constraints' => [new Length(['max' => 150])],
            ])
            ->add('telefono', TelType::class, [
                'label'    => 'Teléfono',
                'required' => false,
                'attr'     => ['maxlength' => 20],
                'constraints' => [new Length(['max' => 20])],
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email',
                'required' => false,
                'constraints' => [
                    new Email(['message' => 'El email no es válido.']),
                    new Length(['max' => 180]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Proveedor::class]);
    }
}
