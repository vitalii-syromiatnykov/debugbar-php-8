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

use DebugBar\DataCollector\TimeDataCollector;
use Twig_CompilerInterface;
use Twig_Environment;
use Twig_ExtensionInterface;
use Twig_LexerInterface;
use Twig_LoaderInterface;
use Twig_NodeInterface;
use Twig_NodeVisitorInterface;
use Twig_ParserInterface;
use Twig_TokenParserInterface;
use Twig_TokenStream;

/**
 * Wrapped a Twig Environment to provide profiling features
 *
 * @deprecated
 */
class TraceableTwigEnvironment extends Twig_Environment
{
    protected $renderedTemplates = [];

    #[\ReturnTypeWillChange]
    public function __construct(protected \Twig_Environment $twig, protected ?TimeDataCollector $timeDataCollector = null)
    {
    }

    #[\ReturnTypeWillChange] public function __call($name, $arguments)
    {
        return call_user_func_array([$this->twig, $name], $arguments);
    }

    #[\ReturnTypeWillChange] public function getRenderedTemplates()
    {
        return $this->renderedTemplates;
    }

    #[\ReturnTypeWillChange] public function addRenderedTemplate(array $info): void
    {
        $this->renderedTemplates[] = $info;
    }

    #[\ReturnTypeWillChange] public function getTimeDataCollector()
    {
        return $this->timeDataCollector;
    }

    #[\ReturnTypeWillChange] public function getBaseTemplateClass()
    {
        return $this->twig->getBaseTemplateClass();
    }

    #[\ReturnTypeWillChange] public function setBaseTemplateClass($class): void
    {
        $this->twig->setBaseTemplateClass($class);
    }

    #[\ReturnTypeWillChange] public function enableDebug(): void
    {
        $this->twig->enableDebug();
    }

    #[\ReturnTypeWillChange] public function disableDebug(): void
    {
        $this->twig->disableDebug();
    }

    #[\ReturnTypeWillChange] public function isDebug()
    {
        return $this->twig->isDebug();
    }

    #[\ReturnTypeWillChange] public function enableAutoReload(): void
    {
        $this->twig->enableAutoReload();
    }

    #[\ReturnTypeWillChange] public function disableAutoReload(): void
    {
        $this->twig->disableAutoReload();
    }

    #[\ReturnTypeWillChange] public function isAutoReload()
    {
        return $this->twig->isAutoReload();
    }

    #[\ReturnTypeWillChange] public function enableStrictVariables(): void
    {
        $this->twig->enableStrictVariables();
    }

    #[\ReturnTypeWillChange] public function disableStrictVariables(): void
    {
        $this->twig->disableStrictVariables();
    }

    #[\ReturnTypeWillChange] public function isStrictVariables()
    {
        return $this->twig->isStrictVariables();
    }

    #[\ReturnTypeWillChange] public function getCache($original = true)
    {
        return $this->twig->getCache($original);
    }

    #[\ReturnTypeWillChange] public function setCache($cache): void
    {
        $this->twig->setCache($cache);
    }

    #[\ReturnTypeWillChange] public function getCacheFilename($name)
    {
        return $this->twig->getCacheFilename($name);
    }

    #[\ReturnTypeWillChange] public function getTemplateClass($name, $index = null)
    {
        return $this->twig->getTemplateClass($name, $index);
    }

    #[\ReturnTypeWillChange] public function getTemplateClassPrefix()
    {
        return $this->twig->getTemplateClassPrefix();
    }

    #[\ReturnTypeWillChange] public function render($name, array $context = [])
    {
        return $this->loadTemplate($name)->render($context);
    }

    #[\ReturnTypeWillChange] public function display($name, array $context = []): void
    {
        $this->loadTemplate($name)->display($context);
    }

    #[\ReturnTypeWillChange] public function loadTemplate($name, $index = null)
    {
        $cls = $this->twig->getTemplateClass($name, $index);

        if (isset($this->twig->loadedTemplates[$cls])) {
            return $this->twig->loadedTemplates[$cls];
        }

        if (!class_exists($cls, false)) {
            if (false === $cache = $this->getCacheFilename($name)) {
                eval('?>'.$this->compileSource($this->getLoader()->getSource($name), $name));
            } else {
                if (!is_file($cache) || ($this->isAutoReload() && !$this->isTemplateFresh($name, filemtime($cache)))) {
                    $this->writeCacheFile($cache, $this->compileSource($this->getLoader()->getSource($name), $name));
                }

                require_once $cache;
            }
        }

        if (!$this->twig->runtimeInitialized) {
            $this->initRuntime();
        }

        return $this->twig->loadedTemplates[$cls] = new TraceableTwigTemplate($this, new $cls($this));
    }

    #[\ReturnTypeWillChange] public function isTemplateFresh($name, $time)
    {
        return $this->twig->isTemplateFresh($name, $time);
    }

    #[\ReturnTypeWillChange] public function resolveTemplate($names)
    {
        return $this->twig->resolveTemplate($names);
    }

    #[\ReturnTypeWillChange] public function clearTemplateCache(): void
    {
        $this->twig->clearTemplateCache();
    }

    #[\ReturnTypeWillChange] public function clearCacheFiles(): void
    {
        $this->twig->clearCacheFiles();
    }

    #[\ReturnTypeWillChange] public function getLexer()
    {
        return $this->twig->getLexer();
    }

    #[\ReturnTypeWillChange] public function setLexer(Twig_LexerInterface $lexer): void
    {
        $this->twig->setLexer($lexer);
    }

    #[\ReturnTypeWillChange] public function tokenize($source, $name = null)
    {
        return $this->twig->tokenize($source, $name);
    }

    #[\ReturnTypeWillChange] public function getParser()
    {
        return $this->twig->getParser();
    }

    #[\ReturnTypeWillChange] public function setParser(Twig_ParserInterface $parser): void
    {
        $this->twig->setParser($parser);
    }

    #[\ReturnTypeWillChange] public function parse(Twig_TokenStream $tokens)
    {
        return $this->twig->parse($tokens);
    }

    #[\ReturnTypeWillChange] public function getCompiler()
    {
        return $this->twig->getCompiler();
    }

    #[\ReturnTypeWillChange] public function setCompiler(Twig_CompilerInterface $compiler): void
    {
        $this->twig->setCompiler($compiler);
    }

    #[\ReturnTypeWillChange] public function compile(Twig_NodeInterface $node)
    {
        return $this->twig->compile($node);
    }

    #[\ReturnTypeWillChange] public function compileSource($source, $name = null)
    {
        return $this->twig->compileSource($source, $name);
    }

    #[\ReturnTypeWillChange] public function setLoader(Twig_LoaderInterface $loader): void
    {
        $this->twig->setLoader($loader);
    }

    #[\ReturnTypeWillChange] public function getLoader()
    {
        return $this->twig->getLoader();
    }

    #[\ReturnTypeWillChange] public function setCharset($charset): void
    {
        $this->twig->setCharset($charset);
    }

    #[\ReturnTypeWillChange] public function getCharset()
    {
        return $this->twig->getCharset();
    }

    #[\ReturnTypeWillChange] public function initRuntime(): void
    {
        $this->twig->initRuntime();
    }

    #[\ReturnTypeWillChange] public function hasExtension($name)
    {
        return $this->twig->hasExtension($name);
    }

    #[\ReturnTypeWillChange] public function getExtension($name)
    {
        return $this->twig->getExtension($name);
    }

    #[\ReturnTypeWillChange] public function addExtension(Twig_ExtensionInterface $extension): void
    {
        $this->twig->addExtension($extension);
    }

    #[\ReturnTypeWillChange] public function removeExtension($name): void
    {
        $this->twig->removeExtension($name);
    }

    #[\ReturnTypeWillChange] public function setExtensions(array $extensions): void
    {
        $this->twig->setExtensions($extensions);
    }

    #[\ReturnTypeWillChange] public function getExtensions()
    {
        return $this->twig->getExtensions();
    }

    #[\ReturnTypeWillChange] public function addTokenParser(Twig_TokenParserInterface $parser): void
    {
        $this->twig->addTokenParser($parser);
    }

    #[\ReturnTypeWillChange] public function getTokenParsers()
    {
        return $this->twig->getTokenParsers();
    }

    #[\ReturnTypeWillChange] public function getTags()
    {
        return $this->twig->getTags();
    }

    #[\ReturnTypeWillChange] public function addNodeVisitor(Twig_NodeVisitorInterface $visitor): void
    {
        $this->twig->addNodeVisitor($visitor);
    }

    #[\ReturnTypeWillChange] public function getNodeVisitors()
    {
        return $this->twig->getNodeVisitors();
    }

    #[\ReturnTypeWillChange] public function addFilter($name, $filter = null): void
    {
        $this->twig->addFilter($name, $filter);
    }

    #[\ReturnTypeWillChange] public function getFilter($name)
    {
        return $this->twig->getFilter($name);
    }

    #[\ReturnTypeWillChange] public function registerUndefinedFilterCallback($callable): void
    {
        $this->twig->registerUndefinedFilterCallback($callable);
    }

    #[\ReturnTypeWillChange] public function getFilters()
    {
        return $this->twig->getFilters();
    }

    #[\ReturnTypeWillChange] public function addTest($name, $test = null): void
    {
        $this->twig->addTest($name, $test);
    }

    #[\ReturnTypeWillChange] public function getTests()
    {
        return $this->twig->getTests();
    }

    #[\ReturnTypeWillChange] public function getTest($name)
    {
        return $this->twig->getTest($name);
    }

    #[\ReturnTypeWillChange] public function addFunction($name, $function = null): void
    {
        $this->twig->addFunction($name, $function);
    }

    #[\ReturnTypeWillChange] public function getFunction($name)
    {
        return $this->twig->getFunction($name);
    }

    #[\ReturnTypeWillChange] public function registerUndefinedFunctionCallback($callable): void
    {
        $this->twig->registerUndefinedFunctionCallback($callable);
    }

    #[\ReturnTypeWillChange] public function getFunctions()
    {
        return $this->twig->getFunctions();
    }

    #[\ReturnTypeWillChange] public function addGlobal($name, $value): void
    {
        $this->twig->addGlobal($name, $value);
    }

    #[\ReturnTypeWillChange] public function getGlobals()
    {
        return $this->twig->getGlobals();
    }

    #[\ReturnTypeWillChange] public function mergeGlobals(array $context)
    {
        return $this->twig->mergeGlobals($context);
    }

    #[\ReturnTypeWillChange] public function getUnaryOperators()
    {
        return $this->twig->getUnaryOperators();
    }

    #[\ReturnTypeWillChange] public function getBinaryOperators()
    {
        return $this->twig->getBinaryOperators();
    }

    #[\ReturnTypeWillChange] public function computeAlternatives($name, $items)
    {
        return $this->twig->computeAlternatives($name, $items);
    }
}
