<?php declare(strict_types=1);

namespace App\Enum\DataCollector;

enum RequestType: string
{
    case Homepage = 'homepage';
    case BlogPosts = 'blogPosts';
    case BlogPost = 'blogPost';
}

