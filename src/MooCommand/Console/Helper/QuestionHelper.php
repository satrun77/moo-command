<?php

/*
 * This file is part of the MooCommand package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MooCommand\Console\Helper;

use MooCommand\Console\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class QuestionHelper.
 */
class QuestionHelper extends Helper
{
    /**
     * @param string|array $question
     *
     * @return mixed
     */
    public function confirmAsk($question, int $default = 0)
    {
        $command = $this->getCommand();
        $command->getOutputStyle()->question($question);
        $yesNoString = $command->getOutputStyle()->formatLine('[yes/no]', 'fg=green');
        $question = new ConfirmationQuestion($yesNoString . "\n> ", (bool) $default);

        return $this->getHelper()->ask($command->getInput(), $command->getOutput(), $question);
    }

    /**
     * @param array|string $question
     *
     * @return mixed
     */
    public function ask($question)
    {
        $command = $this->getCommand();
        $command->getOutputStyle()->question($question);
        $question = new Question('> ');

        return $this->getHelper()->ask($command->getInput(), $command->getOutput(), $question);
    }

    /**
     * Ask user a question with selection of answers to choice from.
     *
     * @return false|int|string
     */
    public function choices(string $questionText, array $choices, string $default)
    {
        $command = $this->getCommand();

        // Format question text with question style
        $questionText = $command->getOutputStyle()->formatLine($questionText, 'question', 'QUESTION');

        // Create an instance of choice question
        $question = new ChoiceQuestion($questionText, $choices, $default);

        // Ask the question
        $answer = $this->getHelper()->ask($command->getInput(), $command->getOutput(), $question);

        // Return the index of selected answer or the answer if index can't be found
        $index = array_search($answer, $choices, true);
        if ($index !== false) {
            $answer = $index;
        }

        return $answer;
    }

    /**
     * Ask the user question and validate the answer.
     *
     * @param string $default
     */
    public function askAndValidate(string $questionText, callable $validator, string $default = null): string
    {
        // Instance of the current command
        $command = $this->getCommand();

        // If you have question text then display it in question style
        if (!empty($questionText)) {
            $command->getOutputStyle()->question($questionText);
        }
        // Create instance of question with validator and max attempts of 10
        $question = new Question('> ', $default);
        $question->setValidator($validator);
        $question->setMaxAttempts(10);

        // Ask the question
        return (string) $this->getHelper()->ask($command->getInput(), $command->getOutput(), $question);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName(): string
    {
        return 'moo_question';
    }

    /**
     * Get instance of symfony question helper.
     */
    protected function getHelper(): BaseQuestionHelper
    {
        return $this->getHelperSet()->get('question');
    }

    /**
     * Get instance of the current command line class.
     */
    protected function getCommand(): Command
    {
        return $this->getHelperSet()->getCommand();
    }
}
