<?php declare(strict_types=1);

namespace App\Entity;

use App\Enum\Language;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'image_translation')]
#[ORM\UniqueConstraint(name: 'uniq_image_language', columns: ['image_id', 'language'])]
#[ORM\HasLifecycleCallbacks]
class ImageTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Image::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id')]
    private ?Image $image = null;

    #[ORM\Column(type: Types::ENUM, enumType: Language::class)]
    #[Groups(['blog_post:read'])]
    private Language $language = Language::English;

    #[ORM\Column(name: 'alt_text', type: Types::TEXT, options: ['default' => ''])]
    #[Groups(['blog_post:read'])]
    private string $altText = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $description = null;

    #[ORM\Column(name: 'description_encoded', type: Types::TEXT, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $descriptionEncoded = null;

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

    public function setImage(?Image $image): ImageTranslation
    {
        $this->image = $image;

        return $this;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): ImageTranslation
    {
        $this->language = $language;

        return $this;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }

    public function setAltText(string $altText): ImageTranslation
    {
        $this->altText = $altText;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): ImageTranslation
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): ImageTranslation
    {
        $this->description = $description;

        return $this;
    }

    public function getDescriptionEncoded(): ?string
    {
        return $this->descriptionEncoded;
    }

    public function setDescriptionEncoded(?string $descriptionEncoded): ImageTranslation
    {
        $this->descriptionEncoded = $descriptionEncoded;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): ImageTranslation
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): ImageTranslation
    {
        $this->updated = $updated;

        return $this;
    }

    #[ORM\PrePersist]
    public function created(): void
    {
        $this->created = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updated(): void
    {
        $this->updated = new DateTimeImmutable();
    }
}
