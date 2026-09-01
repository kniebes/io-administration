<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogPostSlugArchiveRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BlogPostSlugArchiveRepository::class)]
#[ORM\Table(name: 'blog_post_slug_archive')]
#[ORM\HasLifecycleCallbacks]
class BlogPostSlugArchive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BlogPost::class, inversedBy: 'slugs')]
    #[ORM\JoinColumn(name: 'blog_post_id', referencedColumnName: 'id')]
    #[Groups(['blog_post:read'])]
    private ?BlogPost $blogPost = null;

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['blog_post:read'])]
    private DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?DateTimeImmutable $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlogPost(): ?BlogPost
    {
        return $this->blogPost;
    }

    public function setBlogPost(?BlogPost $blogPost): BlogPostSlugArchive
    {
        $this->blogPost = $blogPost;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): BlogPostSlugArchive
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): BlogPostSlugArchive
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): BlogPostSlugArchive
    {
        $this->updated = $updated;

        return $this;
    }

    #[ORM\PrePersist]
    public function updateCreated(): void
    {
        $this->created = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateUpdated(): void
    {
        $this->updated = new DateTimeImmutable();
    }

}
