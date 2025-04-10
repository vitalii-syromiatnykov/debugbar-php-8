<?php

declare(strict_types=1);

namespace DebugBar\Bridge;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Profiler\Dumper\HtmlDumper;
use Twig\Profiler\Profile;

/**
 * Collects data about rendered templates
 *
 * http://twig.sensiolabs.org/
 *
 * A \Twig\Profiler\Profile should be added to your \Twig\Environment
 * The root-Twig\Profiler\Profile-object should then be injected into this collector
 *
 * you can optionally provide the \Twig\Environment or the \Twig\Loader to also create
 * debug-links.
 *
 * @see \Twig\Extension\ProfilerExtension, \Twig\Profiler\Profile
 *
 * <code>
 * $env = new \Twig\Environment($loader); // Or from a PSR11-container
 * $profile = new \Twig\Profiler\Profile();
 * $env->addExtension(new \Twig\Extension\ProfilerExtension($profile));
 * $debugbar->addCollector(new ModernTwigProfileCollector($profile, $env));
 * // or: $debugbar->addCollector(new ModernTwigProfileCollector($profile, $loader));
 * </code>
 */
class NamespacedTwigProfileCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var LoaderInterface|Environment|null
     */
    private $loader;

    private ?int $templateCount = null;

    private ?int $blockCount = null;

    private ?int $macroCount = null;

    /**
     * @var array[] {
     * @var string $name
     * @var int $render_time
     * @var string $render_time_str
     * @var string $memory_str
     * @var string $xdebug_link
     * }
     */
    private ?array $templates = null;

    /**
     * TwigProfileCollector constructor.
     *
     * @param LoaderInterface|Environment $loaderOrEnv
     */
    #[\ReturnTypeWillChange] public function __construct(private readonly Profile $profile, $loaderOrEnv = null)
    {
        if ($loaderOrEnv instanceof Environment) {
            $loaderOrEnv = $loaderOrEnv->getLoader();
        }

        $this->loader = $loaderOrEnv;
    }

    /**
     * Returns a hash where keys are control names and their values
     * an array of options as defined in {@see \DebugBar\JavascriptRenderer::addControl()}
     */
    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            'twig' => [
                'icon' => 'leaf',
                'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
                'map' => 'twig',
                'default' => json_encode(['templates' => []]),
            ],
            'twig:badge' => [
                'map' => 'twig.badge',
                'default' => 0,
            ],
        ];
    }

    #[\ReturnTypeWillChange] public function getAssets(): array
    {
        return [
            'css' => 'widgets/templates/widget.css',
            'js' => 'widgets/templates/widget.js',
        ];
    }

    /**
     * Called by the DebugBar when data needs to be collected
     *
     * @return array Collected data
     */
    #[\ReturnTypeWillChange] public function collect(): array
    {
        $this->templateCount = 0;
        $this->blockCount = 0;
        $this->macroCount = 0;
        $this->templates = [];
        $this->computeData($this->profile);

        return [
            'nb_templates' => $this->templateCount,
            'nb_blocks' => $this->blockCount,
            'nb_macros' => $this->macroCount,
            'templates' => $this->templates,
            'accumulated_render_time' => $this->profile->getDuration(),
            'accumulated_render_time_str' => $this->getDataFormatter()->formatDuration($this->profile->getDuration()),
            'memory_usage_str' => $this->getDataFormatter()->formatBytes($this->profile->getMemoryUsage()),
            'callgraph' => $this->getHtmlCallGraph(),
            'badge' => implode(
                '/',
                [
                    $this->templateCount,
                    $this->blockCount,
                    $this->macroCount,
                ]
            ),
        ];
    }

    /**
     * Returns the unique name of the collector
     */
    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'twig';
    }

    #[\ReturnTypeWillChange] public function getHtmlCallGraph(): string
    {
        $dumper = new HtmlDumper();
        return $dumper->dump($this->profile);
    }

    /**
     * Get an Xdebug Link to a file
     *
     * @return array {
     * @var string url
     * @var bool ajax
     * }
     */
    #[\ReturnTypeWillChange]
    #[\Override] public function getXdebugLink($template, $line = 1)
    {
        if (is_null($this->loader)) {
            return null;
        }

        $file = $this->loader->getSourceContext($template)->getPath();

        return parent::getXdebugLink($file, $line);
    }

    private function computeData(Profile $profile): void
    {
        $this->templateCount += ($profile->isTemplate() ? 1 : 0);
        $this->blockCount += ($profile->isBlock() ? 1 : 0);
        $this->macroCount += ($profile->isMacro() ? 1 : 0);
        if ($profile->isTemplate()) {
            $this->templates[] = [
                'name' => $profile->getName(),
                'render_time' => $profile->getDuration(),
                'render_time_str' => $this->getDataFormatter()->formatDuration($profile->getDuration()),
                'memory_str' => $this->getDataFormatter()->formatBytes($profile->getMemoryUsage()),
                'xdebug_link' => $this->getXdebugLink($profile->getTemplate()),
            ];
        }

        foreach ($profile as $p) {
            $this->computeData($p);
        }
    }
}
