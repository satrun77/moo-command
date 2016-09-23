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

use MooCommand\Command\Workspace as WorkspaceAbstract;

/**
 * Cleanup.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Cleanup extends WorkspaceAbstract
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
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $workspace = $this->getWorkspace();

        // Change current directory to the container root directory
        chdir($workspace);

        // List of commands to execute
        $commands = [
            // Delete old containers that is weeks ago
            [
                'title'   => 'Delete old containers that is weeks ago',
                'command' => 'docker ps -a | grep \'weeks ago\' | awk \'{print $1}\' | xargs docker rm',
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
        ];

        $shell  = $this->getShellHelper();
        $output = $this->getOutputStyle();
        foreach ($commands as $command) {
            if (array_key_exists('title', $command)) {
                $output->title($command['title']);
            }

            $status = $shell->exec($command['command']);
            if (!$status->isSuccessful()) {
                if (array_key_exists('error', $command)) {
                    $output->error($command['error']);
                } elseif (array_key_exists('exception', $command)) {
                    throw new \Exception($command['exception']);
                }
            }

            if (isset($command['ok'])) {
                $output->success($command['ok']);
            }
        }
    }
}
