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
    private ?string $name = null;

    #[ORM\Column(name: 'base_url', length: 256, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $baseUrl = null;

    #[ORM\Column(name:'feed_url', length: 256, unique: true)]
    #[Groups(['blog_post:read'])]
    private ?string $feedUrl = null;

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

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $baseUrl): Blog
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getFeedUrl(): ?string
    {
        return $this->feedUrl;
    }

    public function setFeedUrl(?string $feedUrl): Blog
    {
        $this->feedUrl = $feedUrl;

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
