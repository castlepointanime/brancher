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

namespace CastlePointAnime\Brancher\Tests\Command;

use CastlePointAnime\Brancher\Command\BuildCommand;
use CastlePointAnime\Brancher\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Finder;

/**
 * Test the accuracy of the build command, which builds a site once
 *
 * @package CastlePointAnime\Brancher\Tests\Command
 */
class BuildCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get a list of test websites from the Resources directory
     *
     * @return array
     */
    public static function provideSites()
    {
        $iterator = new \FilesystemIterator(
            __DIR__.'/../Resources',
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        );

        $sites = [];
        foreach ($iterator as $pathname) {
            // Try and find config file if it exists
            $finder = new Finder();
            $finder->in($pathname)->files()->name('/_config\.(xml|yml|php)/');

            $config = null;
            /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
            foreach ($finder as $fileInfo) {
                $config = $fileInfo->getPathname();
            }

            $sites[] = [$pathname, $config];
        }

        return $sites;
    }

    /**
     * @dataProvider provideSites
     * @param string $root Root directory of site
     * @param string|null $config Path to configuration file
     */
    public function testSites($root, $config = null)
    {
        $application = new Application();
        $application->add(new BuildCommand());

        /** @var \CastlePointAnime\Brancher\Command\BuildCommand $command */
        $command = $application->find('build');
        $commandTester = new CommandTester($command);

        $outputDir = sys_get_temp_dir();
        $commandTester->execute(
            array_filter([
                "--config" => $config,
                'root' => $root,
                'output' => $outputDir,
            ])
        );

        $finder = new Finder();
        $finder->files()->in($root)
            ->exclude("$root/_templates")->exclude("$root/_site")
            ->contains('/^---\n.*\n---\n/s');

        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            $filename = $fileInfo->getRelativePathname();
            $this->assertFileEquals("$root/_site/$filename", "$outputDir/$filename");
        }
    }
}
