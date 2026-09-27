<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Enum\BlogPostStatus;
use App\Enum\ImageLicense;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ImageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'image.form.title',
                'required' => true,
                'empty_data' => '',
            ])
            ->add('altText', TextareaType::class, [
                'label' => 'image.form.altText',
                'required' => false,
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
                    'data-publish-guard-target' => 'license',
                    'data-action' => 'change->license-guard#update',
                ]
            ])
        ;
    }
}
