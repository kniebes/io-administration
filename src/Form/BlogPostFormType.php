<?php

declare(strict_types=1);

namespace App\Form;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Blog;
use App\Entity\BlogPost;
use App\Entity\BlogPostType;
use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Tag;
use App\Enum\BlogPostStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

final class BlogPostFormType extends AbstractType
{
    private const int IMAGE_CHOICE_LIMIT = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $blogPost = $options['data'] ?? null;
        $customFieldItems = [];
        $existingImages = [];
        $existingTagIds = [];

        if ($blogPost instanceof BlogPost) {
            foreach ($blogPost->getCustomFields() as $key => $value) {
                $customFieldItems[] = ['key' => (string) $key, 'value' => $value];
            }

            foreach ($blogPost->getImages() as $image) {
                $existingImages[] = $image;
            }

            foreach ($blogPost->getTags() as $tag) {
                $existingTagIds[] = (string) $tag->getId();
            }
        }

        $recentImages = $this->entityManager->getRepository(Image::class)
            ->createQueryBuilder('image')
            ->orderBy('image.created', 'DESC')
            ->setMaxResults(self::IMAGE_CHOICE_LIMIT)
            ->getQuery()
            ->getResult();

        $existingImageIds = array_map(static fn (Image $image): string => (string) $image->getId(), $existingImages);

        $builder->setAttribute('recentImages', $recentImages);

        $builder
            ->add('title', TextType::class, [
                'label' => 'blogpost.form.title',
                'required' => true,
                'empty_data' => '',
            ])
            ->add('slug', TextType::class, [
                'label' => 'blogpost.form.slug',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('blog', EntityType::class, [
                'class' => Blog::class,
                'choice_label' => 'name',
                'label' => 'blogpost.form.blog',
                'required' => false,
                'placeholder' => 'blogpost.form.placeholder',
                'attr' => [
                    'class' => 'default-input',
                    'data-publish-guard-target' => 'blog',
                    'data-action' => 'change->publish-guard#update',
                ]
            ])
            ->add('blogPostType', EntityType::class, [
                'class' => BlogPostType::class,
                'choice_label' => 'name',
                'label' => 'blogpost.form.blog_post_type',
                'required' => false,
                'placeholder' => 'blogpost.form.placeholder',
                'attr' => [
                    'class' => 'default-input',
                    'data-publish-guard-target' => 'blogPostType',
                    'data-action' => 'change->publish-guard#update',
                ]
            ])
            ->add('status', EnumType::class, [
                'class' => BlogPostStatus::class,
                'label' => 'blogpost.form.status',
                'empty_data' => BlogPostStatus::Draft->value,
                'attr' => [
                    'class' => 'default-input',
                    'data-publish-guard-target' => 'status',
                    'data-action' => 'change->publish-guard#update',
                ]
            ])
            ->add('isVisibleOnRss', CheckboxType::class, [
                'label' => 'blogpost.form.is_visible_on_rss',
                'required' => false,
            ])
            ->add('isVisibleOnWeb', CheckboxType::class, [
                'label' => 'blogpost.form.is_visible_on_web',
                'required' => false,
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'blogpost.form.summary',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'blogpost.form.content',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'autogrow',
                    'data-controller' => 'autogrow',
                    'data-action' => 'input->autogrow#resize',
                ],
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
            ->add('tags', CollectionType::class, [
                'label' => 'blogpost.form.tags',
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
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'term',
                'label' => 'blogpost.form.categories',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('images', CollectionType::class, [
                'label' => 'blogpost.form.images',
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
                'data' => $existingImageIds,
            ]);

        $builder->get('images')->addEventListener(FormEvents::SUBMIT, $this->removeEmptyImageRows(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedImages(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedTags(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->applySubmittedCustomFields(...));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->fillEmptySlugFromTitle(...));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->children['images']->vars['recentImages'] = $form->getConfig()->getAttribute('recentImages');
        $imageIds = [];

        foreach ($view->children['images']->children as $imageRowView) {
            if (($imageRowView->vars['value'] ?? '') !== '') {
                $imageIds[] = (int) $imageRowView->vars['value'];
            }
        }

        if ($imageIds !== []) {
            $imageUrlsById = [];

            foreach ($this->entityManager->getRepository(Image::class)->findBy(['id' => $imageIds]) as $image) {
                $imageUrlsById[$image->getId()] = $image->getUrl();
            }

            foreach ($view->children['images']->children as $imageRowView) {
                $value = $imageRowView->vars['value'] ?? '';

                if ($value !== '' && isset($imageUrlsById[(int) $value])) {
                    $imageRowView->vars['imageUrl'] = $imageUrlsById[(int) $value];
                }
            }
        }

        $tagIds = [];

        foreach ($view->children['tags']->children as $tagRowView) {
            $value = (string) ($tagRowView->vars['value'] ?? '');

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
            'data_class' => BlogPost::class,
        ]);
    }

    private function removeEmptyImageRows(FormEvent $event): void
    {
        foreach ($event->getForm() as $name => $imageRow) {
            if (($imageRow->getData() ?? '') === '') {
                $event->getForm()->remove($name);
            }
        }
    }

    private function applySubmittedImages(FormEvent $event): void
    {
        $blogPost = $event->getData();

        if (!$blogPost instanceof BlogPost) {
            return;
        }

        $images = [];

        foreach ($event->getForm()->get('images') as $imageRow) {
            $image = $this->entityManager->find(Image::class, (int) $imageRow->getData());

            if ($image instanceof Image) {
                $images[] = $image;
            }
        }

        $blogPost->setImages(new ArrayCollection($images));
    }

    private function applySubmittedTags(FormEvent $event): void
    {
        $blogPost = $event->getData();

        if (!$blogPost instanceof BlogPost) {
            return;
        }

        $tags = [];

        foreach ($event->getForm()->get('tags') as $tagRow) {
            $tag = $this->resolveTag((string) ($tagRow->getData() ?? ''));

            if ($tag instanceof Tag && !in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        $blogPost->setTags(new ArrayCollection($tags));
    }

    private function applySubmittedCustomFields(FormEvent $event): void
    {
        $blogPost = $event->getData();

        if (!$blogPost instanceof BlogPost) {
            return;
        }

        $customFields = [];

        foreach ($event->getForm()->get('customFields')->getData() ?? [] as $item) {
            if (($item['key'] ?? '') === '') {
                continue;
            }
            $key = $item['key'];
            $key = preg_replace('/[^A-Za-z0-9]/', '_', $key);
            $customFields[$key] = $item['value'] ?? '';
        }

        $blogPost->setCustomFields($customFields);
    }

    private function fillEmptySlugFromTitle(FormEvent $event): void
    {
        $data = $event->getData();

        if (!is_array($data)) {
            return;
        }

        if (trim((string) ($data['slug'] ?? '')) !== '') {
            return;
        }

        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            return;
        }

        $data['slug'] = $this->slugger->slug($title)->lower()->toString();
        $event->setData($data);
    }

    private function resolveTag(string $value): ?Tag
    {
        if (ctype_digit($value)) {
            $tag = $this->entityManager->find(Tag::class, (int) $value);

            if ($tag instanceof Tag) {
                return $tag;
            }
        }

        $term = trim($value);

        if ($term === '') {
            return null;
        }

        $slug = $this->slugger->slug($term)->lower()->toString();
        $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['slug' => $slug]);

        if ($tag instanceof Tag) {
            return $tag;
        }

        $now = new DateTimeImmutable();
        $tag = (new Tag())
            ->setTerm($term)
            ->setSlug($slug)
            ->setCreated($now)
            ->setUpdated($now);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }
}
