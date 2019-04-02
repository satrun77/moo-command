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
 * Sh execute a command inside a container.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Sh extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Execute a command inside a container for a site within the workspace.';
    /**
     * @var string
     */
    protected $childSignature = 'sh';
    /**
     * @var array
     */
    protected $arguments = [
        'name'      => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
        ],
        'container' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the container to execute command into',
        ],
        'execute' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Command to execute inside the container',
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
        // Move to root directory of docker setup
        $this->changeToSiteDirectory();

        // Ensure site name does not include "."
        $siteName  = str_replace('.', '', $this->argument('name'));
        // Name of the container from command argument
        $container = $this->argument('container');
        // Convert name of the container to the name used by docker
        $containerName = $container;
        if ('proxy' !== $siteName) {
            $containerName = sprintf('%s_%s_1', $siteName, $container);
        }
        // Command we want to execute inside container
        $command = $this->argument('execute');

        // Execute command inside the container
        $this->getShellHelper()->execRealTime('docker exec -i -t %s %s', $containerName, $command);
    }
}
