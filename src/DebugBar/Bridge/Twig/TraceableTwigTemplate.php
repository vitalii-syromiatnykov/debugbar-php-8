<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge\Twig;

use Twig_Template;
use Twig_TemplateInterface;

/**
 * Wraps a Twig_Template to add profiling features
 *
 * @deprecated
 */
class TraceableTwigTemplate extends Twig_Template implements Twig_TemplateInterface
{
    #[\ReturnTypeWillChange] public function __construct(TraceableTwigEnvironment $env, protected \Twig_Template $template)
    {
        $this->env = $env;
    }

    #[\ReturnTypeWillChange] public function __call($name, $arguments)
    {
        return call_user_func_array([$this->template, $name], $arguments);
    }

    #[\ReturnTypeWillChange] public function doDisplay(array $context, array $blocks = [])
    {
        return $this->template->doDisplay($context, $blocks);
    }

    #[\ReturnTypeWillChange] public function getTemplateName()
    {
        return $this->template->getTemplateName();
    }

    #[\ReturnTypeWillChange] public function getEnvironment()
    {
        return $this->template->getEnvironment();
    }

    #[\ReturnTypeWillChange] public function getParent(array $context)
    {
        return $this->template->getParent($context);
    }

    #[\ReturnTypeWillChange] public function isTraitable()
    {
        return $this->template->isTraitable();
    }

    #[\ReturnTypeWillChange] public function displayParentBlock($name, array $context, array $blocks = []): void
    {
        $this->template->displayParentBlock($name, $context, $blocks);
    }

    #[\ReturnTypeWillChange] public function displayBlock($name, array $context, array $blocks = [], $useBlocks = true): void
    {
        $this->template->displayBlock($name, $context, $blocks, $useBlocks);
    }

    #[\ReturnTypeWillChange] public function renderParentBlock($name, array $context, array $blocks = [])
    {
        return $this->template->renderParentBlock($name, $context, $blocks);
    }

    #[\ReturnTypeWillChange] public function renderBlock($name, array $context, array $blocks = [], $useBlocks = true)
    {
        return $this->template->renderBlock($name, $context, $blocks, $useBlocks);
    }

    #[\ReturnTypeWillChange] public function hasBlock($name)
    {
        return $this->template->hasBlock($name);
    }

    #[\ReturnTypeWillChange] public function getBlockNames()
    {
        return $this->template->getBlockNames();
    }

    #[\ReturnTypeWillChange] public function getBlocks()
    {
        return $this->template->getBlocks();
    }

    #[\ReturnTypeWillChange] public function display(array $context, array $blocks = []): void
    {
        $start = microtime(true);
        $this->template->display($context, $blocks);
        $end = microtime(true);

        if ($timeDataCollector = $this->env->getTimeDataCollector()) {
            $name = sprintf("twig.render(%s)", $this->template->getTemplateName());
            $timeDataCollector->addMeasure($name, $start, $end);
        }

        $this->env->addRenderedTemplate([
            'name' => $this->template->getTemplateName(),
            'render_time' => $end - $start
        ]);
    }

    #[\ReturnTypeWillChange] public function render(array $context)
    {
        $level = ob_get_level();
        ob_start();
        try {
            $this->display($context);
        } catch (Exception $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        return ob_get_clean();
    }

    public static function clearCache(): void
    {
        Twig_Template::clearCache();
    }
}
