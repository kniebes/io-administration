<?php declare(strict_types=1);

namespace App\Service\Tag;

use App\Entity\Tag;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class TagResolver
{
    public const string NEW_TAG_PREFIX = 'new:';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
    ) {
    }

    public function resolve(string $value): ?Tag
    {
        if (str_starts_with($value, self::NEW_TAG_PREFIX)) {
            $value = substr($value, strlen(self::NEW_TAG_PREFIX));
        } elseif (ctype_digit($value)) {
            return $this->entityManager->find(Tag::class, (int) $value);
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

        foreach ($this->entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $scheduledEntity) {
            if ($scheduledEntity instanceof Tag && $scheduledEntity->getSlug() === $slug) {
                return $scheduledEntity;
            }
        }

        $now = new DateTimeImmutable();
        $tag = (new Tag())
            ->setTerm($term)
            ->setSlug($slug)
            ->setCreated($now)
            ->setUpdated($now);

        $this->entityManager->persist($tag);

        return $tag;
    }
}
