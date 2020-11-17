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
 * ContainerIp.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class ContainerIp extends Workspace
{
    /**
     * @var string
     */
    protected $description = 'Display the IP addresses selected for each of the active docker containers.';
    /**
     * @var string
     */
    protected $childSignature = 'ips';
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
        $command = "docker inspect -f '{{.Name}}|{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \$(docker ps -aq)";
        $grepBy = $this->argument('filter');
        if (!empty($grepBy)) {
            $command .= ' | grep "%s"';
        }

        // Get output as an array
        $output = $this->getShellHelper()->exec($command, $grepBy)->getOutput();
        $lines = array_filter(explode("\n", $output));

        // Convert command output to table rows
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = explode('|', $line);
        }

        // Display table
        $headers = ['Container', 'IP Address'];
        $this->getOutputStyle()->table($headers, $rows);
    }
}
