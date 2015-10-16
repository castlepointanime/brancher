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

namespace CastlePointAnime\Brancher\Extension;

use CastlePointAnime\Brancher\BrancherEvents;
use CastlePointAnime\Brancher\Event\DirectoryEnterEvent;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Webnium\JsonPointer\ArrayAccessor;

/**
 * Extension that will dynamically generate files in a directory based on
 * a DataIterator
 *
 * In the directory configuration, the extension takes a "source"
 * argument, which determines the source DataIterator.
 *
 * @package CastlePointAnime\Brancher\Extension
 */
class PostGeneratorExtension implements BrancherExtensionInterface
{
    /** @var \Webnium\JsonPointer\ArrayAccessor JSON pointer resolution service */
    private $jsonPointerResolver;

    /**
     * Constructor
     *
     * @param \Webnium\JsonPointer\ArrayAccessor $jsonPointerResolver
     */
    public function __construct(ArrayAccessor $jsonPointerResolver)
    {
        $this->jsonPointerResolver = $jsonPointerResolver;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            BrancherEvents::DIRECTORY_ENTER => 'generateFiles',
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'generator';
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('generator');

        /** @noinspection PhpUndefinedMethodInspection */
        $root
            ->children()
                ->scalarNode('source')->end()
            ->end();

        return $builder;
    }

    /**
     * For each directory, generate the necessary files based on the source iterator
     * specified in the directory configuration
     *
     * @param \CastlePointAnime\Brancher\Event\DirectoryEnterEvent $event
     */
    public function generateFiles(DirectoryEnterEvent $event)
    {
        if (!isset($event->getConfig()[$this->getName()])) {
            return;
        }

        $config = $event->getConfig()[$this->getName()];
        /** @var \CastlePointAnime\Brancher\Twig\DataIterator $iterator */
        $iterator = $this->jsonPointerResolver->get(
            $config['source'],
            $event->getBrancher()->getGlobalContext()
        );

        foreach ($iterator as $source) {
            $event->getBrancher()->renderFile(
                $source->getTemplate(),
                $event->getPath()->getRelativePathname() . '/' . $source->getFilename(),
                $source->getData()
            );
        }
    }
}
