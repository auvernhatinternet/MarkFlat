<?php

use Dotenv\Dotenv;
use App\Component\MapComponent;
use App\Component\CodeComponent;
use App\Component\ButtonComponent;
use App\Service\ComponentRegistry;
use App\Component\AccordionComponent;
use Twig\Extra\Markdown\DefaultMarkdown;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;
use MarkFlat\MarkFlatEditor\MarkFlatEditorBundle;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private $markdown;
    private const EDITOR_BUNDLE_PATH = '/vendor/markflat/markflat-editor';
    private const EDITOR_CONFIG_PATH = '/src/Resources/config';

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        $this->markdown = new DefaultMarkdown();
    }

    private function getRootDir(): string
    {
        return dirname(__DIR__);
    }

    private function isEditorBundleInstalled(): bool
    {
        return is_dir($this->getEditorBundlePath());
    }

    private function getEditorBundlePath(): string
    {
        return $this->getRootDir().self::EDITOR_BUNDLE_PATH;
    }

    private function getEditorBundleConfigPath(): string
    {
        return $this->getEditorBundlePath().self::EDITOR_CONFIG_PATH;
    }

    public function registerBundles(): iterable
    {
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
        yield new TwigBundle();
        yield new TwigExtraBundle();
        
        if ($this->isEditorBundleInstalled()) {
            yield new MarkFlatEditorBundle();
        }
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $defaultLocale = $_ENV['MF_CMS_DEFAULT_LOCALE'] ?? 'fr';
        $supportedLocales = json_decode($_ENV['MF_CMS_SUPPORTED_LOCALES'] ?? '["fr","en"]', true);
        $projectDir = $this->getRootDir();

        $container->extension('framework', [
            'secret' => 'S0ME_SECRET',
            'router' => [
                'utf8' => true,
                'resource' => 'kernel::loadRoutes'
            ],
            'default_locale' => $defaultLocale,
            'translator' => [
                'default_path' => '%kernel.project_dir%/translations',
                'fallbacks' => $supportedLocales,
                'paths' => ['%kernel.project_dir%/translations']
            ],
            'session' => [
                'enabled' => true,
                'handler_id' => null
            ]
        ]);

        $container->extension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'globals' => [
                'default_locale' => $defaultLocale,
                'supported_locales' => $supportedLocales
            ]
        ]);

        if ($this->isEditorBundleInstalled()) {
            $container->extension('mark_flat_editor', [
                'admin_password' => $_ENV['MARKFLAT_EDITOR_ADMIN_PASSWORD'] ?? 'admin'
            ]);
        }

        // Configure services
        $services = $container->services();
        
        $services->defaults()
            ->autowire()
            ->autoconfigure();

        $services->load('App\\', '../src/')
            ->exclude('../src/DependencyInjection/')
            ->exclude('../src/Entity/')
            ->exclude('../src/Kernel.php');

        // Register Markdown Components
        $services->set(MapComponent::class)
            ->args([new Reference('App\Service\MapService')]);

        $services->set(ButtonComponent::class)
            ->args([new Reference('App\Service\ButtonService')]);

        $services->set(CodeComponent::class)
            ->args([new Reference('App\Service\CodeService')]);

        $services->set(AccordionComponent::class)
            ->args([new Reference('App\Service\AccordionService')]);

        // Configure Components Registry
        $services->set(ComponentRegistry::class)
            ->call('addComponent', [new Reference(MapComponent::class)])
            ->call('addComponent', [new Reference(ButtonComponent::class)])
            ->call('addComponent', [new Reference(CodeComponent::class)])
            ->call('addComponent', [new Reference(AccordionComponent::class)]);

        // Add translation paths to container parameters
        $container->parameters()
            ->set('app.supported_locales', $supportedLocales)
            ->set('app.default_locale', $defaultLocale);
    }

    protected function configureRoutes($routes): void
    {
        $routes->import('../src/Controller/', 'attribute');
        
        // Import MarkFlatEditor routes if the bundle is installed
        if ($this->isEditorBundleInstalled()) {
            $routes->import($this->getEditorBundleConfigPath().'/routes.yaml', 'yaml');
        }
    }   
}


return static function (array $context) {
    Dotenv::createImmutable(dirname(__DIR__))->load();

    $environment = $_ENV['_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod';
    $debug = (bool)($_ENV['_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false);
    return new Kernel($environment, $debug);
};