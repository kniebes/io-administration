<?php declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\BlogPostStatus;
use App\Repository\BlogPostRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
#[ORM\Table(name: 'blog_post')]
#[ORM\Index(name: 'post_fulltext', columns: ['searchable_text'], flags: ['fulltext'])]
#[ORM\HasLifecycleCallbacks]
class BlogPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private string $title;

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private string $slug;

    #[ORM\Column(name: 'published_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?DateTimeImmutable $publishedDate = null;

    #[ORM\ManyToOne(targetEntity: Blog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'id')]
    #[Groups(['blog_post:read'])]
    private ?Blog $blog = null;

    #[ORM\ManyToOne(targetEntity: BlogPostType::class)]
    #[ORM\JoinColumn(name: 'blog_post_type_id', referencedColumnName: 'id')]
    #[Groups(['blog_post:read'])]
    private ?BlogPostType $blogPostType = null;

    #[ORM\Column(type: TYPES::ENUM, enumType: BlogPostStatus::class)]
    #[Groups(['blog_post:read'])]
    private BlogPostStatus $status = BlogPostStatus::Draft;

    #[ORM\Column(name: 'is_visible_on_rss', type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['blog_post:read'])]
    private bool $isVisibleOnRss = true;

    #[ORM\Column(name: 'is_visible_on_web', type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['blog_post:read'])]
    private bool $isVisibleOnWeb = true;

    #[ORM\Column(length: 8 )]
    #[Groups(['blog_post:read'])]
    private string $encoder = 'markdown';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private ?string $summary = null;

    #[ORM\Column(name: 'summary_encoded', type: Types::TEXT, nullable: true, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private ?string $summaryEncoded = null;

    #[ORM\Column(type: Types::TEXT, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private string $content;

    #[ORM\Column(name: 'content_encoded', type: Types::TEXT, nullable: true, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private ?string $contentEncoded = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'custom_fields', type: Types::JSON, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private array $customFields = [];

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'blogPosts')]
    #[ORM\JoinTable(name: 'blog_post_tag')]
    #[Groups(['blog_post:read'])]
    private Collection $tags;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'blogPosts')]
    #[ORM\JoinTable(name: 'blog_post_category')]
    #[Groups(['blog_post:read'])]
    private Collection $categories;

    /** @var Collection<int, BlogPostImageMapping> */
    #[ORM\OneToMany(targetEntity: BlogPostImageMapping::class, mappedBy: 'blogPost', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $blogPostImages;

    #[ORM\Column(name: 'searchable_text', type: Types::TEXT, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $searchableText = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['blog_post:read'])]
    private DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?DateTimeImmutable $updated = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->blogPostImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): BlogPost
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): BlogPost
    {
        $this->slug = $slug;

        return $this;
    }

    public function getPublishedDate(): ?DateTimeImmutable
    {
        return $this->publishedDate;
    }

    public function setPublishedDate(?DateTimeImmutable $publishedDate): BlogPost
    {
        $this->publishedDate = $publishedDate;

        return $this;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(?Blog $blog): BlogPost
    {
        $this->blog = $blog;

        return $this;
    }

    public function getBlogPostType(): ?BlogPostType
    {
        return $this->blogPostType;
    }

    public function setBlogPostType(?BlogPostType $blogPostType): BlogPost
    {
        $this->blogPostType = $blogPostType;

        return $this;
    }

    public function getStatus(): BlogPostStatus
    {
        return $this->status;
    }

    public function setStatus(BlogPostStatus $status): BlogPost
    {
        $this->status = $status;

        return $this;
    }

    public function getIsVisibleOnRss(): bool
    {
        return $this->isVisibleOnRss;
    }

    public function setIsVisibleOnRss(bool $isVisibleOnRss): BlogPost
    {
        $this->isVisibleOnRss = $isVisibleOnRss;

        return $this;
    }

    public function getIsVisibleOnWeb(): bool
    {
        return $this->isVisibleOnWeb;
    }

    public function setIsVisibleOnWeb(bool $isVisibleOnWeb): BlogPost
    {
        $this->isVisibleOnWeb = $isVisibleOnWeb;

        return $this;
    }

    public function getEncoder(): string
    {
        return $this->encoder;
    }

    public function setEncoder(string $encoder): BlogPost
    {
        $this->encoder = $encoder;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): BlogPost
    {
        $this->summary = $summary;

        return $this;
    }

    public function getSummaryEncoded(): ?string
    {
        return $this->summaryEncoded;
    }

    public function setSummaryEncoded(?string $summaryEncoded): BlogPost
    {
        $this->summaryEncoded = $summaryEncoded;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): BlogPost
    {
        $this->content = $content;

        return $this;
    }

    public function getContentEncoded(): ?string
    {
        return $this->contentEncoded;
    }

    public function setContentEncoded(?string $contentEncoded): BlogPost
    {
        $this->contentEncoded = $contentEncoded;

        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): BlogPost
    {
        $this->customFields = $customFields;

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): BlogPost
    {
        $this->tags = $tags;

        return $this;
    }

    public function addTag(Tag $tag): BlogPost
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): BlogPost
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setCategories(Collection $categories): BlogPost
    {
        $this->categories = $categories;

        return $this;
    }

    public function addCategory(Category $category): BlogPost
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): BlogPost
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    #[Groups(['blog_post:read'])]
    public function getImages(): Collection
    {
        $blogPostImages = $this->blogPostImages->toArray();

        usort($blogPostImages, static fn (BlogPostImageMapping $a, BlogPostImageMapping $b): int => $a->getPosition() <=> $b->getPosition());

        return new ArrayCollection(array_map(
            static fn (BlogPostImageMapping $blogPostImage): Image => $blogPostImage->getImage(),
            $blogPostImages,
        ));
    }

    /**
     * @param Collection<int, Image> $images
     */
    public function setImages(Collection $images): BlogPost
    {
        $kept = [];
        $position = 0;

        foreach ($images as $image) {
            $blogPostImage = $this->findBlogPostImage($image);

            if ($blogPostImage instanceof BlogPostImageMapping && in_array($blogPostImage, $kept, true)) {
                continue;
            }

            if (!$blogPostImage instanceof BlogPostImageMapping) {
                $blogPostImage = (new BlogPostImageMapping())
                    ->setBlogPost($this)
                    ->setImage($image);

                $this->blogPostImages->add($blogPostImage);
            }

            $blogPostImage->setPosition($position);
            $kept[] = $blogPostImage;

            $position++;
        }

        foreach ($this->blogPostImages as $blogPostImage) {
            if (!in_array($blogPostImage, $kept, true)) {
                $this->blogPostImages->removeElement($blogPostImage);
            }
        }

        return $this;
    }

    private function findBlogPostImage(Image $image): ?BlogPostImageMapping
    {
        foreach ($this->blogPostImages as $blogPostImage) {
            if ($blogPostImage->getImage() === $image) {
                return $blogPostImage;
            }
        }

        return null;
    }

    public function addImage(Image $image): BlogPost
    {
        foreach ($this->blogPostImages as $blogPostImage) {
            if ($blogPostImage->getImage() === $image) {
                return $this;
            }
        }

        $blogPostImage = (new BlogPostImageMapping())
            ->setBlogPost($this)
            ->setImage($image)
            ->setPosition($this->blogPostImages->count());

        $this->blogPostImages->add($blogPostImage);

        return $this;
    }

    public function removeImage(Image $image): BlogPost
    {
        foreach ($this->blogPostImages as $blogPostImage) {
            if ($blogPostImage->getImage() === $image) {
                $this->blogPostImages->removeElement($blogPostImage);

                break;
            }
        }

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): BlogPost
    {
        $this->created = $created;

        return $this;
    }

    public function getSearchableText(): ?string
    {
        return $this->searchableText;
    }

    public function setSearchableText(?string $searchableText): BlogPost
    {
        $this->searchableText = $searchableText;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): BlogPost
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
