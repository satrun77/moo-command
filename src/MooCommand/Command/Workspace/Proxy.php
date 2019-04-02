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

/**
 * Proxy.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Proxy extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Build the proxy container if not exists or start the container.';
    /**
     * @var string
     */
    protected $childSignature = 'proxy';

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

        $proxy = $this->getWorkspace() . 'proxy';
        $this->debug('Workspace: ' . $this->getWorkspace());

        if (is_dir($proxy)) {
            $this->startProxyContainer($proxy);
        } else {
            $this->copyProxyContainer($proxy);
        }
    }

    protected function copyProxyContainer(string $proxy): void
    {
        // Info message
        $this->getOutputStyle()->info('There is no proxy container in the workspace.');
        $this->getOutputStyle()->info('Create proxy container.');

        // Copy proxy folder to workspace
        $this->getConfigHelper()->copyResource('docker/proxy', $proxy);

        // Success message
        $message = 'Docker proxy container created in workspace.
You can start container by executing the same command.';
        $this->getOutputStyle()->success($message);
    }

    /**
     * Start the proxy container in the workspace.
     *
     * @param string $proxy
     *
     * @throws \Exception
     */
    protected function startProxyContainer(string $proxy): void
    {
        // Info message
        $this->getOutputStyle()->info('Proxy container exists in the workspace.');

        // Change current directory to the container root directory
        chdir($proxy);

        // Start docker proxy container
        $command = $this->getShellHelper()->execRealTime('./start');
        if (!$command) {
            throw new \RuntimeException("Can't start docker proxy container");
        }

        // Debug and success messages
        $this->getOutputStyle()->success('Started docker proxy container.');
    }
}
