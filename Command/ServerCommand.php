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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that runs the PHP built-in server and builds the site
 *
 * @package CastlePointAnime\Brancher\Command
 */
class ServerCommand extends BaseCommand
{
    /** @var resource Process resource for PHP web server */
    private $server;

    protected function doConfigure()
    {
        $this
            ->setName('server')
            ->setDescription('Run a server with the built site')
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'Hostname to host the server at (defaults to localhost)',
                'localhost'
            )
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_REQUIRED,
                'Port to run the server on (defaults to 9000)',
                9000
            );
    }

    /**
     * Run a server that shows the current side and re-builds
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \CastlePointAnime\Brancher\Brancher $brancher */
        $brancher = $this->container->get('brancher');
        $logger = new ConsoleLogger($output);
        $brancher->setLogger($logger);

        $pipes = [];
        $host = $input->getOption('host') . ':' . $input->getOption('port');
        $this->server = proc_open(PHP_BINARY . " -S $host", [], $pipes, $brancher->getOutputDirectory());

        if (!is_resource($this->server)) {
            throw new \RuntimeException('Could not start PHP built-in server');
        }

        $inotify = null;
        $watches = [];
        if (function_exists('inotify_init')) {
            $logger->debug("Found PHP extension: inotify");
            $inotify = inotify_init();
        }

        if (function_exists('pcntl_signal')) {
            $logger->debug("Found PHP extension: pcntl");
            pcntl_signal(SIGINT, [$this, 'ctrlCHandler']);
        }

        while (proc_get_status($this->server)['running'] === true) {
            $logger->info("Building site");
            $paths = $brancher->build();

            // If we have inotify, use it to regnerate only when necessary
            if (is_resource($inotify)) {
                // Add watches for new paths
                $paths = array_unique(array_filter(array_map('realpath', array_map('dirname', $paths))));
                foreach (array_diff($paths, $watches) as $path) {
                    inotify_add_watch($inotify, $path, IN_ALL_EVENTS & ~IN_ACCESS);
                }

                // Wait until something changes
                $logger->debug("Waiting for inotify to find changes");
                while (inotify_queue_len($inotify) === 0) {
                    sleep(0.5);
                }
                $logger->debug("Changes found! Clearing queue.");
                while (inotify_queue_len($inotify) > 0) {
                    inotify_read($inotify);
                }
            } else {
                sleep(4);
            }
        }

        proc_close($this->server);
    }

    /**
     * Signal handler for Ctrl-C
     */
    public function ctrlCHandler()
    {
        proc_terminate($this->server);
    }
}
