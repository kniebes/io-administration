<?php declare(strict_types=1);

namespace App\Service\BlogPost;

use App\Entity\BlogPost;
use App\Entity\Link;
use App\Enum\LinkType;
use App\Repository\LinkRepository;
use Doctrine\Common\Collections\ArrayCollection;

class LinkUpdater
{
    public function __construct(
        private LinkExtractor $linkExtractor,
        private LinkRepository $linkRepository,
    )
    {
    }

    public function update(BlogPost $blogPost): void
    {
        $urls = $this->linkExtractor->extract($blogPost->getContentEncoded());
        $blogPost->setLinks(new ArrayCollection());
        $collectedUrls = [];

        foreach ($urls as $url) {
            if (in_array($url, $collectedUrls, true)) {
                continue;
            }

            $linkEntity = $this->linkRepository->findOneBy(['url' => $url]);
            if (empty($linkEntity)) {
                $linkEntity = new Link();
                $linkEntity->setUrl($url);
                $linkEntity->setType(LinkType::BlogPostLink);
            }
            $blogPost->addLink($linkEntity);
            $collectedUrls[] = $url;
        }
    }
}
