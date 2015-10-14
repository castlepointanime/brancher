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

namespace CastlePointAnime\Brancher\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Container extension for loading brancher services
 *
 * @package CastlePointAnime\Brancher\DependencyInjection
 */
class BrancherExtension extends Extension
{
    /**
     * Load common services for brancher commands and process configuration
     *
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Add blank scaffolding so we don't have undefined index errors
        array_unshift($configs, [
            'site' => [],
            'build' => [
                'output' => '',
                'special' => '',
                'resources' => '',
                'templates' => [],
                'excludes' => [],
                'data' => [],
            ],
            'twig' => [
                'extensions' => [],
            ],
        ]);

        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        // Add some defaults
        if (!count($config['build']['templates']) && is_dir("{$config['build']['root']}/_templates")) {
            $config['build']['templates'][] = "{$config['build']['root']}/_templates";
        }
        if (!count($config['build']['data']) && is_dir("{$config['build']['root']}/_data")) {
            $config['build']['data'][] = "{$config['build']['root']}/_data";
        }
        if (!$config['build']['resources'] && is_dir("{$config['build']['root']}/_resources")) {
            $config['build']['resources'] = "{$config['build']['root']}/_resources";
        }
        if (!$config['build']['output']) {
            $config['build']['output'] = "{$config['build']['root']}/_site";
        }
        if (!$config['build']['special']) {
            $config['build']['special'] = '.brancher.yml';
        }

        $makeAbsolute = function ($path) use ($config) {
            return !$path || $path[0] === '/'
                ? $path
                : "{$config['build']['config']}/$path";
        };

        $container->getParameterBag()->add([
            'castlepointanime.brancher.site' => $config['site'],
            'castlepointanime.brancher.build.root' => $makeAbsolute($config['build']['root']),
            'castlepointanime.brancher.build.output' => $makeAbsolute($config['build']['output']),
            'castlepointanime.brancher.build.templates' => array_map($makeAbsolute, $config['build']['templates']),
            'castlepointanime.brancher.build.excludes' => array_map($makeAbsolute, $config['build']['excludes']),
            'castlepointanime.brancher.build.resources' => $makeAbsolute($config['build']['resources']),
            'castlepointanime.brancher.build.data' => array_map($makeAbsolute, $config['build']['data']),
            'castlepointanime.brancher.build.special' => $config['build']['special'],
            'castlepointanime.brancher.twig.extensions' => $config['twig']['extensions'],
        ]);
    }

    /**
     * Get directory where XSD file is located
     *
     * @return string
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema/';
    }

    /**
     * Get XML namespace for configuration file
     *
     * @return string
     */
    public function getNamespace()
    {
        return 'http://castlepointanime.com/schema/dic/brancher';
    }
}
