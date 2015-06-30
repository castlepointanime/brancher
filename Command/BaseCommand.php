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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Base command providing common functionality for brancher commands
 *
 * @package CastlePointAnime\Brancher\Command
 */
abstract class BaseCommand extends Command
{
    use ContainerAwareTrait;

    /**
     * Constructor
     *
     * Adds common config option for specifying a configuration file
     */
    public function __construct()
    {
        parent::__construct();

        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Specify a configuration file to read from',
            '_config.yml'
        );
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer() {
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
}
