<?php

namespace App\Controller;

use Dotenv\Dotenv;
use App\Service\PostService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/search')]
class SearchController extends AbstractController
{
    public function __construct(
        private PostService $postService
    ) {
        Dotenv::createImmutable(__DIR__ . '/../../')->load();
    }

    #[Route('/posts', name: 'search')]
    public function search(Request $request): Response
    {
        $search = (string)$request->query->get('search', "");
        $page = (int)$request->query->getInt('page', 1);

        $paginatedPosts = $this->postService->getPaginatedPostsBySearch($_ENV['MF_CMS_POSTS_DIR'], $search, $page);

        return $this->render('pages/search.html.twig', [
            'posts' => $paginatedPosts['posts'],
            'currentPage' => $paginatedPosts['currentPage'],
            'lastPage' => $paginatedPosts['lastPage'],
            'total' => $paginatedPosts['total'],
            'search' => $search,
            'current_route' => 'search',
            'cms_site_name' => $_ENV['MF_CMS_SITE_NAME'] ?? 'MarkFlat CMS',
            'cms_theme' => $_ENV['MF_CMS_THEME'] ?? 'default',
        ]);
    }
}
