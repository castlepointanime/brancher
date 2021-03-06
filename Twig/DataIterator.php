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
 * Iterator that returns DataFile objects for every file in a directory
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class DataIterator extends \FilesystemIterator implements ArrayAccessIteratorInterface, \Countable
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem Filesystem service
     */
    private $filesystem;

    /**
     * @var \Mni\FrontYAML\Parser Front YAML parser service
     */
    private $parser;

    /**
     * @var string Root directory
     */
    private $root;

    /**
     * @var string Relative path to this directory
     */
    private $path;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem Filesystem service
     * @param \Mni\FrontYAML\Parser $parser Front YAML parser
     * @param string $root Root directory
     * @param string $path Path to recurse through
     */
    public function __construct(Filesystem $filesystem, Parser $parser, $root, $path)
    {
        parent::__construct("$root$path", self::CURRENT_AS_PATHNAME | self::SKIP_DOTS);

        $this->filesystem = $filesystem;
        $this->parser = $parser;
        $this->root = $root;
        $this->path = $path;
    }

    /**
     * Get pathname and create a DataFile object
     *
     * @return \CastlePointAnime\Brancher\Twig\DataFile
     */
    public function current()
    {
        $pathname = parent::current();
        $relPathname = rtrim($this->filesystem->makePathRelative($pathname, $this->root), '/');

        if (is_dir($pathname)) {
            return new self($this->filesystem, $this->parser, $this->root, "$relPathname/");
        } else {
            return new DataFile($this->parser, $pathname, $relPathname);
        }
    }

    /**
     * Return either an iterator or a data object, depending on the
     * type of the current file
     *
     * @param string $key
     *
     * @return \CastlePointAnime\Brancher\Twig\DataFile|\CastlePointAnime\Brancher\Twig\DataIterator
     */
    public function offsetGet($key)
    {
        $path = "{$this->path}$key";
        if (is_dir("{$this->root}$path")) {
            return new self($this->filesystem, $this->parser, $this->root, "$path/");
        } else {
            return new DataFile($this->parser, "{$this->root}$path", $path);
        }
    }

    /**
     * Check if the file exists
     *
     * @param string $key Relative path to file
     *
     * @return bool True if exists, false otherwise
     */
    public function offsetExists($key)
    {
        return file_exists("{$this->root}/{$this->path}/$key");
    }

    /**
     * This method cannot be called
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @throws \LogicException
     */
    public function offsetSet($key, $value)
    {
        throw new \LogicException('Cannot set data in immutable object');
    }

    /**
     * This method cannot be called
     *
     * @param mixed $key
     *
     * @throws \LogicException
     */
    public function offsetUnset($key)
    {
        throw new \LogicException('Cannot unset data in immutable object');
    }

    /**
     * Simple wrapper to get the number of files in the directory
     *
     * @return int Number of items in iterator
     */
    public function count()
    {
        return iterator_count($this);
    }
}
