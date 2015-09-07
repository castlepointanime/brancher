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

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Finder\SplFileInfo;

/**
 * The brancher.oldfile event is thrown when an old file in the output directory that no longer
 * exists in the source directory is found.
 */
class OldFileEvent extends Event
{
    /**
     * @var \Symfony\Component\Finder\SplFileInfo File in the root directory
     */
    private $srcFile;

    /**
     * @var \Symfony\Component\Finder\SplFileInfo File in the output directory
     */
    private $dstFile;

    /**
     * @var bool Whether the destination file is old and should be deleted
     */
    private $isOld;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Finder\SplFileInfo $srcFile File in the root directory
     * @param \Symfony\Component\Finder\SplFileInfo $dstFile File in the output directory
     * @param $isOld bool Whether the destination file is old and should be deleted
     */
    public function __construct(SplFileInfo $srcFile, SplFileInfo $dstFile, $isOld)
    {
        $this->srcFile = $srcFile;
        $this->dstFile = $dstFile;
        $this->isOld = $isOld;
    }

    /**
     * Get the file in the root directory
     *
     * @return \Symfony\Component\Finder\SplFileInfo
     */
    public function getSrcFile()
    {
        return $this->srcFile;
    }

    /**
     * Get the file in the output directory
     *
     * @return \Symfony\Component\Finder\SplFileInfo
     */
    public function getDstFile()
    {
        return $this->dstFile;
    }

    /**
     * Get whether the destination file is old and should be deleted
     *
     * @return bool True if old, false otherwise
     */
    public function isOld()
    {
        return $this->isOld;
    }

    /**
     * Set whether the destination file is old and should be deleted
     *
     * @param bool $isOld True to delete, false otherwise
     */
    public function setIsOld($isOld)
    {
        $this->isOld = (bool)$isOld;
    }
}
