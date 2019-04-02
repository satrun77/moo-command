<?php
/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command\Workspace;

use MooCommand\Command\Workspace;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Log.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Log extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'View output from container or containers. A wrapper for docker-compose logs.';
    /**
     * @var string
     */
    protected $childSignature = 'log';
    /**
     * @var array
     */
    protected $arguments = [
        'name'      => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
        ],
        'container' => [
            'mode'        => InputArgument::OPTIONAL,
            'description' => 'Name of the container to show its logs',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire(): void
    {
        $this->changeToSiteDirectory();

        // Container name
        $container = $this->argument('container');

        // Display the container logs
        $this->getOutputStyle()->title('Logs...');

        // Execute docker logs command
        $command = file_exists('./start') ? './start' : 'docker-compose';

        $this->getShellHelper()->execRealTime('%s "logs %s"', $command, $container);
    }
}
