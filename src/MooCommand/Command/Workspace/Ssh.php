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
use Symfony\Component\Console\Input\InputArgument;

/**
 * Ssh.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Ssh extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'SSH into a container for a site within the workspace.';
    /**
     * @var string
     */
    protected $childSignature = 'ssh';
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
            'description' => 'Name of the container to ssh into',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $this->changeToSiteDirectory();

        $siteName  = str_replace('.', '', $this->argument('name'));
        $container = $this->argument('container');

        $containerName = $container;
        if ('proxy' !== $siteName) {
            $containerName = sprintf('%s_%s_1', $siteName, $container);
        }

        // Execute command inside the docker site
        $this->getShellHelper()->execRealTime('docker exec -i -t %s /bin/bash', $containerName);
    }
}
