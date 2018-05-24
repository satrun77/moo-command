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
use Symfony\Component\Yaml\Parser;

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
        // YML file parser
        $yml              = new Parser();
        // Reset collection of containers
        $this->containers = [];
        // Get path to workspace
        $workspace        = $this->getConfigHelper()->getWorkspace();
        // Get instance of shell helper
        $shell            = $this->getShellHelper();

        // Output heading
        $this->getOutputStyle()->title('Available sites:');

        try {
            $iterator = new \DirectoryIterator($workspace);
            $rows     = [];
            $ports    = [];
            foreach ($iterator as $file) {
                // Get data from web.env file about port & host
                $env = $file->getPathname() . '/env/web.env';
                if ($file->isDir() && file_exists($env)) {
                    $envFile = new \SplFileObject($env, 'r');
                    $row     = [$file->getFilename()];

                    foreach ($envFile as $line) {
                        $line = explode('=', trim($line));
                        if (empty($line[0]) || !in_array($line[0], ['VIRTUAL_HOST', 'VIRTUAL_PORT'])) {
                            continue;
                        }

                        $key       = 'VIRTUAL_HOST' === $line[0] ? 1 : 2;
                        $row[$key] = !empty($line[1]) ? $line[1] : '';
                    }

                    // Store ports to an array
                    $ports[$file->getFilename()] = $row[2];

                    // Sort columns & add to rows
                    ksort($row);
                    $rows[$file->getFilename()] = $row;

                    // Extract & add containers to table headers
                    $services = $yml->parse(
                        file_get_contents($file->getPathname() . '/docker-compose.yml'),
                        true,
                        true
                    );

                    // Get containers grouped per site
                    // Exclude app, data, & composer from docker YML
                    // Add padding on both side for each container to have equal width columns
                    $this->containers[$file->getFilename()] = array_map(function ($container) {
                        return str_pad($container, 10, ' ', STR_PAD_BOTH);
                    }, array_diff(array_keys($services['services']), ['app', 'data', 'composer']));
                }
            }

            // Unique value of containers for table headers
            $containers = array_unique(array_reduce($this->containers, function ($result, $item) {
                if (!is_array($result)) {
                    $result = [];
                }

                return array_merge($result, array_values($item));
            }, []));

            // Construct table rows
            foreach ($rows as $key => $row) {
                // Check if port is unique
                $container = array_search($row[2], $ports);
                if (false !== $container && $container !== $key) {
                    $rows[$key][2] .= ' ❌';
                }

                // Check if site is active (running)
                foreach ($containers as $container) {
                    // If the container is not part of the site
                    if (!in_array($container, $this->containers[$key])) {
                        $rows[$key][] = '⚪';
                        continue;
                    }

                    // Check status of the container
                    $status = $shell->exec('docker inspect -f \'{{.State.Running}}\' %s_%s_1', str_replace('.', '', $key), $container);
                    if ('true' === trim($status->getOutput())) {
                        $rows[$key][] = '✅';
                    } else {
                        $rows[$key][] = '❌';
                    }
                }
            }

            // Add containers to headersDisplay table of data
            $headers = array_merge(['Container', 'VIRTUAL_HOST', 'VIRTUAL_PORT'], $containers);
            $this->getOutputStyle()->table($headers, $rows);
            $this->getOutputStyle()->listing([
                '⚪  Container is not used.',
                '✅  Container is running.',
                '❌  Container is not running.',
            ]);
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
        }
    }
}
