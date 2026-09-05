<?php declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\ImageLicense;
use App\Repository\ImageRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\Table(name: 'image')]
#[ORM\HasLifecycleCallbacks]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['blog_post:read'])]
    private DateTimeImmutable $date;

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private string $host;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['blog_post:read'])]
    private string $url;

    #[ORM\Column(name: 'mime_type', length: 32)]
    #[Groups(['blog_post:read'])]
    private string $mimeType;

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

    /**
     * @var Collection<int, ImageVersion>
     */
    #[ORM\OneToMany(targetEntity: ImageVersion::class, mappedBy: 'image', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['blog_post:read'])]
    private Collection $versions;

    /**
     * @var Collection<int, ImageTranslation>
     */
    #[ORM\OneToMany(targetEntity: ImageTranslation::class, mappedBy: 'image', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['blog_post:read'])]
    private Collection $translations;

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

    #[ORM\Column(length: 32, enumType: ImageLicense::class)]
    #[Groups(['blog_post:read'])]
    private ImageLicense $license = ImageLicense::AllRightsReserved;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['blog_post:read'])]
    private array $exif = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'custom_fields', type: Types::JSON)]
    #[Groups(['blog_post:read'])]
    private array $customFields = [];

    /**
     * @var Collection<int, BlogPostImageMapping>
     */
    #[ORM\OneToMany(targetEntity: BlogPostImageMapping::class, mappedBy: 'image')]
    private Collection $blogPostImages;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'images')]
    #[ORM\JoinTable(name: 'image_tag')]
    #[Groups(['blog_post:read'])]
    private Collection $tags;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'images')]
    #[ORM\JoinTable(name: 'image_category')]
    #[Groups(['blog_post:read'])]
    private Collection $categories;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['blog_post:read'])]
    private DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?DateTimeImmutable $updated = null;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->blogPostImages = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->exif = [];
        $this->customFields = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): Image
    {
        $this->date = $date;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): Image
    {
        $this->host = $host;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Image
    {
        $this->url = $url;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): Image
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): Image
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): Image
    {
        $this->height = $height;

        return $this;
    }

    public function getByteSize(): int
    {
        return $this->byteSize;
    }

    public function setByteSize(int $byteSize): Image
    {
        $this->byteSize = $byteSize;

        return $this;
    }

    public function getAspectRatio(): float
    {
        return $this->aspectRatio;
    }

    public function setAspectRatio(float $aspectRatio): Image
    {
        $this->aspectRatio = $aspectRatio;

        return $this;
    }

    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function setVersions(Collection $versions): Image
    {
        $this->versions = $versions;

        return $this;
    }

    public function addImageVersion(ImageVersion $imageVersion): Image
    {
        $this->versions->add($imageVersion);
        $imageVersion->setImage($this);

        return $this;
    }

    public function removeImageVersion(ImageVersion $imageVersion): Image
    {
        $this->versions->removeElement($imageVersion);
        $imageVersion->setImage(null);

        return $this;
    }

    public function getImageVersion(string $versionIdentifier): ?ImageVersion
    {
        $imageVersion = $this->versions->filter(function (ImageVersion $version) use ($versionIdentifier) {
            return $version->getVersionIdentifier() === $versionIdentifier;
        });

        return $imageVersion->first();
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function setTranslations(Collection $translations): Image
    {
        $this->translations = $translations;

        return $this;
    }

    public function addTranslation(ImageTranslation $translation): Image
    {
        $this->translations->add($translation);
        $translation->setImage($this);

        return $this;
    }

    public function removeTranslation(ImageTranslation $translation): Image
    {
        $this->translations->removeElement($translation);
        $translation->setImage(null);

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): Image
    {
        $this->tags = $tags;

        return $this;
    }

    public function addTag(Tag $tag): Image
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): Image
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setCategories(Collection $categories): Image
    {
        $this->categories = $categories;

        return $this;
    }

    public function addCategory(Category $category): Image
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): Image
    {
        $this->categories->removeElement($category);

        return $this;
    }
    public function getAltText(): string
    {
        return $this->altText;
    }

    public function setAltText(string $altText): Image
    {
        $this->altText = $altText;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Image
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Image
    {
        $this->description = $description;

        return $this;
    }

    public function getDescriptionEncoded(): ?string
    {
        return $this->descriptionEncoded;
    }

    public function setDescriptionEncoded(?string $descriptionEncoded): Image
    {
        $this->descriptionEncoded = $descriptionEncoded;

        return $this;
    }

    public function getLicense(): ImageLicense
    {
        return $this->license;
    }

    public function setLicense(ImageLicense $license): Image
    {
        $this->license = $license;

        return $this;
    }

    public function getExif(): ?array
    {
        return $this->exif;
    }

    public function setExif(?array $exif): Image
    {
        $this->exif = $exif;

        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): Image
    {
        $this->customFields = $customFields;

        return $this;
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function getBlogPosts(): Collection
    {
        return $this->blogPostImages->map(
            static fn (BlogPostImageMapping $blogPostImage): BlogPost => $blogPostImage->getBlogPost(),
        );
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): Image
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated = null): Image
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
