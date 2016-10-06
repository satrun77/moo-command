<?php
/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command;

use MooCommand\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commit.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Commit extends Command
{
    /**
     * @var bool
     */
    protected $runRoot = false;
    /**
     * @var string
     */
    protected $description = 'Git Commit wrapper to standardise the commit messages.';
    /**
     * @var string
     */
    protected $signature = 'commit';
    /**
     * @var array
     */
    protected $arguments = [
        'message' => [
            'mode'        => InputArgument::REQUIRED,
            'description' => 'Short of message about the change limit to 60 characters.',
        ],
        // Wrap the body at 72 characters
        'details' => [
            'mode'        => InputArgument::OPTIONAL,
            'description' => 'Details description about the change if the message is not clear.',
        ],
    ];

    /**
     * Holds commit details entered by the user.
     *
     * @var array
     */
    protected $commit = [];

    /**
     * If applied, this commit will "update getting started documentation".
     *
     * @var array
     */
    protected $types;

    /**
     * @var array
     */
    protected $options = [
        'dependencies' => [
            'shortcut'    => 'd',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Shortcut commit changes with default message about updating composer.json & composer.lock',
            'default'     => null,
        ],
        'gitignore'    => [
            'shortcut'    => 'i',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Shortcut commit changes with default message about updating .gitignore',
            'default'     => null,
        ],
        'csfixes'      => [
            'shortcut'    => 'c',
            'mode'        => InputOption::VALUE_NONE,
            'description' => 'Shortcut commit changes with default message about CS fixes',
            'default'     => null,
        ],
    ];

    /**
     * Get an array of allowed commit words.
     *
     * @return array
     */
    public function getTypes()
    {
        if (null === $this->types) {
            $this->types = $this->getConfigHelper()->getConfig('commit.words');
            sort($this->types, SORT_NATURAL);
        }

        return $this->types;
    }

    /**
     * Interactive input to be executed by parent method
     * Ask & validate the commit type.
     *
     * @param string $value
     * @param array  $argument
     *
     * @return mixed
     */
    protected function interactInputMessage($value, array $argument)
    {
        $this->getOutputStyle()->info('Acceptable words to start commit with: ');
        $this->getOutputStyle()->line('If applied, this commit will ....your commit....', 'comment');

        $rows = array_chunk($this->getTypes(), 7);
        $this->getOutputStyle()->table([], $rows);

        $question = $this->getOutputStyle()->question('Enter Commit Message: ');

        return $this->getHelper('dialog')->askAndValidate($this->getOutput(), $question, function ($value) {
            if (strlen($value) > 60) {
                $validLength = substr($value, 0, 60);
                $extraLength = substr($value, 60);
                $this->getOutputStyle()->warning($validLength . '<fg=red>' . $extraLength . '</>');
                throw new \InvalidArgumentException('Commit message must not be more than 60 characters.');
            }

            if (empty($value)) {
                throw new \InvalidArgumentException('Commit message must not be empty.');
            }

            $imperativeMood = false;
            foreach ($this->types as $type) {
                if (stripos($value, $type . ' ') === 0) {
                    $imperativeMood = true;
                    break;
                }
            }
            if (!$imperativeMood) {
                $this->getOutputStyle()->error([
                    'Commit message should start with an imperative mood.',
                    'It should be able to complete the following sentence:',
                    'If applied, this commit will <fg=green;bg=red>' . $value . '</fg=green;bg=red>',
                ]);
                $this->getOutputStyle()->note('If you think you are correct, then ask the developer to fix it.');
                throw new \InvalidArgumentException('Please try again');
            }

            return $value;
        });
    }

    /**
     * Interactive input to be executed by parent method
     * Ask for the commit message details.
     *
     * @param string $value
     * @param array  $argument
     *
     * @return mixed
     */
    protected function interactInputDetails($value, array $argument)
    {
        $question = $this->getOutputStyle()->question('Enter Commit Details (optional): ');

        return $this->getHelper('dialog')->ask($this->getOutput(), $question);
    }

    /**
     * Initializes the command just after the input has been validated.
     * Check for special options that will auto commit changes with default message. (ie. update composer.json).
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $isGitIgnore    = $this->option('gitignore');
        $isDependencies = $this->option('dependencies');
        $isCsFixes      = $this->option('csfixes');

        // Disable interactive we have a default message
        if ($isGitIgnore || $isDependencies || $isCsFixes) {
            $extraDetails = $this->argument('message');
            $this->input->setArgument('details', $extraDetails);

            $this->input->setInteractive(false);
        }

        // Set commit .gitignore file
        if ($isGitIgnore) {
            $this->input->setArgument('message', 'Update .gitignore');
        }

        // Set commit composer.json and/or composer.lock
        if ($isDependencies) {
            $this->input->setArgument('message', 'Update Composer dependencies');
        }

        // Set commit CS fixes
        if ($isCsFixes) {
            $this->input->setArgument('message', 'Apply CS fixes');
        }

        // Output the predefined message
        $message = $this->argument('message');
        if (!empty($message)) {
            $this->getOutputStyle()->info($message);
        }
    }

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        // Make sure interactive enabled. This may get disabled in self::initialize
        $this->input->setInteractive(true);

        $this->confirmStagedFiles();

        // Commit message details
        $message = $this->argument('message');
        $details = wordwrap($this->argument('details'), 70);

        // Execute git commit
        $this->getShellHelper()->exec(
            "git commit -m \"%s\n\n%s\"",
            ucfirst($message),
            $details
        );

        // Success message
        $this->getOutputStyle()->success('Changes committed!');

        // Show git status
        $status = $this->getShellHelper()->exec('git status');
        $this->getOutputStyle()->comment($status->getOutput());
    }

    /**
     * Display all of the staged files and ask the user to confirm.
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function confirmStagedFiles()
    {
        // Get list of staged files
        $command = $this->getShellHelper()->exec('git diff --name-only --cached');
        if (empty($command->getOutput())) {
            throw new \Exception('There are no files to commit.');
        }

        // Ask the user if these are the correct changes to commit
        $question = [
            'Are these the files you have changed & would like to commit them?',
            $command->getIterator(),
        ];
        $status = $this->getQuestionHelper()->confirmAsk($question);
        if (!$status) {
            throw new \Exception('Invalid staged files. Commit aborted by user.');
        }

        return $command;
    }
}
