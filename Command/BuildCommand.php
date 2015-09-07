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

namespace CastlePointAnime\Brancher\Command;

use Assetic\Extension\Twig\TwigResource;
use CastlePointAnime\Brancher\BrancherEvents;
use CastlePointAnime\Brancher\DependencyInjection\BrancherExtension;
use CastlePointAnime\Brancher\Event\OldFileEvent;
use CastlePointAnime\Brancher\Event\RenderEvent;
use CastlePointAnime\Brancher\Event\SetupEvent;
use CastlePointAnime\Brancher\Event\TeardownEvent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command that builds a site from one directory into another
 *
 * A command where the user provides an input and output directory, and
 * every file with front YAML in the input directory is rendered through
 * Twig, optionally additionally rendered (depending on the file type),
 * and output to the same filename in the output directory.
 *
 * @package CastlePointAnime\Brancher\Command
 */
class BuildCommand extends Command
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build the website into a directory')
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Specify a configuration file to read from',
                '_config.yml'
            )
            ->addOption(
                'data-dir',
                'd',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Specify directories to collect data from (defaults to <root>/_data)'
            )
            ->addOption(
                'template-dir',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Directories to look for templates in (defaults to <root>/_templates)'
            )
            ->addOption(
                'resource-dir',
                'r',
                InputOption::VALUE_REQUIRED,
                'Directory to allow loading resources via Assetic'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Files or directories to exclude from rendering (globs supported)'
            )
            ->addArgument(
                'root',
                InputArgument::OPTIONAL,
                'Root directory at which to start rendering',
                '.'
            )
            ->addArgument(
                'output',
                InputArgument::OPTIONAL,
                'Output directory to build the website into (defaults to <root>/_site)'
            );
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Initialize the service container (and extensions), and load the config file
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \Exception if user-provided configuration file causes an error
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $root = $input->getArgument('root');

        // Setup container
        $containerBuilder = new ContainerBuilder();
        $extension = new BrancherExtension();
        $containerBuilder->registerExtension($extension);
        $containerBuilder->addCompilerPass(
            new RegisterListenersPass(
                'event_dispatcher',
                'brancher.event_listener',
                'brancher.event_subscriber'
            )
        );

        // Try and load config file
        $locator = new FileLocator([$input->getArgument('root'), __DIR__ . '/../',]);
        /** @var \Symfony\Component\DependencyInjection\Loader\FileLoader $loader */
        $loader = new DelegatingLoader(new LoaderResolver([
            new YamlFileLoader($containerBuilder, $locator),
            new XmlFileLoader($containerBuilder, $locator),
            new PhpFileLoader($containerBuilder, $locator),
            new IniFileLoader($containerBuilder, $locator)
        ]));

        try {
            $loader->load($input->getOption('config'));
        } catch (\Exception $ex) {
            // Only rethrow if the issue was with the user-provided value
            if ($input->getOption('config') !== '_config.yml') {
                throw $ex;
            }
        }

        // Add in final config from command line options
        $containerBuilder->loadFromExtension($extension->getAlias(), [
            'build' => array_filter([
                'root' => $root,
                'output' => $input->getArgument('output'),
                'templates' => array_filter(array_map('realpath', $input->getOption('template-dir')), 'is_dir'),
                'data' => $input->getOption('data-dir'),
                'exclude' => $input->getOption('exclude'),
            ]),
        ]);

        $containerBuilder->compile();
        $this->setContainer($containerBuilder);
    }

    /**
     * Build the site based on user input, and output status info
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
        $filesystem = $this->container->get('filesystem');
        /** @var \ParsedownExtra $mdParser */
        $mdParser = $this->container->get('parsedown');
        /** @var \Twig_LoaderInterface $twigLoader */
        $twigLoader = $this->container->get('brancher.twig_loader');
        /** @var \Twig_Environment $twig */
        $twig = $this->container->get('twig');
        /** @var \finfo $finfo */
        $finfo = $this->container->get('finfo');
        /** @var \Assetic\Factory\LazyAssetManager $manager */
        $manager = $this->container->get('brancher.manager');
        /** @var \Assetic\AssetWriter $writer */
        $writer = $this->container->get('brancher.writer');
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');

        $root = $this->container->getParameter('castlepointanime.brancher.build.root');
        $outputDir = $input->getArgument('output');

        // First, clean up non-existent files
        if (file_exists($outputDir)) {
            $deleteFinder = new Finder();
            $deleteFinder->in($outputDir)->filter(function (SplFileInfo $dstFile) use ($root, $dispatcher) {
                // Filter out entries where the source does not exist, or is not the same type
                $srcFile = new SplFileInfo(
                    "$root/{$dstFile->getRelativePathname()}",
                    $dstFile->getRelativePath(),
                    $dstFile->getRelativePathname()
                );
                $old = $dstFile->isDir() && !$srcFile->isDir()
                    || $dstFile->isFile() && !$srcFile->isFile();

                $event = new OldFileEvent($srcFile, $dstFile, $old);
                $dispatcher->dispatch(BrancherEvents::OLDFILE, $event);

                return $event->isOld();
            });
            $filesystem->remove($deleteFinder);
        }

        // Find all files in root directory
        $renderFinder = new Finder();
        $renderFinder->files()->in($root);
        array_map(
            [$renderFinder, 'notPath'],
            array_filter(array_merge(
                $this->container->getParameter('castlepointanime.brancher.build.excludes'),
                $this->container->getParameter('castlepointanime.brancher.build.templates'),
                $this->container->getParameter('castlepointanime.brancher.build.data'),
                [$this->container->getParameter('castlepointanime.brancher.build.output')],
                [$this->container->getParameter('castlepointanime.brancher.build.resources')]
            ))
        );

        $dispatcher->dispatch(BrancherEvents::SETUP, new SetupEvent($renderFinder));

        // Render every file and dump to output
        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($renderFinder as $fileInfo) {
            $outputFilename = "$outputDir/{$fileInfo->getRelativePathname()}";

            if (substr($finfo->file($fileInfo->getPathname()), 0, 4) === 'text') {
                // Render text files
                $template = $twig->loadTemplate($fileInfo->getRelativePathname());
                $manager->addResource(new TwigResource($twigLoader, $template), 'twig');

                $event = new RenderEvent($fileInfo, $template, [
                    'path' => $fileInfo->getRelativePathname(),
                ]);
                $dispatcher->dispatch(BrancherEvents::RENDER, $event);

                $rendered = $template->render($event->context);

                // Additional rendering for certain file types
                switch ($fileInfo->getExtension()) {
                    case 'md':
                    case 'markdown':
                        $rendered = $mdParser->parse($rendered);
                        break;
                }

                // Output to final file
                $filesystem->dumpFile($outputFilename, $rendered);
            } else {
                // Dump binary files verbatim into output directory
                $filesystem->copy($fileInfo->getPathname(), $outputFilename);
            }
        }

        $writer->writeManagerAssets($manager);

        $dispatcher->dispatch(BrancherEvents::TEARDOWN, new TeardownEvent());
    }
}
