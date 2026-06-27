<?php

namespace App\Tests\Controller;

use App\Controller\PostController;
use App\Post\Post;
use App\Service\PostService;
use App\Service\MarkdownTailwindService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Component\DependencyInjection\Container;

class PostControllerTest extends TestCase
{
    private PostController $controller;
    private PostService|MockObject $postService;
    private ParameterBagInterface|MockObject $parameterBag;
    private Environment|MockObject $twig;
    private string $postsDir;
    private MarkdownTailwindService|MockObject $markdownService;
    private Container|MockObject $container;
    private Post $testPost;

    protected function setUp(): void
    {
        // Mock PostService
        $this->postService = $this->createMock(PostService::class);

        // Mock ParameterBag
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->postsDir = '/posts';
        $this->parameterBag->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['kernel.project_dir', __DIR__ . '/../..'],
            ]);

        // Mock MarkdownTailwindService
        $this->markdownService = $this->createMock(MarkdownTailwindService::class);
        $this->markdownService->expects($this->any())
            ->method('convert')
            ->willReturnCallback(fn ($content) => $content);

        // Mock Twig
        $this->twig = $this->createMock(Environment::class);
        $this->twig->expects($this->any())
            ->method('render')
            ->willReturn('rendered template');

        // Mock Container
        $this->container = $this->createMock(Container::class);
        $this->container->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['twig', true],
                ['parameter_bag', true],
            ]);
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['twig', $this->twig],
                ['parameter_bag', $this->parameterBag],
            ]);

        // Set up environment variables for testing
        $_ENV['MF_CMS_POSTS_DIR'] = $this->postsDir;
        $_ENV['MF_CMS_SITE_NAME'] = 'Test CMS';
        $_ENV['MF_CMS_THEME'] = 'test';

        // Create controller
        $this->controller = new PostController(
            $this->postService,
            $this->markdownService
        );
        $this->controller->setContainer($this->container);

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

    /**
     * @param array<string, mixed> $query
     */
    private function createRequest(array $query = []): Request
    {
        return new Request($query);
    }

    public function testPostShowReturnsPost(): void
    {
        // Arrange
        $this->postService->expects($this->once())
            ->method('getPost')
            ->with($this->postsDir, 'test-post')
            ->willReturn($this->testPost);

        $request = $this->createRequest();

        // Act
        $response = $this->controller->show('test-post');

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostShowThrowsExceptionForNonExistentPost(): void
    {
        // Arrange
        $this->postService->expects($this->once())
            ->method('getPost')
            ->with($this->postsDir, 'non-existent')
            ->willReturn(null);

        $request = $this->createRequest();

        // Assert & Act
        $this->expectException(NotFoundHttpException::class);
        $this->controller->show('non-existent');
    }

    public function testPostsIndexWithPagination(): void
    {
        // Arrange
        $paginatedData = [
            'posts' => [$this->testPost],
            'currentPage' => 1,
            'lastPage' => 2,
            'total' => 6,
        ];

        $this->postService->expects($this->once())
            ->method('getPaginatedPosts')
            ->with($this->postsDir, 1)
            ->willReturn($paginatedData);

        $request = $this->createRequest(['page' => '1']);

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testLatestPostsReturnsLimitedPosts(): void
    {
        // Arrange
        $this->postService->expects($this->once())
            ->method('getAllPosts')
            ->with($this->postsDir)
            ->willReturn([$this->testPost]);

        $request = $this->createRequest();

        // Act
        $response = $this->controller->latest(5);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
