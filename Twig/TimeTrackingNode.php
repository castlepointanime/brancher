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

/**
 * A Twig node that is inserted into the template compilation process
 * in order to capture the list of assets that were used during compilation,
 * and storing them in the template
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class TimeTrackingNode extends \Twig_Node
{
    /**
     * Get the list of assets from the compiler and store them in a property
     *
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        if ($compiler instanceof TimeTrackingCompiler) {
            $compiler->write('protected $assets = ');
            $compiler->repr($compiler->getAssets());
            $compiler->raw(";\n");
        }
    }
}
