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

namespace CastlePointAnime\Brancher\Console;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('brancher', '0.1');
    }

    public function getHelp()
    {
        return $this->getLongVersion()."\n"."\n"."Copyright (C) 2015  Tyler Romeo <tylerromeo@gmail.com>\n"
        ."This program is free software, and you are welcome to redistribute it\n"
        ."under certain conditions. It comes with ABSOLUTELY NO WARRANTY.\n"."Run the 'license' command for details.";
    }
}
