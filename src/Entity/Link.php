<?php declare(strict_types=1);

namespace App\Entity;

use App\Enum\LinkType;
use App\Enum\TagType;
use App\Repository\LinkRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\Table(name: 'tag')]
#[ORM\Index(name: 'is_featured_tag', columns: ['is_featured_tag'])]
#[ORM\HasLifecycleCallbacks]
class Link
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['blog_post:read'])]
    private string $url = '';

    #[ORM\Column(name: 'alternate_url', length: 255, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $alternateUrl = null;

    #[ORM\Column(type: Types::ENUM, enumType: LinkType::class)]
    #[Groups(['blog_post:read'])]
    private LinkType $type = LinkType::Bookmark;

    /**
     * @var Collection<int, BlogPost>
     */
    #[ORM\ManyToMany(targetEntity: BlogPost::class, mappedBy: 'links')]
    private Collection $blogPosts;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $description = null;

    #[ORM\Column(name: 'description_encoded', type: Types::TEXT, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $descriptionEncoded = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $quote = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'custom_fields', type: Types::JSON, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private array $customFields = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $updated = null;

    public function __construct()
    {
        $this->blogPosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, BlogPost>
     */
    public function getBlogPosts(): Collection
    {
        return $this->blogPosts;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Link
    {
        $this->url = $url;

        return $this;
    }

    public function getAlternateUrl(): ?string
    {
        return $this->alternateUrl;
    }

    public function setAlternateUrl(?string $alternateUrl): Link
    {
        $this->alternateUrl = $alternateUrl;

        return $this;
    }

    public function getType(): LinkType
    {
        return $this->type;
    }

    public function setType(LinkType $type): Link
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Link
    {
        $this->description = $description;

        return $this;
    }

    public function getDescriptionEncoded(): ?string
    {
        return $this->descriptionEncoded;
    }

    public function setDescriptionEncoded(?string $descriptionEncoded): Link
    {
        $this->descriptionEncoded = $descriptionEncoded;

        return $this;
    }

    public function getQuote(): ?string
    {
        return $this->quote;
    }

    public function setQuote(?string $quote): Link
    {
        $this->quote = $quote;

        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): Link
    {
        $this->customFields = $customFields;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(?DateTimeImmutable $created): Link
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): Link
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
