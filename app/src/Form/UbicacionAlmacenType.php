<?php

namespace App\Form;

use App\Entity\UbicacionAlmacen;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UbicacionAlmacenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pasillo', TextType::class, [
                'label' => 'Pasillo',
                'attr'  => ['maxlength' => 50],
                'constraints' => [
                    new NotBlank(['message' => 'El pasillo es obligatorio.']),
                    new Length(['max' => 50]),
                ],
            ])
            ->add('estanteria', TextType::class, [
                'label' => 'Estantería',
                'attr'  => ['maxlength' => 50],
                'constraints' => [
                    new NotBlank(['message' => 'La estantería es obligatoria.']),
                    new Length(['max' => 50]),
                ],
            ])
            ->add('nivel', TextType::class, [
                'label' => 'Nivel',
                'attr'  => ['maxlength' => 50],
                'constraints' => [
                    new NotBlank(['message' => 'El nivel es obligatorio.']),
                    new Length(['max' => 50]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UbicacionAlmacen::class]);
    }
}
