<?php

declare(strict_types=1);

namespace App\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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

        if ($blogPost instanceof BlogPost) {
            foreach ($blogPost->getCustomFields() as $key => $value) {
                $customFieldItems[] = ['key' => (string) $key, 'value' => $value];
            }
        }

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
            ])
            ->add('blogPostType', EntityType::class, [
                'class' => BlogPostType::class,
                'choice_label' => 'name',
                'label' => 'blogpost.form.blog_post_type',
                'required' => false,
                'placeholder' => 'blogpost.form.placeholder',
            ])
            ->add('status', EnumType::class, [
                'class' => BlogPostStatus::class,
                'label' => 'blogpost.form.status',
                'empty_data' => BlogPostStatus::Draft->value,
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
                'entry_type' => EntityType::class,
                'entry_options' => [
                    'class' => Image::class,
                    'choice_label' => 'url',
                    'label' => false,
                    'placeholder' => 'blogpost.form.placeholder',
                    'required' => false,
                    // Unrestricted and unlimited: a row holds one already-chosen image, so it must
                    // always resolve correctly regardless of what the picker <select> currently offers.
                    'query_builder' => static fn (EntityRepository $repository): QueryBuilder => $repository
                        ->createQueryBuilder('image')
                        ->orderBy('image.created', 'DESC'),
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ]);

        $builder->get('images')->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            foreach ($event->getForm() as $name => $imageRow) {
                if ($imageRow->getData() === null) {
                    $event->getForm()->remove($name);
                }
            }
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
        // Separate from the "images" field's own (unrestricted) choices: this is only a
        // convenience list for the picker <select>, capped for usability with many images.
        // Children views only exist once the whole tree has been built, so this must run
        // in finishView() (after children), not buildView() (before children).
        $recentImages = $this->entityManager->getRepository(Image::class)
            ->createQueryBuilder('image')
            ->orderBy('image.created', 'DESC')
            ->setMaxResults(self::IMAGE_CHOICE_LIMIT)
            ->getQuery()
            ->getResult();

        $view->children['images']->vars['recentImages'] = $recentImages;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}
