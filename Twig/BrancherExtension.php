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
use Symfony\Component\Filesystem\Filesystem;

/**
 * Twig extension for brancher-specific functionality
 *
 * Extension for Twig templates that provides access to functionality
 * exposed by this application, e.g., site information parsed from a
 * configuration file
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class BrancherExtension extends \Twig_Extension
{
    /**
     * @var array Generic information about the site
     */
    private $site;

    /**
     * @var \Iterator Data available to the templates
     */
    private $data;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem Filesystem service
     * @param \Mni\FrontYAML\Parser $parser Front YAML parser
     * @param array $site Generic information about the site
     * @param array $dataDirs Array of directories to collect data from
     */
    public function __construct(Filesystem $filesystem, Parser $parser, array $site, array $dataDirs)
    {
        $this->site = $site;

        $this->data = new AppendDataIterator();
        foreach ($dataDirs as $dataDir) {
            $dataDir = rtrim($dataDir, '/') . '/';
            if (is_executable($dataDir)) {
                $this->data->append(new DataIterator($filesystem, $parser, $dataDir, ''));
            }
        }
    }

    /**
     * Get name of Twig extension
     *
     * @return string
     */
    public function getName()
    {
        return 'brancher';
    }

    /**
     * Get global variables to be exposed to scripts
     *
     * @return array Mapping of global variable name to value
     */
    public function getGlobals()
    {
        return [
            'site' => $this->site,
            'data' => $this->data,
        ];
    }
}
