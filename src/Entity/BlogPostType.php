<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogPostTypeRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BlogPostTypeRepository::class)]
#[ORM\Table(name: 'blog_post_type')]
#[ORM\HasLifecycleCallbacks]
class BlogPostType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE,nullable: true)]
    private ?DateTimeImmutable $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): BlogPostType
    {
        $this->name = $name;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(?DateTimeImmutable $created): BlogPostType
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): BlogPostType
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
