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

namespace CastlePointAnime\Brancher\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Command that builds a site from one directory into another
 *
 * A command where the user provides an input and output directory, and
 * every file with front YAML in the input directory is rendered through
 * Twig, optionally additionally rendered (depending on the file type),
 * and output to the same filename in the output directory.
 *
 * @package CastlePointAnime\Brancher\Command
 */
class BuildCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build the website into a directory')
            ->addOption(
                'template-dir',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Directories to look for templates in'
            )->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Files or directories to exclude from rendering (globs supported)'
            )->addArgument(
                'root',
                InputArgument::OPTIONAL,
                'Root directory at which to start rendering',
                '.'
            )->addArgument(
                'output',
                InputArgument::OPTIONAL,
                'Output directory to build the website into',
                '_site'
            );
    }

    /**
     * Build the site based on user input, and output status info
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
        $filesystem = $this->container->get('filesystem');
        /** @var \ParsedownExtra $mdParser */
        $mdParser = $this->container->get('parsedown');
        /** @var \Mni\FrontYAML\Parser $parser */
        $parser = $this->container->get('frontyaml');
        /** @var \Twig_Environment $twig */
        $twig = $this->container->get('twig');

        // Find all files in root directory
        $root = $input->getArgument('root');
        $renderFinder = new Finder();
        $renderFinder
            ->files()
            ->in($root)
            ->exclude($input->getOption('template-dir'))
            ->exclude($input->getOption('exclude'))
            ->exclude($input->getArgument('output'));
        array_map(
            [$renderFinder, 'notPath'],
            $this->container->getParameter('castlepointanime.brancher.build.excludes')
        );

        // First extract the files, parse the front YAML, and store in an array
        // Keep aside files without front YAML
        $templates = [];
        $raws = [];
        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($renderFinder as $fileInfo) {
            $document = $parser->parse($fileInfo->getContents(), false);
            if ($document->getYAML()) {
                $templates[$fileInfo->getRelativePathname()] = $document->getContent();
            } else {
                $raws[] = $fileInfo->getRelativePathname();
            }
        }

        // Put all files into the Twig loader
        $twig->setLoader(
            new \Twig_Loader_Chain(
                [
                    new \Twig_Loader_Filesystem(
                        array_filter(
                            $input->getOption('template-dir') ?: [$input->getArgument('root') . '/_templates'],
                            'is_executable'
                        )
                    ),
                    new \Twig_Loader_Array($templates),
                ]
            )
        );

        $outputDir = $input->getArgument('output');
        // Render every file and dump to output
        foreach (array_keys($templates) as $relativePath) {
            $rendered = $twig->render($relativePath);

            // Additional rendering for certain file types
            switch ($fileInfo->getExtension()) {
                case 'md':
                case 'markdown':
                    $rendered = $mdParser->parse($rendered);
                    break;
            }

            // Output to final file
            $outputFilename = "$outputDir/$relativePath";
            $filesystem->dumpFile($outputFilename, $rendered);
        }
        // Copy over static files verbatim
        foreach ($raws as $relativePath) {
            $filesystem->copy("$root/$relativePath", "$outputDir/$relativePath");
        }
    }
}
