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

namespace CastlePointAnime\Brancher\Twig;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Extension\Twig\AsseticNode;

/**
 * Twig compiler that keeps track of Assetic assets a template uses
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class TimeTrackingCompiler extends \Twig_Compiler
{
    /** @var string[] */
    private $assets;

    /**
     * @param \Twig_NodeInterface $node
     * @param int $indentation
     *
     * @return $this
     */
    public function compile(
        /** @noinspection PhpDeprecationInspection */
        \Twig_NodeInterface $node,
        $indentation = 0
    ) {
        $this->assets = [];
        if ($node instanceof \Twig_Node_Module) {
            $node->setNode('class_end', new TimeTrackingNode());
        }
        parent::compile($node, $indentation);
        $this->findAssets($node);

        return $this;
    }

    /**
     * @param \Twig_NodeInterface $node
     * @param bool|true $raw
     *
     * @return $this
     */
    public function subcompile(
        /** @noinspection PhpDeprecationInspection */
        \Twig_NodeInterface $node,
        $raw = true
    ) {
        $this->findAssets($node);
        parent::subcompile($node, $raw);

        return $this;
    }

    /**
     * Get the list of assets for the last compilation
     *
     * @return \Assetic\Extension\Twig\AsseticNode[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Find Assetic assets the template uses in the node tree
     *
     * @param \Twig_NodeInterface $node
     */
    private function findAssets(
        /** @noinspection PhpDeprecationInspection */
        \Twig_NodeInterface $node
    ) {
        if ($node instanceof AsseticNode) {
            $this->addAsset($node->getAttribute('asset'));
        }
        foreach ($node as $subnode) {
            if ($subnode !== null) {
                $this->findAssets($subnode);
            }
        }
    }

    /**
     * Add the paths for an Asset or AssetCollection to the list
     * of assets
     *
     * @param \Assetic\Asset\AssetInterface $asset
     */
    protected function addAsset(AssetInterface $asset)
    {
        if ($asset instanceof AssetCollection) {
            array_map([$this, 'addAsset'], $asset->all());
        } else {
            $this->assets[] = $asset->getSourceRoot() . '/' . $asset->getSourcePath();
        }
    }
}
