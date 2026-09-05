<?php declare(strict_types=1);

namespace App\Entity;

use App\Enum\ImageExifLabel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity()]
#[ORM\Table(name: 'image_exif')]
#[ORM\UniqueConstraint(name: 'uniq_image_language', columns: ['image_id', 'label'])]
#[ORM\HasLifecycleCallbacks]
class ImageExif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Image::class, inversedBy: 'exif')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    private ?Image $image = null;

    #[ORM\Column(type: Types::ENUM, enumType: ImageExifLabel::class)]
    #[Groups(['blog_post:read'])]
    private ImageExifLabel $label = ImageExifLabel::UnknownLabel;

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private string $value;

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

    public function setImage(?Image $image): ImageExif
    {
        $this->image = $image;

        return $this;
    }

    public function getLabel(): ImageExifLabel
    {
        return $this->label;
    }

    public function setLabel(ImageExifLabel $label): ImageExif
    {
        $this->label = $label;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): ImageExif
    {
        $this->value = $value;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): ImageExif
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): ImageExif
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
