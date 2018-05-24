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
 * Stop.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Stop extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Stop services for a site within the workspace. A wrapper to docker stop command.';
    /**
     * @var string
     */
    protected $childSignature = 'stop';
    /**
     * @var array
     */
    protected $arguments = [
        'name' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
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

        // Should we stop all containers
        if ('all' === $this->argument('name')) {
            return $this->stopAllContainers();
        }

        // Stop one container only
        return $this->stopContainer();
    }

    /**
     * Stop all of the containers in a site.
     */
    protected function stopAllContainers()
    {
        $workspace = $this->getWorkspace();

        chdir($workspace);

        $stop = $this->getShellHelper()->exec('docker stop $(docker ps -a -q)');
        if (!$stop->isSuccessful()) {
            $this->getOutputStyle()->error('Unable to stop containers');
        } else {
            $this->notify('Stop all environment', 'All containers are stopped.');
        }
    }

    /**
     * Stop a specific container within a site,.
     */
    protected function stopContainer()
    {
        // Stop the containers
        $stop = $this->getShellHelper()->execRealTime('./start stop');
        if (!$stop) {
            $this->getOutputStyle()->error('Unable to stop the containers.');
        }

        // Success message
        $successMessage = 'The site stopped successfully.';
        $this->getOutputStyle()->success($successMessage);
        $this->notify('Stop environment ' . $this->getConfigHelper()->getCurrentSiteName(), $successMessage);
    }
}
