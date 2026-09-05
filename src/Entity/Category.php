<?php declare(strict_types=1);

namespace App\Entity;

use App\Enum\CategoryType;
use App\Enum\TagType;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity()]
#[ORM\Table(name: 'category')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    /**
     * @var Collection<int, BlogPost>
     */
    #[ORM\ManyToMany(targetEntity: BlogPost::class, mappedBy: 'categories')]
    private Collection $blogPosts;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\ManyToMany(targetEntity: Image::class, mappedBy: 'categories')]
    private Collection $images;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $term = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: TYPES::ENUM, enumType: CategoryType::class)]
    #[Groups(['blog_post:read'])]
    private CategoryType $type = CategoryType::BlogPost;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $updated = null;

    public function __construct()
    {
        $this->blogPosts = new ArrayCollection();
        $this->images = new ArrayCollection();
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

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(?string $term): Category
    {
        $this->term = $term;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): Category
    {
        $this->slug = $slug;

        return $this;
    }

    public function getType(): CategoryType
    {
        return $this->type;
    }

    public function setType(CategoryType $type): Category
    {
        $this->type = $type;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(?DateTimeImmutable $created): Category
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): Category
    {
        $this->updated = $updated;

        return $this;
    }
}
