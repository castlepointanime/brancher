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
 * The brancher.render event is thrown when a template is about to be rendered.
 */
class RenderEvent extends Event
{
    /**
     * @var \Symfony\Component\Finder\SplFileInfo File being rendered
     */
    private $file;

    /**
     * @var \Twig_Template Template for the file being rendered
     */
    private $template;

    /**
     * @var array Rendering context to be passed to twig
     */
    public $context;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param \Twig_TemplateInterface $template
     * @param array $context
     */
    public function __construct(
        SplFileInfo $file,
        /** @noinspection PhpDeprecationInspection */
        \Twig_TemplateInterface $template,
        array $context
    ) {
        $this->file = $file;
        $this->template = $template;
        $this->context = $context;
    }

    /**
     * Get the File object being rendered
     *
     * @return \Symfony\Component\Finder\SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get the template object being rendered
     *
     * @return \Twig_Template
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
