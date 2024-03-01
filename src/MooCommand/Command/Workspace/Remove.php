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
 * Remove.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Remove extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Remove stopped containers for a site within the workspace. A wrapper for docker-compose rm.';

    /**
     * @var string
     */
    protected $childSignature = 'rm';

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
     * @throws Exception
     */
    protected function fire(): void
    {
        $this->changeToSiteDirectory();

        // Stop the container before removing it
        $this->getShellHelper()->execApplicationCommand('ws:stop', [
            'name' => $this->argument('name'),
        ]);

        // Stop the containers
        $stop = $this->getShellHelper()->execRealTime('./start rm');
        if (!$stop) {
            $this->getOutputStyle()->error('Unable to remove the containers.');
        }

        // Remove network
        $networkName = $this->argument('name') . '_default';
        $this->getShellHelper()->exec('docker network rm %s', $networkName);

        // Success message
        $successMessage = 'The site removed successfully.';
        $this->getOutputStyle()->success($successMessage);
        $this->notify('Remove environment ' . $this->getConfigHelper()->getCurrentSiteName(), $successMessage);
    }
}
