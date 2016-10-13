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

/**
 * ListSites.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class ListSites extends WorkspaceAbstract
{
    /**
     * @var string
     */
    protected $description = 'Display list of available sites and their statuses.';
    /**
     * @var string
     */
    protected $childSignature = 'sites';

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        $this->getOutputStyle()->title('Available sites:');

        $workspace = $this->getConfigHelper()->getWorkspace();
        try {
            $iterator = new \DirectoryIterator($workspace);
            $rows     = [];
            $ports    = [];
            foreach ($iterator as $file) {
                $env = $file->getPathname() . '/env/web.env';
                if ($file->isDir() && file_exists($env)) {
                    $envFile = new \SplFileObject($env, 'r');
                    $row     = [$file->getFilename()];

                    foreach ($envFile as $line) {
                        $line = explode('=', trim($line));
                        if (empty($line[0]) || !in_array($line[0], ['VIRTUAL_HOST', 'VIRTUAL_PORT'])) {
                            continue;
                        }

                        $key       = $line[0] === 'VIRTUAL_HOST' ? 1 : 2;
                        $row[$key] = !empty($line[1]) ? $line[1] : '';
                    }

                    // Store ports to an array
                    $ports[$file->getFilename()] = $row[2];

                    // Sort columns & add to rows
                    ksort($row);
                    $rows[$file->getFilename()] = $row;
                }
            }

            foreach ($rows as $key => $row) {
                // Check if port is unique
                $container = array_search($row[2], $ports);
                if ($container !== false && $container !== $key) {
                    $rows[$key][2] .= ' <fg=red>×</fg=red>';
                }

                // Check if site is active (running)
                $containerStatus = [];
                foreach ($this->containers as $container) {
                    $status = $this->getShellHelper()->exec('docker inspect -f \'{{.State.Running}}\' %s_%s_1', str_replace('.', '', $key), $container);
                    if (trim($status->getOutput()) === 'true') {
                        $containerStatus[$container] = '<fg=green>✓ ' . $container . '</fg=green>';
                    } else {
                        $containerStatus[$container] = '<fg=red>× ' . $container . '</fg=red>';
                    }
                }

                $rows[$key][3] = implode(' | ', $containerStatus);
            }

            // Display table of data
            $headers = ['Container', 'VIRTUAL_HOST', 'VIRTUAL_PORT', 'Running'];
            $this->getOutputStyle()->table($headers, $rows);
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
        }
    }
}
