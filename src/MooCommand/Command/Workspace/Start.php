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
 * Start.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Start extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Create and start containers for a site within the workspace. A wrapper to docker-compose up.';
    /**
     * @var string
     */
    protected $childSignature = 'start';
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

        // Start the container
        $start = $this->getShellHelper()->execRealTime('./start');
        if (!$start) {
            throw new \Exception('Unable to start the site container.');
        }

        // Success message
        $successMessage = 'The site started successfully.';
        $this->getOutputStyle()->success($successMessage);
        $this->notify('Start environment ' . $this->getConfigHelper()->getCurrentSiteName(), $successMessage);
    }
}
