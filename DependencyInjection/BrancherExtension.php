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
        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        if (!empty($config['site'])) {
            $container->setParameter('castlepointanime.brancher.site', $config['site']);
        }
        if (!empty($config['build']['excludes'])) {
            $container->setParameter('castlepointanime.brancher.build.excludes', $config['build']['excludes']);
        }
        if (!empty($config['build']['data'])) {
            $container->setParameter('castlepointanime.brancher.build.includes', $config['build']['data']);
        }
        if (!empty($config['twig']['extensions'])) {
            $container->setParameter('castlepointanime.brancher.twig.extensions', $config['twig']['extensions']);
        }
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
