<?php declare(strict_types=1);

namespace App\Model\DataCollector;

readonly class BlogPostRequestData implements RequestDataInterface
{
    public function __construct(
        private ?int $id = null,
        private ?int $year = null,
        private ?int $month = null,
        private ?int $day = null,
        private ?string $slug = null,
    )
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

}
