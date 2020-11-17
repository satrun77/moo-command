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
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * ListSites.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class ListSites extends Workspace
{
    /**
     * Constants for various statuses.
     *
     * @var string
     */
    const STATUS_ACTIVE = '✔';
    /**
     * @var string
     */
    const STATUS_INACTIVE = '✘';
    /**
     * @var string
     */
    const STATUS_NA = '●';

    /**
     * Collection of color for each status.
     *
     * @var array
     */
    protected static $statusTag = [
        self::STATUS_ACTIVE => ['info', 'info'],
        self::STATUS_NA => ['fg=white', ''],
        self::STATUS_INACTIVE => ['fg=red', ''],
    ];

    /**
     * Command arguments.
     *
     * @var array
     */
    protected $arguments = [
        'container' => [
            'mode' => InputArgument::OPTIONAL,
            'description' => 'Name of the container to show its status',
        ],
    ];

    /**
     * @var array
     */
    protected $options = [
        'all' => [
            'shortcut' => 'a',
            'mode' => InputOption::VALUE_NONE,
            'description' => 'List all sites',
            'default' => null,
        ],
    ];

    /**
     * @var string
     */
    protected $description = 'Display list of available sites and their statuses.';
    /**
     * @var string
     */
    protected $childSignature = 'sites';

    /**
     * Collection holds internal cache data.
     *
     * @var array
     */
    protected static $cache = [
        'ports' => [],
        'machineIp' => null,
        'activeContainers' => '',
        'uniqueContainers' => [],
        'ymlParser' => null,
        'tableOutput' => null,
    ];

    /**
     * Main method to execute the command script.
     *
     * @throws \Exception
     */
    protected function fire(): void
    {
        // Reset collection of containers & ports
        $this->containers = [];
        // Get path to workspace
        $workspace = $this->getConfigHelper()->getWorkspace();

        // Output heading
        $this->outputTitle();

        try {
            $iterator = new \DirectoryIterator($workspace);
            $rows = [];
            foreach ($iterator as $file) {
                // Get data from web.env file about port & host
                if ($file->isDir() && ($row = $this->containerData($file))) {
                    $containerName = $file->getFilename();
                    // Store ports to an array
                    $this->ports($containerName, $row['port']);
                    // Set container data & merge container
                    $rows[$containerName] = $row;
                }
            }

            // Construct table rows
            foreach ($rows as $container => $row) {
                // Check if port is unique
                $row['port'] = $this->validatePort($row['port'], $container);

                // Check if site is active (running)
                $row = $this->validateContainersStatuses($row, $container);

                // Output site details
                $this->outputSite($row);
            }
        } catch (\Exception $e) {
            $this->debug($e->getMessage());
        }
    }

    /**
     * Output title.
     */
    protected function outputTitle(): void
    {
        $title = 'Available sites:';

        $containerFilter = $this->argument('container');
        if (!empty($containerFilter)) {
            $title .= sprintf(' ➜ (filter by %s)', $containerFilter);
        }

        $this->getOutputStyle()->title($title);
    }

    protected function validateContainersStatuses(array $row, string $key): array
    {
        $containers = $this->uniqueContainers();
        foreach ($containers as $container) {
            // If the container is not part of the site
            if (!in_array($container, $this->containers[$key], true)) {
                $row[$container] = static::STATUS_NA;
                continue;
            }

            // Check status of the container
            $containerName = sprintf('%s_%s_1', str_replace('.', '', $key), $container);
            $row[$container] = false !== mb_strpos($this->activeContainers(), $containerName) ? static::STATUS_ACTIVE : static::STATUS_INACTIVE;
        }

        return $row;
    }

    /**
     * Check if the container port does not match any other ports used for another container.
     */
    protected function validatePort(int $port, string $key): string
    {
        $container = array_search($port, $this->ports(), true);
        if (false !== $container && $container !== $key) {
            return sprintf('<error>%s</error>', $port);
        }

        return $port;
    }

    /**
     * Get data for a container.
     */
    protected function containerData(\SplFileInfo $file): ?array
    {
        $env = $file->getPathname() . '/env/web.env';
        if (!file_exists($env)) {
            return null;
        }

        $envFile = new \SplFileObject($env, 'r');
        $row = ['container' => $file->getFilename()];

        // Extract data from env file
        foreach ($envFile as $line) {
            $line = explode('=', trim($line));
            if (empty($line[0]) || !in_array($line[0], ['VIRTUAL_HOST', 'VIRTUAL_PORT'])) {
                continue;
            }

            $row[$line[0] === 'VIRTUAL_HOST' ? 'host' : 'port'] = !empty($line[1]) ? $line[1] : '';
        }

        $this->debug('Docker Compose: ' . $file->getPathname() . '/docker-compose.yml');
        $services = $this->ymlParser()->parse(
            file_get_contents($file->getPathname() . '/docker-compose.yml'),
            Yaml::PARSE_OBJECT
        );

        // Get containers grouped per site
        // Exclude app, data, & composer from docker YML
        // Add padding on both side for each container to have equal width columns
        $containers = array_diff(array_keys($services['services']), ['app', 'data', 'composer']);
        $this->containers[$file->getFilename()] = $containers;

        return $row;
    }

    /**
     * Output site details.
     */
    protected function outputSite(array $data): void
    {
        // Container name & container filter argument
        $containerFilter = $this->argument('container');
        $container = !empty($data['container']) ? $data['container'] : '';
        $showAll = $this->option('all');

        // Filter out output if we have container filter argument
        if (!empty($containerFilter) && mb_strpos($container, $containerFilter) === false) {
            return;
        }

        // If no filter used and we don't want to show all sites, then limit display to active sites
        if (empty($containerFilter) && !$showAll && !in_array(static::STATUS_ACTIVE, $data)) {
            return;
        }

        // Host and port values
        $host = !empty($data['host']) ? $data['host'] : '';
        $port = !empty($data['port']) ? $data['port'] : '';
        unset($data['container'], $data['host'], $data['port']);
        // Docker machine IP
        $ip = $this->machineIp();

        // Metadata cells
        $cells = $this->formatMetadataCells($data);
        // Number of cells we have
        $cellsNo = count($cells);
        // Width of the cells
        $cellsWidth = Helper::strlenWithoutDecoration($this->getOutputStyle()->getFormatter(), implode('', $cells));
        // Separator markup based on width of cells
        $separatorMarkup = str_repeat('--', $cellsWidth) . '+';

        // Style for table output
        $table = new Table($this->getOutputStyle());
        $table->setStyle(clone Table::getStyleDefinition('compact'));

        // Set table header
        $table->setHeaders([
            new TableCell(sprintf('<fg=magenta>%s</>', $container), ['colspan' => $cellsNo]),
        ]);

        // Set table rows
        $table->addRows([
            [new TableCell(sprintf('%s (http://%s:%d)', $host, $ip, $port), ['colspan' => $cellsNo])],
            [new TableCell(sprintf('<fg=magenta>%s</>', $separatorMarkup), ['colspan' => $cellsNo])],
            new TableSeparator(),
            $cells,
            [new TableCell(sprintf('<fg=magenta>%s</>', $separatorMarkup), ['colspan' => $cellsNo])],
        ]);

        // Render table
        $table->render();

        // Render line separator
        $this->getOutputStyle()->line('');
    }

    /**
     * Format metadata cells and return collection.
     */
    protected function formatMetadataCells(array $data): array
    {
        $cells = [];
        foreach ($data as $name => $value) {
            $tag = array_key_exists($value, static::$statusTag) ? static::$statusTag[$value] : static::$statusTag[static::STATUS_NA];
            $cells[$name] = str_pad(sprintf('<%s>%s %s</%s>', $tag[0], $value, $name, $tag[1]), 6, ' ', STR_PAD_RIGHT);
        }

        return $cells;
    }

    /**
     * Get table output.
     */
    protected function tableOutput(): Table
    {
        return $this->cache('tableOutput', function () {
            $table = new Table($this->getOutputStyle());
            $table->setStyle(clone Table::getStyleDefinition('compact'));

            return $table;
        });
    }

    /**
     * Get collection of all unique containers.
     */
    protected function uniqueContainers(): array
    {
        return $this->cache('uniqueContainers', function () {
            return array_unique(array_reduce($this->containers, function ($result, $item) {
                if (!is_array($result)) {
                    $result = [];
                }

                return array_merge($result, array_values($item));
            }, []));
        });
    }

    /**
     * Get YML file parser.
     */
    protected function ymlParser(): Parser
    {
        return $this->cache('ymlParser', function () {
            return new Parser();
        });
    }

    protected function machineIp(): string
    {
        return $this->cache('machineIp', function () {
            $fixedIp = $this->getHelper('config')->getConfig('sites.ip');
            if (!empty($fixedIp)) {
                return $fixedIp;
            }

            return $this->getMachineIp();
        });
    }

    /**
     * Get command output for all active containers.
     */
    protected function activeContainers(): string
    {
        return $this->cache('activeContainers', function () {
            return (string) $this->getShellHelper()->exec('docker ps')->getOutput();
        });
    }

    /**
     * Get collection of ports or add value to the collection.
     */
    protected function ports(string $key = '', string $port = ''): array
    {
        if (is_null(self::$cache['ports'])) {
            self::$cache['ports'] = [];
        }

        if ($port !== '') {
            self::$cache['ports'][$key] = $port;
        }

        return self::$cache['ports'];
    }

    /**
     * Helper method to get value from an internal cache.
     *
     * @return mixed
     */
    protected function cache(string $name, callable $fetch)
    {
        if (empty(self::$cache[$name])) {
            self::$cache[$name] = $fetch();
        }

        return self::$cache[$name];
    }
}
