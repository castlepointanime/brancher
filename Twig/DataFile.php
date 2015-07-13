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

use Mni\FrontYAML\Parser;
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
     * @var \Mni\FrontYAML\Parser Front YAML parser service
     */
    private $parser;

    /**
     * @var array|null Arbitrary data loaded from the front YAML
     * @warning This is loaded dynamically; call getData() instead
     */
    private $data = null;

    /**
     * Constructor
     *
     * @param \Mni\FrontYAML\Parser $parser Front YAML parser
     * @param string $pathname
     * @param string $relPathname
     */
    public function __construct(Parser $parser, $pathname, $relPathname)
    {
        parent::__construct($pathname, dirname($relPathname), $relPathname);
        $this->parser = $parser;
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

    /**
     * Get the data from the front YAML, and load it if not loaded already
     *
     * @return array|null
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->parser->parse($this->getContents(), false)->getYAML();
        }

        return $this->data;
    }
}
