<?php

/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Command\Commit;

use MooCommand\Command\Commit;

/**
 * Class ImperativeMoodStyle.
 */
class ImperativeMoodStyle implements CommitStyleInterface
{
    /**
     * List of short cut messages.
     *
     * @var array
     */
    protected $shortcutMessages = [
        Commit::SHORTCUT_DEPENDENCIES => 'Update Composer dependencies',
        Commit::SHORTCUT_GITIGNORE => 'Update .gitignore',
        Commit::SHORTCUT_CSFIXES => 'Apply CS fixes',
    ];

    /**
     * List of commit allowed words.
     *
     * @var array
     */
    protected $types;

    /**
     * Instance of commit command class.
     *
     * @var Commit
     */
    protected $command;

    /**
     * ImperativeMoodStyle constructor.
     */
    public function __construct(Commit $command)
    {
        $this->command = $command;
    }

    /**
     * Return the display name of the commit style.
     */
    public function getDisplayName(): string
    {
        return 'Imperative Mood';
    }

    /**
     * Return list of arguments for the command.
     */
    public function getArguments(): array
    {
        $arguments = (array) $this->command->getHelper('config')->getConfig('commit.arguments');
        if (empty($arguments)) {
            $arguments = ['Message', 'Details'];
        }

        return $arguments;
    }

    /**
     * Return list of extra options to be added to the commit defaults.
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * Return list of short cut options.
     */
    public function getShortcutOptions(): array
    {
        return [];
    }

    /**
     * Return list extra validators.
     *
     * [Input name].[Validator name] => class containing the validator.
     * (ie. 'Issue.Number' for method validateIssueNumber)
     */
    public function getValidators(): array
    {
        return [
            'Message.ImperativeMood' => $this,
        ];
    }

    /**
     * Return message for a short option.
     */
    public function getShortcutMessage(string $shortcutOption): string
    {
        return $this->shortcutMessages[$shortcutOption];
    }

    /**
     * Return details for a short option.
     */
    public function getShortcutDetails(string $shortcutOption): string
    {
        return '';
    }

    /**
     * Return an array of arguments for the commit command.
     */
    public function getCommitCommand(string $message, string $details): array
    {
        $format = (string) $this->command->getHelper('config')->getConfig('commit.format');
        if (!empty($format)) {
            // Commit message details
            $arguments = $this->getArguments();
            foreach ($arguments as $argument) {
                $argumentValue = (string) $this->command->argument(mb_strtolower($argument));
                $format = str_replace('{' . $argument . '}', $argumentValue, $format);
            }
        } else {
            $format = sprintf("%s\n\n%s", trim($message), $details);
        }

        return [
            'git commit -m "%s"',
            $format,
        ];
    }

    /**
     * Execute code before interactive input message.
     */
    public function beforeInputMessage(): void
    {
        $this->command->getOutputStyle()->info('Acceptable words to start commit with: ');
        $this->command->getOutputStyle()->line('If applied, this commit will ....your commit....', 'comment');

        $rows = array_chunk($this->getTypes(), 7);
        $this->command->getOutputStyle()->table([], $rows);
    }

    /**
     * Interactive input to be executed by commit command.
     * Ask & validate the commit issue number.
     */
    public function interactInputIssue(): string
    {
        $issueNumber = $this->command->findTicketFromBranch();
        $question = 'Enter your issue number: ';
        if (!empty($issueNumber)) {
            $question .= '(default: ' . $issueNumber . ') ';
        }
        $this->command->getOutputStyle()->question($question);

        return (string) $this->command->validator('', 'Ticket', $issueNumber);
    }

    /**
     * Validate the first word of the input message.
     */
    public function validateMessageImperativeMood(string $value): string
    {
        $imperativeMood = false;
        foreach ($this->types as $type) {
            if (0 === mb_stripos($value, $type . ' ')) {
                $imperativeMood = true;
                break;
            }
        }

        if (!$imperativeMood) {
            $this->command->getOutputStyle()->error([
                'Commit message should start with an imperative mood.',
                'It should be able to complete the following sentence:',
                'If applied, this commit will <fg=green;bg=red>' . $value . '</fg=green;bg=red>',
            ]);
            $this->command->getOutputStyle()->note('If you think you are correct, then ask the developer to fix it.');
            throw new \InvalidArgumentException('Please try again');
        }

        return ucfirst($value);
    }

    /**
     * Get an array of allowed commit words.
     */
    public function getTypes(): array
    {
        if (null === $this->types) {
            $this->types = $this->command->getHelper('config')->getConfig('commit.words');
            sort($this->types, SORT_NATURAL);
        }

        return $this->types;
    }
}
