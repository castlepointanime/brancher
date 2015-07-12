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

use Symfony\Component\Finder\SplFileInfo;

/**
 * Wrapper for an SplFileInfo object that provides additional convenience
 * functions
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class DataFile extends SplFileInfo
{
    /**
     * Constructor
     *
     * @param string $pathname
     * @param string $relPathname
     */
    public function __construct($pathname, $relPathname)
    {
        parent::__construct($pathname, dirname($relPathname), $relPathname);
    }

    /**
     * Get name of the template for this file
     *
     * Sometimes you may want to render a data file's contents as
     * a Twig template. This function returns the namespace and path
     * for retrieving this file as a template
     *
     * @return string
     */
    public function getTemplate()
    {
        return "@data/{$this->getRelativePathname()}";
    }
}
