<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector\Interface;

use App\Entity\Blog;
use App\Model\DataCollector\RequestDataInterface;
use App\Model\DataCollector\ResponseDataBag;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 */
#[AutoconfigureTag('app.data_collector')]
interface DataCollectorInterface
{
    public const string METHOD_BLOG_POSTS = 'blog-posts';
    public const string METHOD_BLOG_POST = 'blog-post';
    public const string METHOD_BLOG_POST_META = 'blog-post-meta';
    public const string METHOD_PAGE = 'page';
    public function collect(Blog $blog, string $method, Request $request, ResponseDataBag $data): void;
}
