<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Link;
use App\Entity\Tag;
use App\Service\Tag\TagResolver;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

final class LinkFormType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagResolver $tagResolver,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $link = $options['data'] ?? null;
        $customFieldItems = [];
        $jsonCustomFieldKeys = [];
        $existingTagIds = [];

        if ($link instanceof Link) {
            foreach ($link->getCustomFields() as $key => $value) {
                if (is_array($value)) {
                    $jsonCustomFieldKeys[] = (string) $key;
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $customFieldItems[] = ['key' => (string) $key, 'value' => $value];
            }

            foreach ($link->getTags() as $tag) {
                $existingTagIds[] = (string) $tag->getId();
            }
        }

        $builder->setAttribute('jsonCustomFieldKeys', $jsonCustomFieldKeys);

        $builder
            ->add('title', TextType::class, [
                'label' => 'link.form.title',
                'required' => false,
                'constraints' => [
                    new Length(max: 255),
                ],
            ])
            ->add('url', UrlType::class, [
                'label' => 'link.form.url',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                    new Url(),
                    new Length(max: 255),
                ],
            ])
            ->add('alternateUrl', UrlType::class, [
                'label' => 'link.form.alternateUrl',
                'required' => false,
                'constraints' => [
                    new Url(),
                    new Length(max: 255),
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
            ->add('tags', CollectionType::class, [
                'label' => 'link.form.tags',
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

        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedTags(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedCustomFields(...));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
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
            'data_class' => Link::class,
            'constraints' => [
                new UniqueEntity(fields: ['url'], errorPath: 'url'),
            ],
        ]);
    }

    private function applySubmittedTags(FormEvent $event): void
    {
        $link = $event->getData();

        if (!$link instanceof Link) {
            return;
        }

        $tags = [];

        foreach ($event->getForm()->get('tags') as $tagRow) {
            $tag = $this->tagResolver->resolve((string) ($tagRow->getData() ?? ''));

            if ($tag instanceof Tag && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        foreach ($link->getTags()->toArray() as $existingTag) {
            if (!in_array($existingTag, $tags, true)) {
                $link->removeTag($existingTag);
            }
        }

        foreach ($tags as $tag) {
            $link->addTag($tag);
        }
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
