<?php

namespace App\Form;

use App\Entity\Articulo;
use App\Entity\Categoria;
use App\Entity\Proveedor;
use App\Entity\UbicacionAlmacen;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ArticuloType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'formulario.etiqueta.titulo_es',
                'attr'  => ['maxlength' => 200],
                'constraints' => [
                    new NotBlank(['message' => 'validation.title.required']),
                    new Length(['max' => 200]),
                ],
            ])
            ->add('tituloEn', TextType::class, [
                'label'    => 'formulario.etiqueta.titulo_en',
                'required' => false,
                'attr'     => ['maxlength' => 200],
                'constraints' => [new Length(['max' => 200])],
            ])
            ->add('tituloGl', TextType::class, [
                'label'    => 'formulario.etiqueta.titulo_gl',
                'required' => false,
                'attr'     => ['maxlength' => 200],
                'constraints' => [new Length(['max' => 200])],
            ])
            ->add('descripcion', TextareaType::class, [
                'label'    => 'formulario.etiqueta.descripcion_es',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
            ->add('descripcionEn', TextareaType::class, [
                'label'    => 'formulario.etiqueta.descripcion_en',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
            ->add('descripcionGl', TextareaType::class, [
                'label'    => 'formulario.etiqueta.descripcion_gl',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
            ->add('imagenFile', FileType::class, [
                'label' => 'formulario.etiqueta.imagen',
                'mapped' => false,
                'required' => false,
                'help' => 'formulario.imagen.ayuda',
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'validation.image.mime',
                    ]),
                ],
            ])
            ->add('precioSinIva', NumberType::class, [
                'label' => 'formulario.etiqueta.precio',
                'scale' => 2,
                'attr' => ['step' => '0.01'],
                'constraints' => [
                    new NotBlank(['message' => 'validation.price.required']),
                    new Positive(['message' => 'validation.price.positive']),
                ],
            ])
            ->add('iva', ChoiceType::class, [
                'label' => 'formulario.etiqueta.iva',
                'choices' => [
                    '0%' => '0.00',
                    '4%' => '4.00',
                    '10%' => '10.00',
                    '21%' => '21.00',
                ],
            ])
            ->add('peso', NumberType::class, [
                'label' => 'formulario.etiqueta.peso',
                'scale' => 3,
                'attr'  => ['step' => '0.001'],
                'constraints' => [
                    new NotBlank(['message' => 'validation.weight.required']),
                    new Positive(['message' => 'validation.weight.positive']),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'formulario.etiqueta.stock',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual(['value' => 0, 'message' => 'validation.stock.nonnegative']),
                ],
            ])
            ->add('categoria', EntityType::class, [
                'class'        => Categoria::class,
                'choice_label' => 'nombre',
                'label'        => 'formulario.etiqueta.categoria',
                'placeholder'  => 'formulario.categoria.placeholder',
                'required'     => false,
            ])
            ->add('proveedor', EntityType::class, [
                'class'        => Proveedor::class,
                'choice_label' => 'nombre',
                'label'        => 'admin.proveedores.breadcrumb',
                'placeholder'  => 'formulario.proveedor.placeholder',
                'required'     => false,
            ])
            ->add('ubicacion', EntityType::class, [
                'class'        => UbicacionAlmacen::class,
                'label'        => 'logistica.inventario.location',
                'placeholder'  => 'formulario.ubicacion.placeholder',
                'required'     => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Articulo::class]);
    }
}
