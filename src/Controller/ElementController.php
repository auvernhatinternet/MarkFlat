<?php

namespace App\Controller;

use Dotenv\Dotenv;
use App\Service\ElementService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/elements')]
class ElementController extends AbstractController
{
    private string $elementDir;

    public function __construct()
    {
        Dotenv::createImmutable(__DIR__ . '/../../')->load();
        $this->elementDir = $_ENV['MF_CMS_ELEMENTS_DIR'];
    }

    #[Route('/home_hero', name: 'home_hero')]
    public function homeHero(ElementService $elementService): Response
    {
        $content = $elementService->getElementContent('home_hero', $this->elementDir);
        return $this->render('elements/_simple_markdown_element.html.twig', [
            'content' => $content,
        ]);
    }
}
