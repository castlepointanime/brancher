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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that adds tagged Brancher extensions into the brancher
 * service
 *
 * @package CastlePointAnime\Brancher\DependencyInjection
 */
class ExtensionCompilerPass implements CompilerPassInterface
{
    /**
     * Add all services tagged with brancher.extension to the brancher service
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('brancher')) {
            return;
        }

        $brancherDef = $container->findDefinition('brancher');
        foreach (array_keys($container->findTaggedServiceIds('brancher.extension')) as $id) {
            $brancherDef->addMethodCall('registerExtension', [new Reference($id)]);
        }
    }
}
