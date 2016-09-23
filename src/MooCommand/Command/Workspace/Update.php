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
 * Update.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Update extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Update site containers except for the directories site, env, solr/myindex.';
    /**
     * @var string
     */
    protected $childSignature = 'update';
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
        $sitePath = $this->changeToSiteDirectory();
        $siteName = $this->getTemplate($sitePath);

        // Copy container files
        $this->getConfigHelper()->copyResource('docker/' . $siteName, $sitePath, [
            '/env',
            '/site',
            '/site/public',
            '/solr/myindex',
        ]);

        // Display success message
        $successMessage = 'The container files updated successfully.';
        $this->getOutputStyle()->success($successMessage);

        // Build the container
        $this->getShellHelper()->execApplicationCommand('ws:build', [
            'name' => $this->argument('name'),
        ]);
    }
}
