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
 * Start.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Start extends Workspace
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
    protected function fire(): void
    {
        $this->changeToSiteDirectory();

        // Start the container
        $start = $this->getShellHelper()->execRealTime('./start');
        if (!$start) {
            throw new \RuntimeException('Unable to start the site container.');
        }

        // Success message
        $protocol       = 'http://';
        $ip             = $this->getMachineIp();
        $port           = ':' . $this->getEnvFileValue('VIRTUAL_PORT');
        $successMessage = sprintf("The site started successfully.\nWebsite: %s%s%s", $protocol, $ip, $port);
        $this->getOutputStyle()->success($successMessage);
        $this->notify('Start environment ' . $this->getConfigHelper()->getCurrentSiteName(), $successMessage);

        $this->showDockerSyncInfo('', 'start');
    }
}
