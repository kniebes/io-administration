<?php declare(strict_types=1);

namespace App\Service\Tag;

use App\Entity\Tag;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class TagResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
    ) {
    }

    public function resolve(string $value): ?Tag
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
