<?php

declare(strict_types=1);

namespace App\Form\Filter;

use App\Entity\Blog;
use App\Entity\BlogPostType;
use App\Enum\BlogPostStatus;
use App\Model\Filter\BlogPostFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BlogPostFilterType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchQuery', SearchType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'blogpost.index.filter.search_query',
                    'aria-label' => $this->translator->trans('blogpost.index.filter.aria.search_query'),
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => BlogPostStatus::class,
                'label' => false,
                'required' => false,
                'placeholder' => 'blogpost.index.filter.status',
                'attr' => [
                    'class' => 'default-input',
                    'aria-label' => $this->translator->trans('blogpost.index.filter.aria.status'),
                ],
            ])
            ->add('blog', EntityType::class, [
                'class' => Blog::class,
                'choice_label' => 'name',
                'label' => false,
                'required' => false,
                'placeholder' => 'blogpost.index.filter.blog',
                'attr' => [
                    'class' => 'default-input',
                    'aria-label' => $this->translator->trans('blogpost.index.filter.aria.blog'),
                ],
            ])
            ->add('blogPostType', EntityType::class, [
                'class' => BlogPostType::class,
                'choice_label' => 'name',
                'label' => false,
                'required' => false,
                'placeholder' => 'blogpost.index.filter.blog_post_type',
                'attr' => [
                    'class' => 'default-input',
                    'aria-label' => $this->translator->trans('blogpost.index.filter.aria.blog_post_type'),
                ],
            ])
            ->add('reset', SubmitType::class, [
                'label' => 'blogpost.index.filter.reset',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPostFilter::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'filter';
    }
}
