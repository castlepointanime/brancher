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
 * Variation of \AppendIterator that only takes iterators that
 * implement \ArrayAccess, and provides appropriate \ArrayAccess
 * functionality
 *
 * @package CastlePointAnime\Brancher\Twig
 */
class AppendDataIterator extends \AppendIterator implements ArrayAccessIterator
{
    /**
     * @var ArrayAccessIterator[]
     */
    private $iterators;

    /**
     * Add an iterator to the end of the list of iterators
     *
     * @param ArrayAccessIterator $iterator
     */
    public function append(ArrayAccessIterator $iterator)
    {
        $this->iterators[] = $iterator;

        parent::append($iterator);
    }

    /**
     * Check each iterator for the key, and return the first value found
     *
     * @param mixed $key
     *
     * @return mixed|null
     */
    public function offsetGet($key)
    {
        foreach ($this->iterators as $iterator) {
            if (isset($iterator[$key])) {
                return $iterator[$key];
            }
        }

        return null;
    }

    /**
     * Check if the key exists in any of the iterators
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        foreach ($this->iterators as $iterator) {
            if (isset($iterator[$key])) {
                return true;
            }
        }

        return false;
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
}
