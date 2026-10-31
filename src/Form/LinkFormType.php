<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Link;
use JsonException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

final class LinkFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $link = $options['data'] ?? null;
        $customFieldItems = [];
        $jsonCustomFieldKeys = [];

        if ($link instanceof Link) {
            foreach ($link->getCustomFields() as $key => $value) {
                if (is_array($value)) {
                    $jsonCustomFieldKeys[] = (string) $key;
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $customFieldItems[] = ['key' => (string) $key, 'value' => $value];
            }
        }

        $builder->setAttribute('jsonCustomFieldKeys', $jsonCustomFieldKeys);

        $builder
            ->add('title', TextType::class, [
                'label' => 'link.form.title',
                'required' => false,
            ])
            ->add('url', UrlType::class, [
                'label' => 'link.form.url',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                    new Url(),
                ],
            ])
            ->add('alternateUrl', UrlType::class, [
                'label' => 'link.form.alternateUrl',
                'required' => false,
                'constraints' => [
                    new Url(),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'link.form.description',
                'required' => false,
            ])
            ->add('quote', TextareaType::class, [
                'label' => 'link.form.quote',
                'required' => false,
            ])
            ->add('customFields', CollectionType::class, [
                'label' => 'blogpost.form.custom_fields.label',
                'entry_type' => CustomFieldItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
                'mapped' => false,
                'data' => $customFieldItems,
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedCustomFields(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
            'constraints' => [
                new UniqueEntity(fields: ['url'], errorPath: 'url'),
            ],
        ]);
    }

    private function applySubmittedCustomFields(FormEvent $event): void
    {
        $link = $event->getData();

        if (!$link instanceof Link) {
            return;
        }

        $jsonCustomFieldKeys = $event->getForm()->getConfig()->getAttribute('jsonCustomFieldKeys');
        $customFields = [];
        $hasInvalidJson = false;

        foreach ($event->getForm()->get('customFields') as $customFieldRow) {
            $item = $customFieldRow->getData();

            if (($item['key'] ?? '') === '') {
                continue;
            }

            $value = $item['value'] ?? '';

            if (in_array($item['key'], $jsonCustomFieldKeys, true)) {
                try {
                    $value = json_decode((string) $value, associative: true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    $customFieldRow->get('value')->addError(new FormError('Invalid JSON in "' . $item['key'] . '": ' . $exception->getMessage()));
                    $hasInvalidJson = true;

                    continue;
                }
            }

            $key = preg_replace('/[^A-Za-z0-9]/', '_', $item['key']);
            $customFields[$key] = $value;
        }

        if ($hasInvalidJson) {
            return;
        }

        $link->setCustomFields($customFields);
    }
}
