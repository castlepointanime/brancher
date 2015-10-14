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
use Symfony\Component\EventDispatcher\Event;

/**
 * Event used by the brancher build tool. Provides access to
 * the Brancher object
 *
 * @package CastlePointAnime\Brancher\Event
 */
class BrancherEvent extends Event
{
    /** @var \CastlePointAnime\Brancher\Brancher Build object */
    private $brancher;

    /**
     * Constructor
     *
     * @param \CastlePointAnime\Brancher\Brancher $brancher
     */
    public function __construct(Brancher $brancher)
    {
        $this->brancher = $brancher;
    }

    /**
     * Get the builder object that triggered the event
     *
     * @return \CastlePointAnime\Brancher\Brancher
     */
    public function getBrancher()
    {
        return $this->brancher;
    }
}
