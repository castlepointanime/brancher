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

namespace CastlePointAnime\Brancher;

/**
 * Class containing constants and information about events dispatched
 * in Brancher
 */
final class BrancherEvents
{
    /**
     * The brancher.oldfile event is thrown when an old file that no longer
     * exists in the source directory is found.
     *
     * The event listener receives a CastlePointAnime\Brancher\Event\OldFileEvent,
     * and can affect whether the file is deleted.
     *
     * @var string
     */
    const OLDFILE = 'brancher.oldfile';

    /**
     * The brancher.setup event is thrown once when the file finder has been set
     * up and is about to start rendering.
     *
     * The event listener receives a CastlePointAnime\Brancher\Event\SetupEvent.
     *
     * @var string
     */
    const SETUP = 'brancher.setup';

    /**
     * The brancher.directory_enter event is triggered every time a new directory
     * is entered
     *
     * The event listener receives a CastlePointAnime\Brancher\Event\DirectoryEnterEvent
     *
     * @var string
     */
    const DIRECTORY_ENTER = 'brancher.directory_enter';

    /**
     * The brancher.render event is thrown when a template is about to be rendered.
     *
     * The event listener receives a CastlePointAnime\Brancher\Event\RenderEvent.
     *
     * @var string
     */
    const RENDER = 'brancher.render';

    /**
     * The brancher.teardown event is thrown at the end of the build.
     *
     * The event listener receives a CastlePointAnime\Brancher\Event\TeardownEvent.
     *
     * @var string
     */
    const TEARDOWN = 'brancher.teardown';
}
