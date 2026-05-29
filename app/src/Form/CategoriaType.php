<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use App\Entity\Categoria;

class CategoriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'formulario.etiqueta.nombre_es',
                'attr'  => ['maxlength' => 100],
                'constraints' => [
                    new NotBlank(['message' => 'validation.name.required']),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('nombreEn', TextType::class, [
                'label'    => 'formulario.etiqueta.nombre_en',
                'required' => false,
                'attr'     => ['maxlength' => 100],
                'constraints' => [
                    new Length(['max' => 100]),
                ],
            ])
            ->add('nombreGl', TextType::class, [
                'label'    => 'formulario.etiqueta.nombre_gl',
                'required' => false,
                'attr'     => ['maxlength' => 100],
                'constraints' => [
                    new Length(['max' => 100]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Categoria::class]);
    }
}
