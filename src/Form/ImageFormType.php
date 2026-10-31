<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\ImageTranslation;
use App\Entity\Tag;
use App\Enum\ImageLicense;
use App\Enum\Language;
use App\Service\Tag\TagResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use JsonException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ImageFormType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagResolver $tagResolver,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $image = $options['data'] ?? null;
        $customFieldItems = [];
        $jsonCustomFieldKeys = [];
        $existingTagIds = [];

        if ($image instanceof Image) {
            foreach ($image->getCustomFields() as $key => $value) {
                if (is_array($value)) {
                    $jsonCustomFieldKeys[] = (string) $key;
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $customFieldItems[] = ['key' => (string) $key, 'value' => $value];
            }

            foreach ($image->getTags() as $tag) {
                $existingTagIds[] = (string) $tag->getId();
            }
        }

        $translationLanguages = array_values(array_filter(
            Language::cases(),
            static fn (Language $language): bool => $language !== Language::Deutsch,
        ));

        $builder->setAttribute('translationLanguages', $translationLanguages);
        $builder->setAttribute('jsonCustomFieldKeys', $jsonCustomFieldKeys);

        $builder
            ->add('title', TextType::class, [
                'label' => 'image.form.title',
                'required' => true,
                'empty_data' => '',
            ])
            ->add('altText', TextareaType::class, [
                'label' => 'image.form.altText',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'image.form.description',
                'required' => false,
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'term',
                'label' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('license', EnumType::class, [
                'class' => ImageLicense::class,
                'label' => false,
                'empty_data' => ImageLicense::AllRightsReserved->value,
                'attr' => [
                    'class' => 'default-input',
                ]
            ])
            ->add('tags', CollectionType::class, [
                'label' => 'image.form.tags',
                'entry_type' => HiddenType::class,
                'entry_options' => [
                    'label' => false,
                    'required' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'mapped' => false,
                'data' => $existingTagIds,
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

        foreach ($translationLanguages as $language) {
            $translation = $image instanceof Image ? $image->getTranslation($language) : null;

            $builder->add('translation_' . $language->value, ImageTranslationType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'data' => $translation ?? (new ImageTranslation())->setLanguage($language),
            ]);
        }

        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedTags(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedCustomFields(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedTranslations(...));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['translationLanguages'] = $form->getConfig()->getAttribute('translationLanguages');

        $tagIds = [];

        foreach ($view->children['tags']->children as $tagRowView) {
            $value = (string) ($tagRowView->vars['value'] ?? '');

            if (str_starts_with($value, TagResolver::NEW_TAG_PREFIX)) {
                $tagRowView->vars['tagTerm'] = substr($value, strlen(TagResolver::NEW_TAG_PREFIX));
            }

            if (ctype_digit($value)) {
                $tagIds[] = (int) $value;
            }
        }

        if ($tagIds === []) {
            return;
        }

        $tagTermsById = [];

        foreach ($this->entityManager->getRepository(Tag::class)->findBy(['id' => $tagIds]) as $tag) {
            $tagTermsById[$tag->getId()] = $tag->getTerm();
        }

        foreach ($view->children['tags']->children as $tagRowView) {
            $value = (string) ($tagRowView->vars['value'] ?? '');

            if (ctype_digit($value) && isset($tagTermsById[(int) $value])) {
                $tagRowView->vars['tagTerm'] = $tagTermsById[(int) $value];
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }

    private function applySubmittedTags(FormEvent $event): void
    {
        $image = $event->getData();

        if (!$image instanceof Image) {
            return;
        }

        $tags = [];

        foreach ($event->getForm()->get('tags') as $tagRow) {
            $tag = $this->tagResolver->resolve((string) ($tagRow->getData() ?? ''));

            if ($tag instanceof Tag && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        foreach ($image->getTags()->toArray() as $existingTag) {
            if (!in_array($existingTag, $tags, true)) {
                $image->removeTag($existingTag);
            }
        }

        foreach ($tags as $tag) {
            $image->addTag($tag);
        }
    }

    private function applySubmittedCustomFields(FormEvent $event): void
    {
        $image = $event->getData();

        if (!$image instanceof Image) {
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

        $image->setCustomFields($customFields);
    }

    private function applySubmittedTranslations(FormEvent $event): void
    {
        $image = $event->getData();

        if (!$image instanceof Image) {
            return;
        }

        foreach ($event->getForm()->getConfig()->getAttribute('translationLanguages') as $language) {
            $translation = $event->getForm()->get('translation_' . $language->value)->getData();

            if (!$translation instanceof ImageTranslation) {
                continue;
            }

            $isEmpty = trim((string) $translation->getTitle()) === ''
                && trim($translation->getAltText()) === ''
                && trim((string) $translation->getDescription()) === '';
            $isAttached = $image->getTranslations()->contains($translation);

            if ($isEmpty && $isAttached) {
                $image->removeTranslation($translation);
            }

            if (!$isEmpty && !$isAttached) {
                $image->addTranslation($translation);
            }
        }
    }
}
