<?php

namespace App\Controller;

use Dotenv\Dotenv;
use App\Service\PageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VarController extends AbstractController
{
    public function __construct()
    {
        Dotenv::createImmutable(__DIR__ . '/../../')->load();
    }

    #[Route('/var-render/{var}', name: 'var-render')]
    public function siteName(string $var): Response
    {
        $result = $_ENV[$var];

        return $this->render('var/varRender.html.twig', [
            'var' => $result,
        ]);
    }

    #[Route('/nav', name: 'nav')]
    public function nav(PageService $pageService, Request $request): Response
    {
        $allPages = $pageService->getAllPages($_ENV['MF_CMS_PAGES_DIR']);

        $menuPages = array_filter($allPages, function ($page) {
            return $page['show_in_menu'];
        });

        return $this->render('base/nav.html.twig', [
            'pages' => array_values($menuPages),
            'current_route' => $request->get('current_route'),
            'current_path' => $request->get('current_path'),
        ]);
    }
}
