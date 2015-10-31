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
 * Twig template class that tracks its dependencies and can determine if any of the templates
 * have changed since compilation
 *
 * @package CastlePointAnime\Brancher\Twig
 */
abstract class TimeTrackingTemplate extends \Twig_Template
{
    /** @var \Twig_Template[] Templates that this template depends on */
    protected $depenencies;

    /** @var string[] Paths to assets that this template depends on */
    protected $assets;

    /**
     * @param \Twig_Environment $env
     */
    public function __construct(\Twig_Environment $env)
    {
        parent::__construct($env);
        $this->depenencies = [$this];
    }

    /**
     * Get a list of filesystem paths that this template depends upon,
     * including template dependencies, CSS/JS assets, etc.
     *
     * @return array
     */
    public function getPaths()
    {
        $that = $this;

        return array_merge(
            // This template
            [$this->env->getLoader()->getCacheKey($this->getTemplateName())],
            // Dependencies
            array_map(function (\Twig_Template $template) use ($that) {
                return $that->env->getLoader()->getCacheKey($template->getTemplateName());
            }, $this->depenencies),
            // Assets
            $this->assets
        );
    }

    /**
     * Add a template to the list of dependencies after loading it
     *
     * @param \Twig_Template|TimeTrackingTemplate $template
     * @param string $templateName
     * @param int $line
     * @param int $index
     *
     * @return \Twig_Template
     * @throws \Exception
     * @throws \Twig_Error
     */
    protected function loadTemplate($template, $templateName = null, $line = null, $index = null)
    {
        /** @var \Twig_Template|TimeTrackingTemplate $template */
        $template = parent::loadTemplate($template, $templateName, $line, $index);

        $this->depenencies[] = $template;

        if ($template instanceof TimeTrackingTemplate) {
            $this->assets = array_unique(array_merge($this->assets, $template->assets));
        }

        return $template;
    }

    /**
     * Check if a template tries to access data, and record it as
     * a dependency if so
     *
     * @param mixed $object
     * @param mixed $item
     * @param array $arguments
     * @param string $type
     * @param bool|false $isDefinedTest
     * @param bool|false $ignoreStrictCheck
     *
     * @return mixed
     * @throws \Twig_Error_Runtime
     */
    protected function getAttribute(
        $object,
        $item,
        array $arguments = [],
        $type = self::ANY_CALL,
        $isDefinedTest = false,
        $ignoreStrictCheck = false
    ) {
        $obj = parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);

        if ($obj instanceof DataFile) {
            $this->assets[] = $obj->getPathname();
        }

        return $obj;
    }

    /**
     * Check whether a template has changed since a given modification time
     *
     * @param int $mtime The time to check for newer changes
     *
     * @return bool True if changes, false otherwise
     */
    public function isTemplateFresh($mtime)
    {
        return
            !array_filter($this->depenencies, function (\Twig_Template $template) use ($mtime) {
                return !$this->env->isTemplateFresh($template->getTemplateName(), $mtime);
            })
            &&
            !array_filter($this->assets, function ($pathname) use ($mtime) {
                return !file_exists($pathname) || filemtime($pathname) <= $mtime;
            });
    }
}
