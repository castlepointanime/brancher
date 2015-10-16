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
use Symfony\Component\Finder\SplFileInfo;

/**
 * Event that occurs when the builder enters a directory
 *
 * @package CastlePointAnime\Brancher\Event
 */
class DirectoryEnterEvent extends BrancherEvent
{
    /** @var bool Whether the directory should be skipped */
    private $shouldSkip = false;

    /** @var SplFileInfo Path of the directory being entered */
    private $path;

    /** @var array Directory configuration */
    private $config;

    /**
     * Constructor
     *
     * @param \CastlePointAnime\Brancher\Brancher $brancher
     * @param $path
     * @param $config
     */
    public function __construct(Brancher $brancher, SplFileInfo $path, $config)
    {
        parent::__construct($brancher);
        $this->path = $path;
        $this->config = $config;
    }

    /**
     * Get whether all files in this directory should be skipped
     *
     * @return bool
     */
    public function isShouldSkip()
    {
        return $this->shouldSkip;
    }

    /**
     * Return the configuration for the directory
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the path being entered
     *
     * @return SplFileInfo
     */
    public function getPath()
    {
        return $this->path;
    }
}
