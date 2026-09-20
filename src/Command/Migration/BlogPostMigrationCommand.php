<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Command\Migration\Exception\SkipImportException;
use App\Entity\BlogPost;
use App\Entity\BlogPostType;
use App\Entity\Category;
use App\Entity\Link;
use App\Entity\Tag;
use App\Enum\BlogPostStatus;
use App\Enum\CategoryType;
use App\Enum\LinkType;
use App\Enum\TagType;
use App\Repository\BlogPostRepository;
use App\Repository\BlogPostTypeRepository;
use App\Repository\BlogRepository;
use App\Repository\CategoryRepository;
use App\Repository\ImageRepository;
use App\Repository\LinkRepository;
use App\Repository\TagRepository;
use App\Service\BlogPost\LinkExtractor;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'app:migrate:blog-posts', description: 'Migrate blogpost from the current system.')]
class BlogPostMigrationCommand
{
    private array $blogCache = [];
    private array $blogPostTypeCache = [];
    private array $tagCache = [];
    private array $categoryCache = [];
    private array $linkCache = [];

    public function __construct(
        private Connection $migrationConnection,
        private BlogRepository $blogRepository,
        private BlogPostTypeRepository $blogPostTypeRepository,
        private BlogPostRepository $blogPostRepository,
        private TagRepository $tagRepository,
        private CategoryRepository $categoryRepository,
        private ImageRepository $imageRepository,
        private LinkRepository $linkRepository,
        private EntityManagerInterface $entityManager,
        private LinkExtractor $linkExtractor,
    ) {
    }
    public function __invoke(SymfonyStyle $io): int
    {
        $sql = 'SELECT * FROM journal_entry ORDER BY id DESC';
        $entries = $this->migrationConnection->fetchAllAssociative($sql);

        $metadata = $this->entityManager->getClassMetadata(BlogPost::class);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
        $metadata->setIdGenerator(new AssignedGenerator());

        $io->progressStart(count($entries));
        $counter = 0;
        foreach ($entries as $entry) {

            try {
                $entryId = $entry['id'] ?? null;
                if (is_null($entryId)) {
                    continue;
                }
                if ($this->isPostExists($entry['id'])) {
                    $io->progressAdvance();
                    continue;
                }
                $blogPostEntity = new BlogPost();
                $metadata->setFieldValue(entity: $blogPostEntity, field: 'id', value: $entryId);
                $this->assignScalarData(blogPostEntity: $blogPostEntity, entry: $entry);
                $this->assignDates(blogPostEntity: $blogPostEntity, entry: $entry, io: $io);
                $this->assignStatus(blogPostEntity: $blogPostEntity, entry: $entry);
                $this->assignBlog(blogPostEntity: $blogPostEntity, entry: $entry);
                $this->assignPostType(blogPostEntity: $blogPostEntity, entry: $entry);

                $this->assignTags(blogPostEntity: $blogPostEntity, entry: $entry);
                $this->assignCategories(blogPostEntity: $blogPostEntity, entry: $entry);
                $this->assignLinks(blogPostEntity: $blogPostEntity, entry: $entry);

                $this->assignImages(blogPostEntity: $blogPostEntity, entry: $entry);
                $this->assignCustomFields(blogPostEntity: $blogPostEntity, entry: $entry);

                $this->entityManager->persist($blogPostEntity);
                $counter++;
            } catch (SkipImportException $e) {
                continue;
            } catch (Throwable $throwable) {
                $io->error($throwable->getMessage());
                $io->text($throwable->getTraceAsString());
                return Command::FAILURE;
            }

            $io->progressAdvance();

            if ($counter > 100) {
                try {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $this->blogCache = [];
                    $this->blogPostTypeCache = [];
                    $this->tagCache = [];
                    $this->categoryCache = [];
                    $this->linkCache = [];
                } catch (Throwable $throwable) {
                    $io->error($throwable->getMessage());
                }
            }
        }
        $io->progressFinish();

        return Command::SUCCESS;
    }

    private function isPostExists(int $entryId): bool
    {
        $post = $this->blogPostRepository->find($entryId);

        return null !== $post;
    }

    private function assignScalarData(BlogPost $blogPostEntity, array $entry): void
    {
        $blogPostEntity->setTitle($entry['title'] ?? '');
        $blogPostEntity->setContent($entry['content'] ?? '');
        $blogPostEntity->setContentEncoded($entry['contentEncoded'] ?? '');
        $blogPostEntity->setSummary($entry['summary'] ?? null);
        $blogPostEntity->setSummaryEncoded($entry['summaryEncoded'] ?? null);
        $blogPostEntity->setSlug($entry['slug'] ?? '');
        $blogPostEntity->setSearchableText($entry['searchIndex'] ?? '');
    }

    private function assignDates(BlogPost $blogPostEntity, array $entry, SymfonyStyle $io): void
    {
        try {
            $entryDate = $entry['date'] ?? null;
            if (is_null($entryDate)) {
                throw new Exception('Date on Entry is null');
            }
            $date = new DateTimeImmutable($entry['date'] ?? null);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            throw new \RuntimeException(sprintf('Can not assign date (%s): %s', $entry['id'],  $e->getMessage()));
        }

        try {
            $updated = new DateTimeImmutable($entry['updated'] ?? null);
        } catch (\DateMalformedStringException $e) {
            $io->warning('Invalid updated: '.$e->getMessage());
            $updated = new DateTimeImmutable();
        }

        $blogPostEntity->setUpdated($updated);
        $blogPostEntity->setPublishedDate($date);
        $blogPostEntity->setCreated($date);
    }

    private function assignStatus(BlogPost $blogPostEntity, array $entry): void
    {
        $statusText = match ($entry['status'] ?? null) {
            'pending' => 'draft',
            'published' => 'published',
            default => 'hidden',
        };
        $status = BlogPostStatus::tryFrom($statusText);
        if (is_null($status)) {
            $status = BlogPostStatus::Hidden;
        }

        $blogPostEntity->setStatus($status);
    }

    /**
     * @throws SkipImportException
     * @throws Exception
     */
    private function assignBlog(BlogPost $blogPostEntity, array $entry): void
    {
        $blogId = match ($entry['entrySource'] ?? null) {
            'notes', 'photoblog', 'wordpress', 'twitter', 'flickr', 'journal', 'now' => 1, // kniebes.com
            'grumpy-old-man', 'mastodon', 'ruhr.social', 'grumpy-old-man-wordpress' => 2,  // grumpyoldman.com
            'lifestream' => 3, // Lifestream
            default => null,
        };
        if (is_null($blogId)) {
            throw new SkipImportException();
        }

        if (array_key_exists($blogId, $this->blogCache)) {
            $blogPostEntity->setBlog($this->blogCache[$blogId]);
            return;
        }

        $blog = $this->blogRepository->find($blogId);
        if (is_null($blog)) {
            throw new Exception('Blog not found with id '.$blogId);
        }

        $blogPostEntity->setBlog($blog);
    }

    private function assignPostType(BlogPost $blogPostEntity, array $entry): void
    {
        $entrySource = $entry['entrySource'] ?? null;
        $blogPostTypeName = match ($entrySource) {
            'notes' => 'Notes',
            'now' => 'Now',
            'photoblog' => 'Photoblog',
            'wordpress', 'twitter', 'flickr', 'journal' => 'Journal',
            default => 'Default',
        };

        if (array_key_exists($blogPostTypeName, $this->blogPostTypeCache)) {
            $blogPostEntity->setBlogPostType($this->blogPostTypeCache[$blogPostTypeName]);
            return;
        }

        $blogPostType = $this->blogPostTypeRepository->findOneBy(['name' => $blogPostTypeName]);
        if (is_null($blogPostType)) {
            $blogPostType = new BlogPostType();
            $blogPostType->setName($blogPostTypeName);
            $this->entityManager->persist($blogPostType);
        }

        $this->blogPostTypeCache[$blogPostTypeName] = $blogPostType;
        $blogPostEntity->setBlogPostType($blogPostType);
    }

    private function assignTags(BlogPost $blogPostEntity, array $entry): void
    {
        $sql = <<<SQL
SELECT t.*
FROM journal_tag t
LEFT JOIN journal_entry_tag et on et.tagId = t.id
WHERE et.entryId = :entryId and t.scheme IN ('tag', 'photo')
SQL;

        $entryTags = $this->migrationConnection->fetchAllAssociative($sql, ['entryId' => $entry['id']]);

        foreach ($entryTags as $entryTag) {
            $blogPostEntity->addTag($this->resolveTag($entryTag));
        }
    }

    private function resolveTag(array $entryTag): Tag
    {
        $term = $entryTag['term'];
        if (array_key_exists($term, $this->tagCache)) {
            return $this->tagCache[$term];
        }

        $tagEntity = $this->tagRepository->findOneBy(['term' => $term]);
        if (is_null($tagEntity)) {
            $tagEntity = new Tag();
            $tagEntity->setTerm($term);
            $tagEntity->setSlug($entryTag['slug']);
            $tagEntity->setType(TagType::General);
            $this->entityManager->persist($tagEntity);
        }

        $this->tagCache[$term] = $tagEntity;

        return $tagEntity;
    }

    private function assignCategories(BlogPost $blogPostEntity, array $entry): void
    {
        $sql = <<<SQL
SELECT t.*
FROM journal_tag t
LEFT JOIN journal_entry_tag et on et.tagId = t.id
WHERE et.entryId = :entryId and t.scheme = 'category'
SQL;

        $entryTags = $this->migrationConnection->fetchAllAssociative($sql, ['entryId' => $entry['id']]);

        foreach ($entryTags as $entryCategory) {
            if (str_contains($entryCategory['term'], 'EntryType:')) {
                continue;
            }
            $blogPostEntity->addCategory($this->resolveCategory($entryCategory));
        }
    }

    private function resolveCategory(array $entryCategory): Category
    {
        $term = $entryCategory['term'];
        if (array_key_exists($term, $this->categoryCache)) {
            return $this->categoryCache[$term];
        }

        $categoryEntity = $this->categoryRepository->findOneBy(['term' => $term]);
        if (is_null($categoryEntity)) {
            $categoryEntity = new Category();
            $categoryEntity->setTerm($term);
            $categoryEntity->setSlug($entryCategory['slug']);
            $categoryEntity->setType(CategoryType::BlogPost);
            $this->entityManager->persist($categoryEntity);
        }

        $this->categoryCache[$term] = $categoryEntity;

        return $categoryEntity;
    }

    private function assignLinks(BlogPost $blogPostEntity, array $entry): void
    {
        $links = $this->linkExtractor->extract(html: $entry['contentEncoded'] ?? '');
        foreach ($links as $link) {
            if (strlen($link) > 255) {
                continue;
            }
            $blogPostEntity->addLink($this->resolveLink($link));
        }
    }

    private function resolveLink(string $url): Link
    {
        if (array_key_exists($url, $this->linkCache)) {
            return $this->linkCache[$url];
        }

        $linkEntity = $this->linkRepository->findOneBy(['url' => $url]);
        if (is_null($linkEntity)) {
            $linkEntity = new Link();
            $linkEntity->setUrl($url);
            $linkEntity->setType(LinkType::BlogPostLink);
            $this->entityManager->persist($linkEntity);
        }

        $this->linkCache[$url] = $linkEntity;

        return $linkEntity;
    }

    private function assignImages(BlogPost $blogPostEntity, array $entry): void
    {
        if ($entry['id'] === 27419) {
            sleep(1);
        }
        if (!empty($entry['imageId'])) {
            $images = $this->imageRepository->find($entry['imageId']);
            if (!is_null($images)) {
                $blogPostEntity->addImage($images);
            }
        }

        $index = json_decode(($entry['metadataIndex'] ?? '[]'), true);
        $additionalPhotoblogImages = $index['additional_photoblog_images'] ?? null;
        if (is_null($additionalPhotoblogImages)) {
            return;
        }

        $urlList = preg_split('/\r\n|\r|\n|,/', $additionalPhotoblogImages);
        foreach ($urlList as $url) {
            $image = $this->imageRepository->findOneBy(['url' => $url]);
            if (is_null($image)) {
                continue;
            }
            $blogPostEntity->addImage($image);
        }
    }

    public function assignCustomFields(BlogPost $blogPostEntity, array $entry): void
    {
        $skipNames = [
            'artikelbild_1536x1536',
            'artikelbild_2048x2048',
            'artikelbild_4K',
            'artikelbild_fullhd',
            'artikelbild_large',
            'artikelbild_medium',
            'artikelbild_medium_large',
            'artikelbild_post-thumbnail',
            'artikelbild_thumbnail',
            'artikelbild_wqhd',
            'auszug',
            'CurrentlyViewingTitle',
            'CurrentlyViewingUrl',
            'flickr_image_c',
            'flickr_image_l',
            'flickr_image_o',
            'flickr_url',
            'journal_image_id',
            'journal_image_size_100',
            'journal_image_size_1024',
            'journal_image_size_2048',
            'journal_image_size_800',
            'journal_image_size_original',
            'journal_image_url_100',
            'journal_image_url_1024',
            'journal_image_url_2048',
            'journal_image_url_800',
            'photoblog_com_url',
            'photos_in_post',
            'pixelfed_url',
        ];
        $sql = 'SELECT * FROM journal_metadata WHERE entryId = :entryId';
        $metaData = $this->migrationConnection->fetchAllAssociative($sql, ['entryId' => $entry['id']]);
        $customFields = [];
        foreach ($metaData as $metaData) {
            $name = $metaData['name'];
            if (empty($name)) {
                continue;
            }
            if (in_array($name, $skipNames)) {
                continue;
            }
            $customFields[$name] = $metaData['value'];
        }
        $blogPostEntity->setCustomFields($customFields);
    }
}
