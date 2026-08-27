<?php

declare(strict_types=1);

namespace App\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Kniebes\IoCore\Entity\Blog;
use Kniebes\IoCore\Entity\BlogPost;
use Kniebes\IoCore\Entity\BlogPostType;
use Kniebes\IoCore\Entity\Category;
use Kniebes\IoCore\Entity\Image;
use Kniebes\IoCore\Entity\Tag;
use Kniebes\IoCore\Enum\BlogPostStatus;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class BlogPostFormType extends AbstractType
{
    private const int IMAGE_CHOICE_LIMIT = 50;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $blogPost = $options['data'] ?? null;
        $customFieldItems = [];
        $existingImages = [];

        if ($blogPost instanceof BlogPost) {
            foreach ($blogPost->getCustomFields() as $key => $value) {
                $customFieldItems[] = ['key' => (string) $key, 'value' => $value];
            }

            foreach ($blogPost->getImages() as $image) {
                $existingImages[] = $image;
            }
        }

        // Convenience list for the picker <select>, capped for usability with many images
        // (the live system has 2000+).
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
                'empty_data' => '',
            ])
            ->add('blog', EntityType::class, [
                'class' => Blog::class,
                'choice_label' => 'name',
                'label' => 'blogpost.form.blog',
                'required' => false,
                'placeholder' => 'blogpost.form.placeholder',
                'attr' => ['class' => 'default-input']
            ])
            ->add('blogPostType', EntityType::class, [
                'class' => BlogPostType::class,
                'choice_label' => 'name',
                'label' => 'blogpost.form.blog_post_type',
                'required' => false,
                'placeholder' => 'blogpost.form.placeholder',
                'attr' => ['class' => 'default-input']
            ])
            ->add('status', EnumType::class, [
                'class' => BlogPostStatus::class,
                'label' => 'blogpost.form.status',
                'empty_data' => BlogPostStatus::Draft->value,
                'attr' => ['class' => 'default-input']
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
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'term',
                'label' => 'blogpost.form.tags',
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'data-controller' => 'tag-autocomplete',
                    'data-tag-autocomplete-search-url-value' => $this->urlGenerator->generate('ux_entity_autocomplete', ['alias' => 'tag']),
                    'data-tag-autocomplete-create-url-value' => $this->urlGenerator->generate('tag_create'),
                    'data-tag-autocomplete-csrf-token-value' => $this->csrfTokenManager->getToken('tag-create')->getValue(),
                ],
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
                // Rows carry only the image ID, resolved to an actual Image entity in the
                // SUBMIT listener below (same approach as "customFields"). An EntityType per
                // row would need its own choice list to validate against, and with 2000+
                // images that either means loading everything or repeating the same capped
                // "recent" query once per row for no benefit, since the value is looked up
                // directly here anyway.
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

        $builder->get('images')->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            foreach ($event->getForm() as $name => $imageRow) {
                if (($imageRow->getData() ?? '') === '') {
                    $event->getForm()->remove($name);
                }
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
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

            // Rebuilding via setImages() (rather than relying on the default add/remove
            // diffing) is what actually persists reordering: comparing old vs. new
            // collection only detects additions/removals, not a pure order change.
            $blogPost->setImages(new ArrayCollection($images));
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $blogPost = $event->getData();

            if (!$blogPost instanceof BlogPost) {
                return;
            }

            $customFields = [];

            foreach ($event->getForm()->get('customFields')->getData() ?? [] as $item) {
                if (($item['key'] ?? '') === '') {
                    continue;
                }

                $customFields[$item['key']] = $item['value'] ?? '';
            }

            $blogPost->setCustomFields($customFields);
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Children views only exist once the whole tree has been built, so this must run
        // in finishView() (after children), not buildView() (before children).
        $view->children['images']->vars['recentImages'] = $form->getConfig()->getAttribute('recentImages');

        // Rows only carry a raw ID (see buildForm()), so the display URL has to be looked
        // up separately here. This is done per row by its own current value, not by array
        // position: after a submit (e.g. when this view is reused to render the turbo-stream
        // response right after saving), row count and order can differ from the
        // pre-submission snapshot taken in buildForm(), which would silently pair a row with
        // the wrong image if matched by index instead.
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}
