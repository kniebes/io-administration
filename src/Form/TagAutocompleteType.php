<?php

declare(strict_types=1);

namespace App\Form;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Kniebes\IoCore\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField(alias: 'tag')]
final class TagAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $submitted = $event->getData();

            if (!is_array($submitted)) {
                return;
            }

            $tagIds = [];

            foreach ($submitted as $value) {
                $tagId = $this->resolveTagId((string) $value);

                if ($tagId !== null && !in_array($tagId, $tagIds, true)) {
                    $tagIds[] = $tagId;
                }
            }

            $event->setData($tagIds);
        }, priority: 10);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Tag::class,
            'choice_label' => 'term',
            'searchable_fields' => ['term'],
            'multiple' => true,
            'security' => 'ROLE_USER',
            'tom_select_options' => ['create' => true],
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }

    private function resolveTagId(string $value): ?string
    {
        if (ctype_digit($value) && $this->entityManager->find(Tag::class, (int) $value) instanceof Tag) {
            return $value;
        }

        $term = trim($value);

        if ($term === '') {
            return null;
        }

        $slug = $this->slugger->slug($term)->lower()->toString();
        $tagRepository = $this->entityManager->getRepository(Tag::class);
        $tag = $tagRepository->findOneBy(['slug' => $slug]);

        if (!$tag instanceof Tag) {
            $now = new DateTimeImmutable();
            $tag = (new Tag())
                ->setTerm($term)
                ->setSlug($slug)
                ->setCreated($now)
                ->setUpdated($now);

            $this->entityManager->persist($tag);
            $this->entityManager->flush();
        }

        return (string) $tag->getId();
    }
}
