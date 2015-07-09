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

use CastlePointAnime\Brancher\DependencyInjection\BrancherExtension;
use Symfony\Component\Config\FileLocator;
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
use Symfony\Component\Finder\Finder;

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
                'Specify directories to collect data from',
                ['_data']
            )
            ->addOption(
                'template-dir',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Directories to look for templates in',
                ['_templates']
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
                'Output directory to build the website into',
                '_site'
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
        $containerBuilder = new ContainerBuilder();
        $extension = new BrancherExtension();
        $containerBuilder->registerExtension($extension);
        $containerBuilder->loadFromExtension($extension->getAlias());

        $root = $input->getArgument('root');
        chdir($root);
        $containerBuilder->setParameter('castlepointanime.brancher.build.data', $input->getOption('data-dir'));
        $containerBuilder->setParameter(
            'castlepointanime.brancher.build.includes',
            array_filter(
                array_merge([$root], array_map('realpath', $input->getOption('template-dir'))),
                'is_executable'
            )
        );

        // Try and load config file
        $locator = new FileLocator([
            $input->getArgument('root'),
            getcwd(),
            __DIR__.'/../',
        ]);

        $config = $input->getOption('config');
        /** @var \Symfony\Component\DependencyInjection\Loader\FileLoader $loader */
        $loader = null;
        switch (substr($config, strrpos($config, '.') + 1)) {
            case 'yml':
                $loader = new YamlFileLoader($containerBuilder, $locator);
                break;

            case 'xml':
                $loader = new XmlFileLoader($containerBuilder, $locator);
                break;

            case 'php':
                $loader = new PhpFileLoader($containerBuilder, $locator);
                break;

            case 'ini':
                $loader = new IniFileLoader($containerBuilder, $locator);
                break;

            default:
                throw new \RuntimeException('Invalid type of configuration file (only yml, php, xml, ini)');
        }

        try {
            $loader->load($config);
        } catch (\Exception $ex) {
            // Only rethrow if the issue was with the user-provided value
            if ($config !== '_config.yml') {
                throw $ex;
            }
        }

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
        /** @var \Twig_Environment $twig */
        $twig = $this->container->get('twig');

        // Find all files in root directory
        $root = $input->getArgument('root');
        $renderFinder = new Finder();
        $renderFinder
            ->files()
            ->in($root)
            ->exclude($filesystem->makePathRelative($input->getOption('config'), $root))
            ->exclude($input->getOption('template-dir'))
            ->exclude($input->getOption('data-dir'))
            ->exclude($input->getOption('exclude'))
            ->exclude($input->getArgument('output'));
        array_map(
            [$renderFinder, 'notPath'],
            $this->container->getParameter('castlepointanime.brancher.build.excludes')
        );

        $outputDir = $input->getArgument('output');
        // Render every file and dump to output
        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($renderFinder as $fileInfo) {
            $rendered = $twig->render($fileInfo->getRelativePathname());

            // Additional rendering for certain file types
            switch ($fileInfo->getExtension()) {
                case 'md':
                case 'markdown':
                    $rendered = $mdParser->parse($rendered);
                    break;
            }

            // Output to final file
            $outputFilename = "$outputDir/{$fileInfo->getRelativePathname()}";
            $filesystem->dumpFile($outputFilename, $rendered);
        }
    }
}
