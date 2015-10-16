<?php
/**
 * This file is part of brancher, a static site generation tool
 * Copyright (C) 2015  Tyler Romeo <tylerromeo@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CastlePointAnime\Brancher;

use Assetic\AssetWriter;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\LazyAssetManager;
use CastlePointAnime\Brancher\Event\DirectoryEnterEvent;
use CastlePointAnime\Brancher\Event\OldFileEvent;
use CastlePointAnime\Brancher\Event\RenderEvent;
use CastlePointAnime\Brancher\Event\SetupEvent;
use CastlePointAnime\Brancher\Event\TeardownEvent;
use CastlePointAnime\Brancher\Extension\BrancherExtensionInterface;
use Mni\FrontYAML\YAML\YAMLParser;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Core build service that renders files for a site and outputs them
 * to the output directory
 *
 * @package CastlePointAnime\Brancher
 */
class Brancher
{
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    private $filesystem;
    /** @var \Twig_Environment $twig */
    private $twig;
    /** @var \finfo $finfo */
    private $finfo;
    /** @var \Assetic\Factory\LazyAssetManager $manager */
    private $manager;
    /** @var \Assetic\AssetWriter $writer */
    private $writer;
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
    private $dispatcher;
    /** @var \Mni\FrontYAML\YAML\YAMLParser YAML parser */
    private $yaml;

    /** @var string Root directory to start rendering from */
    private $root = '';
    /** @var string Output directory to store site in */
    private $outputDir = '';
    /** @var string Filename for special files */
    private $specialFile = '';
    /** @var string[] Directories to exclude from rendering */
    private $excludes = [];

    /** @var Extension\BrancherExtensionInterface[] */
    private $extensions;

    /**
     * Constructor (dependency injection)
     *
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param \Twig_Environment $twig
     * @param \finfo $finfo
     * @param \Assetic\Factory\LazyAssetManager $manager
     * @param \Assetic\AssetWriter $writer
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     * @param \Mni\FrontYAML\YAML\YAMLParser $yaml
     */
    public function __construct(
        Filesystem $filesystem,
        \Twig_Environment $twig,
        \finfo $finfo,
        LazyAssetManager $manager,
        AssetWriter $writer,
        EventDispatcherInterface $dispatcher,
        YAMLParser $yaml
    ) {
        $this->filesystem = $filesystem;
        $this->twig = $twig;
        $this->finfo = $finfo;
        $this->manager = $manager;
        $this->writer = $writer;
        $this->dispatcher = $dispatcher;
        $this->yaml = $yaml;
    }

    /**
     * Set the root directory from which to render
     *
     * The root directory is what is iterated over when creating files in
     * the output directory.
     *
     * @param string $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * Set the output directory into which files are rendered
     *
     * @warning Files not associated with the site will be cleared from
     * this directory upon build
     *
     * @param string $outpuDir
     */
    public function setOutputDirectory($outpuDir)
    {
        $this->outputDir = $outpuDir;
    }

    /**
     * Set the filename to look for in each directory for directory-specific
     * configuration
     *
     * Directory-specific configuration can be stored in a YAML file in any
     * directory, for processing by Brancher extensions.
     *
     * @param string $specialFile Filename for special files
     */
    public function setSpecialFilename($specialFile)
    {
        $this->specialFile = $specialFile;
    }

    /**
     * Add a directory to exclude from iterating over when rendering
     *
     * @param string $directory
     */
    public function addExclude($directory)
    {
        $this->excludes[] = $directory;
    }

    /**
     * Add multiple directories to exclude from iterating over when rendering
     *
     * @param string[] $directories
     */
    public function addExcludes(array $directories)
    {
        $this->excludes = array_merge($this->excludes, $directories);
    }

    /**
     * Set the directories (and erase previous ones) to exclude from iterating
     * over when rendering
     *
     * @param string[] $directories
     */
    public function setExcludes(array $directories)
    {
        $this->excludes = $directories;
    }

    /**
     * Register an extension with Brancher for receiving events and configuration
     * from directories
     *
     * @param \CastlePointAnime\Brancher\Extension\BrancherExtensionInterface $ext
     */
    public function registerExtension(BrancherExtensionInterface $ext)
    {
        $this->extensions[$ext->getName()] = $ext;
        $this->dispatcher->addSubscriber($ext);
    }

    /**
     * Build the site
     */
    public function build()
    {
        $that = $this;
        $this->root = realpath($this->root);

        // First, clean up non-existent files
        if (file_exists($this->outputDir)) {
            $deleteFinder = new Finder();
            $deleteFinder->in($this->outputDir)->filter(function (SplFileInfo $dstFile) {
                // Filter out entries where the source does not exist, or is not the same type
                $srcFile = new SplFileInfo(
                    "{$this->root}/{$dstFile->getRelativePathname()}",
                    $dstFile->getRelativePath(),
                    $dstFile->getRelativePathname()
                );
                $old = $dstFile->isDir() && !$srcFile->isDir()
                    || $dstFile->isFile() && !$srcFile->isFile();

                $event = new OldFileEvent($this, $srcFile, $dstFile, $old);
                $this->dispatcher->dispatch(BrancherEvents::OLDFILE, $event);

                return $event->isOld();
            });
            $this->filesystem->remove($deleteFinder);
        }

        // Find all files in root directory
        $renderFinder = new Finder();
        $renderFinder->files()->in(realpath($this->root))->ignoreDotFiles(false);
        array_map(
            [$renderFinder, 'notPath'],
            array_filter(
                array_map(
                    function ($exclude) use ($that) {
                        return $this->filesystem->makePathRelative(
                            realpath($exclude),
                            $that->root
                        );
                    },
                    array_merge(
                        $this->excludes,
                        [$this->outputDir]
                    )
                )
            )
        );

        $this->dispatcher->dispatch(BrancherEvents::SETUP, new SetupEvent($this, $renderFinder));

        // Render every file and dump to output
        $directoryVisited = [];
        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($renderFinder as $fileInfo) {
            $path = $fileInfo->getRelativePath();
            if (!isset($directoryVisited[$path])) {
                $pathObj = new SplFileInfo(
                    $fileInfo->getPath(),
                    basename($fileInfo->getRelativePath()),
                    $fileInfo->getRelativePath()
                );
                $event = new DirectoryEnterEvent($this, $pathObj, $this->getSpecialConfig($fileInfo->getPath()));
                $this->dispatcher->dispatch(BrancherEvents::DIRECTORY_ENTER, $event);
                $directoryVisited[$path] = !$event->isShouldSkip();
            }
            if ($directoryVisited[$fileInfo->getRelativePath()] === false) {
                continue;
            }
            if ($fileInfo->getFilename() === $this->specialFile) {
                continue;
            }

            if (substr($this->finfo->file($fileInfo->getPathname()), 0, 4) === 'text') {
                $this->renderFile(
                    $fileInfo->getRelativePathname(),
                    $fileInfo->getRelativePathname(),
                    ['path' => $fileInfo->getRelativePathname()]
                );
            } else {
                // Dump binary files verbatim into output directory
                $this->filesystem->copy(
                    $fileInfo->getPathname(),
                    "{$this->outputDir}/{$fileInfo->getRelativePathname()}"
                );
            }
        }

        $this->writer->writeManagerAssets($this->manager);

        $this->dispatcher->dispatch(BrancherEvents::TEARDOWN, new TeardownEvent($this));
    }

    /**
     * Render a single file from a template to an output path
     *
     * @param string $templateName Name of the template for Twig
     * @param string $outputPath Relative path to render to
     * @param array $context Twig context
     */
    public function renderFile($templateName, $outputPath, array $context)
    {
        $template = $this->twig->loadTemplate($templateName);
        $this->manager->addResource(
            new TwigResource($this->twig->getLoader(), $templateName),
            'twig'
        );

        $event = new RenderEvent($this, $template, $context);
        $this->dispatcher->dispatch(BrancherEvents::RENDER, $event);

        $rendered = $template->render($event->context);
        $this->filesystem->dumpFile("{$this->outputDir}/$outputPath", $rendered);
    }

    /**
     * Get the global Twig context
     *
     * @return array
     */
    public function getGlobalContext()
    {
        return $this->twig->getGlobals();
    }

    /**
     * Get and cache resolved configuration data for the given path
     *
     * Starting at the path, and working up the directory tree to the root
     * directory, search for (and cache) configuration data from special files.
     * Then cache and return the final processed configuration for the path.
     *
     * @param string $topPath Original directory to look from
     *
     * @return array Resolved configuration
     */
    private function getSpecialConfig($topPath)
    {
        static $fileCache = [];
        static $configCache = [];

        // Return early if cached
        if (isset($configCache[$topPath])) {
            return $configCache[$topPath];
        }

        $configs = [];

        // Go up the directory tree
        for ($path = $topPath; strpos($path, $this->root) !== false; $path = dirname($path)) {
            $specialFile = "$path/{$this->specialFile}";
            $config = [];

            // Check file cache to avoid multiple YAML parsing
            if (isset($fileCache[$specialFile])) {
                if ($fileCache[$specialFile] !== false) {
                    $config = $fileCache[$specialFile];
                }
            } elseif (file_exists($specialFile)) {
                $fileCache[$specialFile] = $config = $this->yaml->parse(file_get_contents($specialFile));
            } else {
                $fileCache[$specialFile] = false;
            }

            foreach ($config as $key => $value) {
                $configs[$key][] = $value;
            }
        }

        // Process configs arrays
        $processor = new Processor();
        array_walk($configs, function (&$item, $key) use ($processor) {
            $item = $processor->processConfiguration($this->extensions[$key], $item);
        });

        return $configCache[$topPath] = $configs;
    }
}
