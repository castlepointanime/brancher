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

namespace CastlePointAnime\Brancher\Event;

use CastlePointAnime\Brancher\Brancher;
use Symfony\Component\Finder\Finder;

/**
 * The brancher.setup event is thrown once when the file finder has been set
 * up and is about to start rendering.
 */
class SetupEvent extends BrancherEvent
{
    /**
     * @var \Symfony\Component\Finder\Finder Finder to be used to find files
     */
    private $renderFinder;

    /**
     * Constructor
     *
     * @param Brancher $brancher
     * @param \Symfony\Component\Finder\Finder $renderFinder
     */
    public function __construct(Brancher $brancher, Finder $renderFinder)
    {
        parent::__construct($brancher);
        $this->renderFinder = $renderFinder;
    }

    /**
     * Get the Finder object to be used to find source files
     *
     * @return \Symfony\Component\Finder\Finder
     */
    public function getRenderFinder()
    {
        return $this->renderFinder;
    }
}
