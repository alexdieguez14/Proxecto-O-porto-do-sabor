<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base for all filter forms.
 *
 * Child classes implement only filterFields(). Everything else is sealed here
 * so every filter has GET method, no CSRF, and clean URL parameters.
 *
 * Field config keys:
 *   name     string          (required) becomes the query-param key
 *   type     class-string    (optional) Symfony form type; default TextType
 *   col      int<1,12>       (optional) Bootstrap column span at ≥768 px; default 3
 *   options  array           (optional) passed verbatim to FormBuilder::add()
 */
abstract class AbstractFilterType extends AbstractType
{
    /**
     * @return list<array{
     *     name: string,
     *     type?: class-string,
     *     col?: int<1,12>,
     *     options?: array<string, mixed>
     * }>
     */
    abstract protected function filterFields(): array;

    final public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->filterFields() as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? TextType::class;
            $col  = max(1, min(12, (int) ($field['col'] ?? 3)));
            $opts = $field['options'] ?? [];

            $opts['required'] ??= false;
            $opts['label']    ??= false;

            // Store the column hint in row_attr so it does not pollute widget HTML.
            $opts['row_attr'] = array_merge(
                ['data-filter-col' => $col],
                $opts['row_attr'] ?? [],
            );

            $builder->add($name, $type, $opts);
        }
    }

    final public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method'          => 'GET',
            'csrf_protection' => false,
        ]);
    }

    /** Omits the form name prefix → ?busqueda=foo instead of ?filter[busqueda]=foo */
    final public function getBlockPrefix(): string
    {
        return '';
    }
}
