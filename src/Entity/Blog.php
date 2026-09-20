<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\BlogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BlogRepository::class)]
#[ORM\Table(name: 'blog')]
class Blog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['blog_post:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['blog_post:read'])]
    private string $name = '';

    #[ORM\Column(length: 255)]
    #[Groups(['blog_post:read'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['blog_post:read'])]
    private string $description = '';

    #[ORM\Column(name: 'base_url', length: 256, unique: true)]
    #[Groups(['blog_post:read'])]
    private string $baseUrl = '';

    #[ORM\Column(name:'feed_path', length: 256, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $feedPath = null;

    #[ORM\Column(name:'perma_link_pattern', length: 256)]
    #[Groups(['blog_post:read'])]
    private string $permaLinkPattern = '/post/%id%';

    #[ORM\Column(name:'webhook_path', length: 256, nullable: true)]
    #[Groups(['blog_post:read'])]
    private ?string $webhookPath = null;

    #[ORM\Column(name: 'ping_services', type: Types::JSON, options: ['default' => null])]
    #[Groups(['blog_post:read'])]
    private array $pingServices = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Blog
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Blog
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Blog
    {
        $this->description = $description;

        return $this;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $baseUrl): Blog
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getFeedPath(): ?string
    {
        return $this->feedPath;
    }

    public function setFeedPath(?string $feedPath): Blog
    {
        $this->feedPath = $feedPath;

        return $this;
    }

    public function getPermaLinkPattern(): string
    {
        return $this->permaLinkPattern;
    }

    public function setPermaLinkPattern(string $permaLinkPattern): Blog
    {
        $this->permaLinkPattern = $permaLinkPattern;

        return $this;
    }

    public function getWebhookPath(): ?string
    {
        return $this->webhookPath;
    }

    public function setWebhookPath(?string $webhookPath): Blog
    {
        $this->webhookPath = $webhookPath;

        return $this;
    }

    public function getPingServices(): array
    {
        return $this->pingServices;
    }

    public function setPingServices(array $pingServices): Blog
    {
        $this->pingServices = $pingServices;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(?DateTimeImmutable $created): Blog
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?DateTimeImmutable $updated): Blog
    {
        $this->updated = $updated;

        return $this;
    }


}
