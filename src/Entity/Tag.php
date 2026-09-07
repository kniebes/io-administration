<?php declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\TagType;
use App\Repository\TagRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tag')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    /**
     * @var Collection<int, BlogPost>
     */
    #[ORM\ManyToMany(targetEntity: BlogPost::class, mappedBy: 'tags')]
    private Collection $blogPosts;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\ManyToMany(targetEntity: Image::class, mappedBy: 'tags')]
    private Collection $images;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $term = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::ENUM, enumType: TagType::class)]
    #[Groups(['blog_post:read'])]
    private TagType $type = TagType::General;

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

    public function setTerm(?string $term): Tag
    {
        $this->term = $term;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): Tag
    {
        $this->slug = $slug;

        return $this;
    }

    public function getType(): TagType
    {
        return $this->type;
    }

    public function setType(TagType $type): Tag
    {
        $this->type = $type;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(?DateTimeImmutable $created): Tag
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): Tag
    {
        $this->updated = $updated;

        return $this;
    }


}
