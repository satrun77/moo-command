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
 * Build.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Build extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Build a container or all of the containers within a site. A wrapper to docker-compose build command.';
    /**
     * @var string
     */
    protected $childSignature = 'build';
    /**
     * @var array
     */
    protected $arguments = [
        'name' => [
            'mode'        => InputArgument::OPTIONAL,
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
        $start = $this->getShellHelper()->exec('./start build');
        if (!$start->isSuccessful()) {
            throw new \Exception('Unable to build the site container.');
        }

        // Display success message
        $successMessage = 'The site build completed successfully.';
        $this->getOutputStyle()->success($successMessage);

        // Ask to start container
        if ($this->getQuestionHelper()->confirmAsk('Would you like to start the new container?', false)) {
            $this->getShellHelper()->execApplicationCommand('ws:start', [
                'name' => $this->argument('name'),
            ]);
        }
    }
}