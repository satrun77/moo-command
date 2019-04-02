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
 * Composer.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Composer extends Workspace
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
    protected function fire(): void
    {
        // Site root directory
        $this->changeToSiteDirectory();
        $argument = $this->argument('argument');

        $this->execCommandInContainer('composer', $argument, 'php');
    }
}
