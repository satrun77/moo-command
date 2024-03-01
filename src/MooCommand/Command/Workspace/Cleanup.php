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
use MooCommand\Console\Helper\ShellHelper;
use MooCommand\Console\StyledOutput;
use Symfony\Component\Console\Input\InputOption;

/**
 * Cleanup.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Cleanup extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Execute specific commands to free up unwanted space such as, removing old containers, or dangling images.';

    /**
     * @var string
     */
    protected $childSignature = 'clean';

    /**
     * @var array
     */
    protected $options = [
        'network' => [
            'shortcut'    => 't',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Option to remove stale networks.',
            'default'     => null,
        ],
    ];

    /**
     * List of commands to execute.
     *
     * @var array
     */
    protected $commands = [
        // Delete old containers that is weeks ago
        [
            'title'   => 'Delete old containers that is weeks ago',
            'command' => "docker ps -a | grep 'weeks ago' | awk '{print \$1}' | xargs docker rm",
            'error'   => 'Unable to delete old containers.',
        ],
        // Delete old containers
        [
            'title'   => 'Delete old containers',
            'command' => 'docker rm `docker ps --no-trunc -aq`',
            'error'   => 'Unable to delete old containers.',
        ],
        // Delete dangling images
        [
            'title'   => 'Delete dangling images',
            'command' => 'docker rmi $(docker images -q -f dangling=true)',
            'error'   => 'Unable to delete dangling images.',
        ],
        // Delete dangling volumes
        [
            'title'   => 'Delete dangling volumes',
            'command' => 'docker volume rm $(docker volume ls -q -f dangling=true)',
            'error'   => 'Unable to delete dangling volumes.',
        ],
        // Delete stale networks
        [
            'title'   => 'Delete stale networks',
            'command' => 'removeStaleNetworks',
            'option'  => 'network',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @throws Exception
     */
    protected function fire(): void
    {
        // Change current directory to the container root directory
        $workspace = $this->getWorkspace();
        chdir($workspace);

        // Shell & output helpers
        $shell  = $this->getShellHelper();
        $output = $this->getOutputStyle();

        // Execute clean up commands
        foreach ($this->commands as $command) {
            if ($this->isMethodCallback($command['command'])) {
                $this->executeMethodCallback($command, $output, $shell);
            } else {
                $this->executeShellCommand($command, $output, $shell);
            }
        }
    }

    /**
     * Execute a callback method to preform a clean up task.
     */
    protected function executeMethodCallback(array $command, StyledOutput $output, ShellHelper $shell): void
    {
        // Check if command is allowed to be executed
        if (array_key_exists('option', $command) && !$this->option($command['option'])) {
            return;
        }

        // Execute method
        if (($method = $this->getCommandValue('command', $command)) !== '') {
            $output->title($this->getCommandValue('title', $command));
            $this->{$method}($output, $shell);
        }
    }

    /**
     * Execute shell command to preform a clean up task.
     */
    protected function executeShellCommand(array $command, StyledOutput $output, ShellHelper $shell): void
    {
        // Value for shell command must exists
        $shellCommand = $this->getCommandValue('command', $command);
        if ($shellCommand === '') {
            return;
        }

        // Title output
        $output->title($this->getCommandValue('title', $command));

        // Execute command & display error if not successful
        $status = $shell->exec($shellCommand);
        $error  = $this->getCommandValue('error', $command);
        if (!$status->isSuccessful() && !empty($error)) {
            $output->error($error);
        }
    }

    /**
     * Clean up task to remove stale networks.
     */
    protected function removeStaleNetworks(StyledOutput $output, ShellHelper $shell): void
    {
        $networkCommand = $shell->exec('docker network ls -q');
        if (!$networkCommand->isSuccessful()) {
            return;
        }

        $skipNetworks = ['host', 'bridge', 'none', 'proxy_default'];
        $networks     = explode("\n", $networkCommand->getOutput());
        foreach ($networks as $network) {
            // Skip empty lines
            if (empty($network)) {
                continue;
            }

            // If network details contains less than 5 strings, we assume this is empty network or closes, remove it
            $networkStatus = $shell->exec("docker network inspect -f '{{json .Containers}}' \"{$network}\"");
            if (mb_strlen($networkStatus->getOutput()) <= 5) {
                // Get network name
                $name = trim(str_replace('"', '', $shell->exec("docker network inspect -f '{{json .Name}}' \"{$network}\"")->getOutput()));

                // Skip selected networks
                if (!in_array($name, $skipNetworks)) {
                    $shell->exec("docker network rm {$network}");
                }
            }
        }
    }

    /**
     * Whether or no the command string is a callback method.
     */
    protected function isMethodCallback(string $command): bool
    {
        return !preg_match('/\s/', $command) && method_exists($this, $command);
    }

    /**
     * Get value from an array based on key, or empty for not found.
     */
    protected function getCommandValue(string $name, array $source): string
    {
        return array_key_exists($name, $source) ? $source[$name] : '';
    }
}
