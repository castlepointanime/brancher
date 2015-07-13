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

/**
 * Wrapper around Twig's filesystem loader that processes and removes
 * front YAML from the template before giving to Twig to render
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class FrontYamlLoader extends \Twig_Loader_Filesystem
{
    /**
     * @var \Mni\FrontYAML\Parser Front YAML parser service
     */
    private $parser;

    /**
     * Constructor
     *
     * @param \Mni\FrontYAML\Parser $parser
     * @param array $paths
     */
    public function __construct(Parser $parser, $paths = [])
    {
        parent::__construct($paths);
        $this->parser = $parser;
    }

    /**
     * Get the source of the template, without the front YAML
     *
     * @param string $name Name of the template
     *
     * @return string Source code of the template
     */
    public function getSource($name)
    {
        return $this->parser->parse(parent::getSource($name), false)->getContent();
    }
}
