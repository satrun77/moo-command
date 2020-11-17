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
 * Class CategorisedStyle.
 */
class CategorisedStyle implements CommitStyleInterface
{
    /**
     * List of short cut messages.
     *
     * @var array
     */
    protected $shortcutMessages = [
        Commit::SHORTCUT_DEPENDENCIES => 'Misc: update Composer dependencies',
        Commit::SHORTCUT_GITIGNORE => 'Misc: update .gitignore',
        Commit::SHORTCUT_CSFIXES => 'Misc: apply CS fixes',
    ];

    /**
     * Default commit types.
     *
     * @var array
     */
    protected static $DEFAULT_TYPES = [
        'Change' => 'Implemented a change to source code.',
        'Misc' => 'Generic change.',
        'Bug' => 'Fixed a bug.',
        'Update' => 'Update site core code or installed/update modules',
        'Build' => 'build CSS & Javascript',
        'Feature' => 'Implemented a new feature.',
    ];

    /**
     * List of commit types.
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
     * CategorisedStyle constructor.
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
        return 'Categorised';
    }

    /**
     * Return list of arguments for the command.
     */
    public function getArguments(): array
    {
        return [
            'Type', 'Message', 'Issue', 'Details',
        ];
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
            'Issue.Number' => $this,
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
        // Commit message details
        $type = explode(':', $this->command->argument('type'))[0];
        $issue = $this->command->argument('issue');

        return [
            "git commit -m '%s: %s\n\n%s\n%s'",
            $type,
            $message,
            $issue,
            $details,
        ];
    }

    /**
     * Execute code before interactive input type.
     */
    public function beforeInputType(): void
    {
        $this->command->getOutputStyle()->info('Message structured as,');
        $this->command->getOutputStyle()->line([
            'Type: short message',
            '-- empty line --',
            'Issue number',
            'Optional details...',
        ], 'comment');
    }

    /**
     * Interactive input to be executed by commit command.
     * Ask & validate the commit type.
     */
    public function interactInputType(): string
    {
        return $this->command->getQuestionHelper()->choices(
            'Please select the commit type: ',
            $this->getTypes(),
            1
        );
    }

    /**
     * Interactive input to be executed by commit command.
     * Ask & validate the commit issue number.
     */
    public function interactInputIssue(): string
    {
        if (!$this->command->option('oneline')) {
            $this->command->getOutputStyle()->question('Enter Commit Issue No.:  ');

            return $this->command->validator('', 'Issue');
        }

        return '';
    }

    /**
     * Validate the issue number.
     *
     * @param string $value
     */
    public function validateIssueNumber(?string $value): string
    {
        $value = mb_strtoupper($value);
        $segments = explode('-', $value);

        if (empty($segments[0])) {
            throw new \InvalidArgumentException('Project key is missing from your issue no.');
        }

        if (empty($segments[1])) {
            throw new \InvalidArgumentException('Issue number is in incorrect format.');
        }

        return $value;
    }

    /**
     * Get an array of commit types (categories).
     */
    public function getTypes(): array
    {
        if (null === $this->types) {
            $this->types = array_merge(
                $this->command->getHelper('config')->getConfig('commit.categories'),
                self::$DEFAULT_TYPES
            );
            ksort($this->types, SORT_NATURAL);
        }

        return $this->types;
    }
}
