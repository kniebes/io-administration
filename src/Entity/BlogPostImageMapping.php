<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'blog_post_image_mapping')]
#[ORM\UniqueConstraint(name: 'uniq_blog_post_image', columns: ['blog_post_id', 'image_id'])]
class BlogPostImageMapping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BlogPost::class, inversedBy: 'blogPostImages')]
    #[ORM\JoinColumn(name: 'blog_post_id', referencedColumnName: 'id', nullable: false)]
    private BlogPost $blogPost;

    #[ORM\ManyToOne(targetEntity: Image::class, inversedBy: "blogPostImages")]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id', nullable: false)]
    private Image $image;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlogPost(): BlogPost
    {
        return $this->blogPost;
    }

    public function setBlogPost(BlogPost $blogPost): BlogPostImageMapping
    {
        $this->blogPost = $blogPost;

        return $this;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function setImage(Image $image): BlogPostImageMapping
    {
        $this->image = $image;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): BlogPostImageMapping
    {
        $this->position = $position;

        return $this;
    }
}
