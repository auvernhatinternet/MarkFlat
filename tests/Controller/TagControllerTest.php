<?php

namespace App\Tests\Controller;

use App\Controller\TagController;
use App\Post\Post;
use App\Service\PostService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Psr\Container\ContainerInterface;

class TagControllerTest extends TestCase
{
    private TagController $controller;
    private PostService|MockObject $postService;
    private Environment|MockObject $twig;
    private string $postsDir;
    private Post $testPost;

    protected function setUp(): void
    {
        // Mock PostService
        $this->postService = $this->createMock(PostService::class);

        // Set test posts directory according to CMS configuration
        $this->postsDir = '/posts';
        putenv('MF_CMS_POSTS_DIR=' . $this->postsDir);
        $_ENV['MF_CMS_POSTS_DIR'] = $this->postsDir;

        // Mock Twig
        $this->twig = $this->createMock(Environment::class);
        $this->twig->expects($this->any())
            ->method('render')
            ->willReturn('rendered template');

        // Create controller
        $this->controller = new TagController($this->postService);
        $this->controller->setContainer($this->getContainer());

        // Create test post
        $this->testPost = new Post(
            'Test Post',
            new \DateTime('2025-03-11'),
            'test-post',
            'Test content',
            0,
            'Test Author',
            'Test Description',
            ['test']
        );
    }

    private function getContainer(): ContainerInterface|MockObject
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('has')
            ->willReturn(true);
        $container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['twig', $this->twig],
            ]);
        return $container;
    }

    public function testPostsByTagReturnsFilteredPosts(): void
    {
        // Arrange
        $tag = 'test';
        $paginatedData = [
            'posts' => [$this->testPost],
            'currentPage' => 1,
            'lastPage' => 1,
            'total' => 1,
        ];

        $this->postService->expects($this->once())
            ->method('getPostsByTag')
            ->with($this->postsDir, $tag, 1)
            ->willReturn($paginatedData);

        $request = new Request(['page' => '1']);

        // Act
        $response = $this->controller->postsByTag($tag, $request);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostsByTagWithEmptyResults(): void
    {
        // Arrange
        $tag = 'nonexistent';
        $paginatedData = [
            'posts' => [],
            'currentPage' => 1,
            'lastPage' => 1,
            'total' => 0,
        ];

        $this->postService->expects($this->once())
            ->method('getPostsByTag')
            ->with($this->postsDir, $tag, 1)
            ->willReturn($paginatedData);

        $request = new Request(['page' => '1']);

        // Act
        $response = $this->controller->postsByTag($tag, $request);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $paginatedData['posts'], 'Le tableau posts devrait être vide');
        $this->assertEquals(1, $paginatedData['currentPage'], 'La page courante devrait être 1');
        $this->assertEquals(1, $paginatedData['lastPage'], 'La dernière page devrait être 1');
        $this->assertEquals(0, $paginatedData['total'], 'Le total devrait être 0');
    }

    public function testPostsByTagWithPagination(): void
    {
        // Arrange
        $tag = 'test';
        $posts = array_fill(0, 10, $this->testPost);
        $page = 2;

        $paginatedData = [
            'posts' => array_slice($posts, 5, 5),
            'currentPage' => $page,
            'lastPage' => 2,
            'total' => 10,
        ];

        $this->postService->expects($this->once())
            ->method('getPostsByTag')
            ->with($this->postsDir, $tag, $page)
            ->willReturn($paginatedData);

        $request = new Request(['page' => (string)$page]);

        // Act
        $response = $this->controller->postsByTag($tag, $request);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
