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
use CastlePointAnime\Brancher\DependencyInjection\ExtensionCompilerPass;
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
                'special',
                's',
                InputOption::VALUE_REQUIRED,
                'Specify a filename for special files that brancher loads ' .
                'configuration from (defaults to .brancher.yml)'
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
                'Root directory at which to start rendering'
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
        $containerBuilder->addCompilerPass(new ExtensionCompilerPass());
        $containerBuilder->addCompilerPass(
            new RegisterListenersPass(
                'event_dispatcher',
                'brancher.event_listener',
                'brancher.event_subscriber'
            )
        );

        // Try and load config file
        $locator = new FileLocator([getcwd(), $input->getArgument('root'), __DIR__ . '/../',]);
        /** @var \Symfony\Component\DependencyInjection\Loader\FileLoader $loader */
        $loader = new DelegatingLoader(new LoaderResolver([
            new YamlFileLoader($containerBuilder, $locator),
            new XmlFileLoader($containerBuilder, $locator),
            new PhpFileLoader($containerBuilder, $locator),
            new IniFileLoader($containerBuilder, $locator)
        ]));

        $config = null;
        try {
            $config = $locator->locate($input->getOption('config'));
            $loader->load($input->getOption('config'));
        } catch (\Exception $ex) {
            // Only rethrow if the issue was with the user-provided value
            if ($input->getOption('config') !== '_config.yml') {
                throw $ex;
            }
        }

        // Add in final config from command line options
        $containerBuilder->setParameter('castlepointanime.brancher.build.config', $config);
        $containerBuilder->loadFromExtension($extension->getAlias(), [
            'build' => array_filter([
                'config' => dirname($config) ?: $root,
                'root' => $root,
                'special' => $input->getOption('special'),
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
        /** @var \CastlePointAnime\Brancher\Brancher $brancher */
        $brancher = $this->container->get('brancher');
        $brancher->build();
    }
}
