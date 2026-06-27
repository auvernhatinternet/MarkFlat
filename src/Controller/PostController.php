<?php

namespace App\Controller;

use Dotenv\Dotenv;
use App\Service\PostService;
use App\Service\MarkdownTailwindService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/posts')]
class PostController extends AbstractController
{
    private PostService $postService;
    private string $postsDir;
    private MarkdownTailwindService $markdownTailwindService;
    private Dotenv $dotenv;

    public function __construct(
        PostService $postService,
        MarkdownTailwindService $markdownTailwindService
    ) {
        $this->postService = $postService;
        $this->markdownTailwindService = $markdownTailwindService;

        $this->dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $this->dotenv->load();
        $this->postsDir = $_ENV['MF_CMS_POSTS_DIR'];
    }

    #[Route('/', name: 'posts_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $paginatedPosts = $this->postService->getPaginatedPosts($this->postsDir, $page);

        return $this->render('posts/index.html.twig', [
            'posts' => $paginatedPosts['posts'],
            'currentPage' => $paginatedPosts['currentPage'],
            'lastPage' => $paginatedPosts['lastPage'],
            'total' => $paginatedPosts['total'],
            'current_route' => 'posts_index',
            'cms_site_name' => $_ENV['MF_CMS_SITE_NAME'] ?? 'MarkFlat CMS',
            'cms_theme' => $_ENV['MF_CMS_THEME'] ?? 'default',
        ]);
    }

    #[Route('/{slug}', name: 'post_show')]
    public function show(string $slug): Response
    {
        // Démarrer la session PHP si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $post = $this->postService->getPost($this->postsDir, $slug);

        if (!$post) {
            throw new NotFoundHttpException('Post not found');
        }

        // Récupérer les posts vus depuis la session PHP native
        $viewedPosts = $_SESSION['viewed_posts'] ?? [];

        // Si le post n'a pas été vu dans cette session, incrémenter le compteur
        if (!in_array($post->getSlug(), $viewedPosts)) {
            $this->postService->incrementViews($this->postsDir, $post->getSlug());
            $viewedPosts[] = $post->getSlug();
            $_SESSION['viewed_posts'] = $viewedPosts;
        }

        // Convertir le contenu Markdown en HTML avec les classes Tailwind
        $htmlContent = $this->markdownTailwindService->convert($post->getContent());
        $post->setContent($htmlContent);

        return $this->render('posts/show.html.twig', [
            'post' => $post,
            'current_route' => 'post_show',
            'cms_site_name' => $_ENV['MF_CMS_SITE_NAME'] ?? 'MarkFlat CMS',
            'cms_theme' => $_ENV['MF_CMS_THEME'] ?? 'default',
        ]);
    }

    #[Route('/latest/{limit}', name: 'posts_latest', defaults: ['limit' => 5])]
    public function latest(int $limit): Response
    {
        $posts = $this->postService->getAllPosts($this->postsDir);
        $posts = array_slice($posts, 0, $limit);

        return $this->render('posts/_latest.html.twig', [
            'posts' => $posts,
            'current_route' => 'posts_latest',
            'cms_site_name' => $_ENV['MF_CMS_SITE_NAME'] ?? 'MarkFlat CMS',
            'cms_theme' => $_ENV['MF_CMS_THEME'] ?? 'default',
        ]);
    }
}
