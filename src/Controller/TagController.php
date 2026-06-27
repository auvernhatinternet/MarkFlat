<?php

namespace App\Controller;

use Dotenv\Dotenv;
use App\Service\PostService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/tags')]
class TagController extends AbstractController
{
    private PostService $postService;
    private string $postsDir;
    private Dotenv $dotenv;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
        $this->dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $this->dotenv->load();
        $this->postsDir = $_ENV['MF_CMS_POSTS_DIR'];
    }

    #[Route('/{tag}', name: 'posts_by_tag')]
    public function postsByTag(string $tag, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $paginatedPosts = $this->postService->getPostsByTag($this->postsDir, $tag, $page);

        return $this->render('tag/show.html.twig', [
            'posts' => $paginatedPosts['posts'],
            'currentPage' => $paginatedPosts['currentPage'],
            'lastPage' => $paginatedPosts['lastPage'],
            'total' => $paginatedPosts['total'],
            'tag' => $tag,
        ]);
    }
}
