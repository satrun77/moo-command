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

use Exception;
use MooCommand\Command\Workspace;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Frontend provide command to execute frontend tasks (ie. build assets, watch for assets changes).
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Frontend extends Workspace
{
    /**
     * Constants for acceptable commands.
     *
     * @var string
     */
    public const CMD_INSTALL = 'install';

    /**
     * @var string
     */
    public const CMD_BUILD = 'build';

    /**
     * @var string
     */
    public const CMD_WATCH = 'watch';

    /**
     * @var string
     */
    protected $description = 'Execute a frontend commands to build or watch assets.';

    /**
     * @var string
     */
    protected $childSignature = 'fe';

    /**
     * @var array
     */
    protected $arguments = [
        'name' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
        ],
        'execute' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Command to execute inside the container',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @throws Exception
     */
    protected function fire(): void
    {
        // Move to root directory of docker setup
        $this->changeToSiteDirectory();

        // Ensure site name does not include "."
        $siteName = str_replace('.', '', $this->argument('name'));
        // Frontend container name
        $containerName = sprintf('%s_front_1', $siteName);

        // Execute command inside the container
        $this->getShellHelper()->execRealTime('docker exec -i -t %s %s', $containerName, $this->command());
    }

    /**
     * Get command to execute in container.
     */
    protected function command(): string
    {
        // Command we want to execute inside container
        $command = $this->argument('execute');
        // Collection of acceptable commands
        $options = [
            static::CMD_INSTALL => 'install',
            static::CMD_BUILD   => 'run dev',
            static::CMD_WATCH   => 'run watch',
        ];
        // Check if command option value or fallback to build
        if (!array_key_exists($command, $options)) {
            $command = $options[static::CMD_BUILD];
        }

        return (string) $command;
    }
}
