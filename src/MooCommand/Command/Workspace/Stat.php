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
 * Stat.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Stat extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Display a live stream of container(s) resource usage statistics.';
    /**
     * @var string
     */
    protected $childSignature = 'stat';
    /**
     * @var array
     */
    protected $arguments = [
        'filter' => [
            'mode' => InputArgument::OPTIONAL,
            'description' => 'Filter the output by keyword',
        ],
    ];

    /**
     * Main method to execute the command script.
     *
     * @throws \Exception
     */
    protected function fire(): void
    {
        $command = 'docker stats $(docker ps%s|grep -v "NAMES"|awk \'{ print $NF }\'|tr "\n" " ")';

        $grepBy = $this->argument('filter');
        if (!empty($grepBy)) {
            $grepBy = '|grep "' . $grepBy . '" ';
        }

        $this->getShellHelper()->execRealTime($command, $grepBy);
    }
}
