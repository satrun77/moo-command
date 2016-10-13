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
 * Composer.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Composer extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Execute PHP composer command inside the composer container.';
    /**
     * @var string
     */
    protected $childSignature = 'composer';
    /**
     * @var array
     */
    protected $arguments = [
        'name'     => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Name of the directory containing the docker/site files',
        ],
        'argument' => [
            'mode'        => InputArgument::OPTIONAL,
            'description' => 'Argument to pass to composer command',
            'default'     => 'install',
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
        $argument = $this->argument('argument');

        // Site root directory
        $siteRoot = $this->getConfigHelper()->getSiteRoot('name');

        // Change current directory to the container root directory
        chdir($siteRoot);

        $this->getShellHelper()->execRealTime('./start "run --rm composer %s"', $argument);
    }
}
