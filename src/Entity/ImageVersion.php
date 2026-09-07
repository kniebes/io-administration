<?php declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity()]
#[ORM\Table(name: 'image_version')]
#[ORM\UniqueConstraint(name: 'uniq_image_language', columns: ['image_id', 'version_identifier'])]
#[ORM\HasLifecycleCallbacks]
class ImageVersion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Image::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    private ?Image $image = null;

    #[ORM\Column(name: 'version_identifier', length: 255)]
    #[Groups(['blog_post:read'])]
    private string $versionIdentifier;

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private string $url;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['blog_post:read'])]
    private int $width;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['blog_post:read'])]
    private int $height;

    #[ORM\Column(name: 'byte_size', type: Types::INTEGER)]
    #[Groups(['blog_post:read'])]
    private int $byteSize;

    #[ORM\Column(name: 'aspect_ratio', type: Types::FLOAT)]
    #[Groups(['blog_post:read'])]
    private float $aspectRatio = 0.0;

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

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image = null): ImageVersion
    {
        $this->image = $image;

        return $this;
    }

    public function getVersionIdentifier(): string
    {
        return $this->versionIdentifier;
    }

    public function setVersionIdentifier(string $versionIdentifier): ImageVersion
    {
        $this->versionIdentifier = $versionIdentifier;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): ImageVersion
    {
        $this->url = $url;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): ImageVersion
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): ImageVersion
    {
        $this->height = $height;

        return $this;
    }

    public function getByteSize(): int
    {
        return $this->byteSize;
    }

    public function setByteSize(int $byteSize): ImageVersion
    {
        $this->byteSize = $byteSize;

        return $this;
    }

    public function getAspectRatio(): float
    {
        return $this->aspectRatio;
    }

    public function setAspectRatio(float $aspectRatio): ImageVersion
    {
        $this->aspectRatio = $aspectRatio;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): ImageVersion
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): ImageVersion
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
