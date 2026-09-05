<?php declare(strict_types=1);

namespace App\Form\Filter;

use App\Model\Filter\ImageIndexFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageIndexFilterType extends AbstractType
{
//    public function __construct(private readonly TranslatorInterface $translator)
//    {
//    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reset', SubmitType::class, [
                'label' => 'image.index.filter.reset',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImageIndexFilter::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'filter';
    }
}
