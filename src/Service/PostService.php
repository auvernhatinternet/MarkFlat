<?php

namespace App\Service;

use App\Post\Post;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PostService
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    /**
     * @return Post[]
     */
    public function getAllPosts(string $postsDirectory): array
    {
        $posts = [];
        $postsDirectory = $this->projectDir . $postsDirectory;

        if (!is_dir($postsDirectory)) {
            return $posts;
        }

        $files = glob($postsDirectory . '/*.md');

        foreach ($files as $file) {
            $post = $this->createPostFromFile($file);
            if ($post) {
                $posts[] = $post;
            }
        }

        usort($posts, function ($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });

        return $posts;
    }

    /**
     * @return array{posts: Post[], currentPage: int, lastPage: int, total: int}
     */
    public function getPaginatedPosts(string $postsDirectory, int $page = 1): array
    {
        $postsPerPage = (int)($_ENV['MF_CMS_POSTS_PER_PAGE'] ?? 10);
        $posts = $this->getAllPosts($postsDirectory);

        $total = count($posts);

        $lastPage = max(1, ceil($total / $postsPerPage));

        $currentPage = min(max(1, $page), $lastPage);

        $offset = ($currentPage - 1) * $postsPerPage;

        return [
            'posts' => array_slice($posts, $offset, $postsPerPage),
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * @return array{posts: Post[], currentPage: int, lastPage: int, total: int}
     */
    public function getPostsByTag(string $postsDirectory, string $tag, int $page = 1): array
    {
        $postsPerPage = (int)($_ENV['MF_CMS_POSTS_PER_PAGE'] ?? 10);
        $allPosts = $this->getAllPosts($postsDirectory);

        // Filtrer les posts par tag
        $filteredPosts = array_filter($allPosts, function ($post) use ($tag) {
            return in_array($tag, $post->getTags());
        });

        $total = count($filteredPosts);
        $lastPage = max(1, ceil($total / $postsPerPage));
        $currentPage = min(max(1, $page), $lastPage);
        $offset = ($currentPage - 1) * $postsPerPage;

        return [
            'posts' => array_slice($filteredPosts, $offset, $postsPerPage),
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }

    public function getPost(string $postsDirectory, string $slug): ?Post
    {
        $postsDirectory = $this->projectDir . $postsDirectory;
        $filePath = $postsDirectory . '/' . $slug . '.md';

        if (!file_exists($filePath)) {
            return null;
        }

        return $this->createPostFromFile($filePath);
    }

    public function incrementViews(string $postsDirectory, string $slug): void
    {
        $postsDirectory = $this->projectDir . $postsDirectory;
        $filePath = $postsDirectory . '/' . $slug . '.md';

        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $parts = explode('---', $content, 3);
        if (count($parts) !== 3) {
            return;
        }

        try {
            $frontmatter = Yaml::parse(trim($parts[1]));
            $frontmatter['views'] = ($frontmatter['views'] ?? 0) + 1;

            $newContent = '---' . "\n" . Yaml::dump($frontmatter) . "\n---\n" . trim($parts[2]);
            file_put_contents($filePath, $newContent);
        } catch (\Exception $e) {
            return;
        }
    }

    private function createPostFromFile(string $filePath): ?Post
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }
        $parts = explode('---', $content, 3);
        if (count($parts) !== 3) {
            return null;
        }

        try {
            $frontmatter = Yaml::parse(trim($parts[1]));
            $date = isset($frontmatter['date'])
                ? new \DateTime($frontmatter['date'])
                : new \DateTime('now');

            return new Post(
                $frontmatter['title'] ?? '',
                $date,
                $frontmatter['slug'] ?? basename($filePath, '.md'),
                trim($parts[2]),
                (int)($frontmatter['views'] ?? 0),
                $frontmatter['author'] ?? null,
                $frontmatter['description'] ?? null,
                $frontmatter['tags'] ?? []
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return Post[]
     */
    public function getPostsBySearch(string $postsDirectory, string $search): array
    {
        $posts = $this->getAllPosts($postsDirectory);
        $search = strtolower($search);
        return array_filter($posts, function ($post) use ($search) {
            $titleMatch = strpos(strtolower($post->getTitle()), $search) !== false;
            $descriptionMatch = strpos(strtolower($post->getDescription()), $search) !== false;
            $contentMatch = strpos(strtolower($post->getContent()), $search) !== false;

            return $titleMatch || $descriptionMatch || $contentMatch;
        });
    }

    /**
     * @return array{posts: Post[], currentPage: int, lastPage: int, total: int}
     */
    public function getPaginatedPostsBySearch(string $postsDirectory, string $search, int $page = 1): array
    {
        $postsPerPage = (int)($_ENV['MF_CMS_POSTS_PER_PAGE'] ?? 10);
        $allPosts = $this->getPostsBySearch($postsDirectory, $search);

        $total = count($allPosts);
        $lastPage = max(1, ceil($total / $postsPerPage));
        $currentPage = min(max(1, $page), $lastPage);
        $offset = ($currentPage - 1) * $postsPerPage;

        return [
            'posts' => array_slice($allPosts, $offset, $postsPerPage),
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }
}
