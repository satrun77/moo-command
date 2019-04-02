#!/usr/bin/env php
<?php
/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (PHP_SAPI !== 'cli') {
    echo 'Warning: app should be invoked via the CLI version of PHP, not the ' . PHP_SAPI . ' SAPI' . PHP_EOL;
}

if (!PHP_VERSION_ID >= 70100) {
    die("PHP version 7.1 or above is required for the command line tool.\n");
}

define('__APP_DIR__', __DIR__);

require_once __APP_DIR__ . '/vendor/autoload.php';

use MooCommand\Command;
use MooCommand\Console\Helper\ConfigHelper;
use MooCommand\Console\Helper\QuestionHelper;
use MooCommand\Console\Helper\ShellHelper;
use Symfony\Component\Console\Application;

$application = new Application('Moo Development Console', '1.0.0-alpha8');

// Console helpers
$application->getHelperSet()->set(new ConfigHelper());
$application->getHelperSet()->set(new ShellHelper());
$application->getHelperSet()->set(new QuestionHelper());

// Console command lines
$application->add(new Command\Commit('commit', $application));
$application->add(new Command\CsFixer());
$application->add(new Command\CodeQuality());
$application->add(new Command\Faq());
$application->add(new Command\Workspace\ListSites());
$application->add(new Command\Workspace\Cleanup());
$application->add(new Command\Workspace\MachineIp());
$application->add(new Command\Workspace\ContainerIp());
$application->add(new Command\Workspace\Hosts());
$application->add(new Command\Workspace\Build());
$application->add(new Command\Workspace\Start());
$application->add(new Command\Workspace\Stop());
$application->add(new Command\Workspace\Remove());
$application->add(new Command\Workspace\Proxy());
$application->add(new Command\Workspace\Ssh());
$application->add(new Command\Workspace\Sh());
$application->add(new Command\Workspace\Frontend());
$application->add(new Command\Workspace\Log());
$application->add(new Command\Workspace\Create());
$application->add(new Command\Workspace\Composer());
$application->add(new Command\Workspace\Cp());
$application->add(new Command\Workspace\Stat());

// Start console app
$application->run();
