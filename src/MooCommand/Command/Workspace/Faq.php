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
 * Faq.
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 */
class Faq extends WorkspaceAbstract
{
    const DATA_SOURCE_LOCAL = 'local';
    const DATA_SOURCE_REMOTE = 'url';
    /**
     * @var string
     */
    protected $description = 'Display FAQs.';
    /**
     * @var string
     */
    protected $childSignature = 'faq';

    /**
     * Main method to execute the command script.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function fire()
    {
        // FAQ data
        $faqs = $this->getData();

        // Ask user for a question to answer
        $question = $this->getHelper('dialog')->select(
            $this->getOutput(),
            $this->getOutputStyle()->formatLine('Please select a question', 'question', 'QUESTION'),
            $faqs['questions'],
            0
        );

        // Display the question and the answer
        $this->getOutputStyle()->title($faqs['questions'][$question]);
        $this->getOutputStyle()->info($faqs['answers'][$question]);
    }

    /**
     * Collect FAQ questions from app, remote URL or config file.
     *
     * @return array
     */
    protected function getData()
    {
        // Core data
        $faqs = $this->getConfigHelper()->getResource('faqs.yml');
        $userQuestions = $userAnswers = [];

        // Type of use question source
        $source = $this->getConfigHelper()->getConfig('faqs.source');

        // Load questions/answers from local config
        if ($source === self::DATA_SOURCE_LOCAL) {
            $userQuestions = $this->getConfigHelper()->getConfig('faqs.data.questions');
            $userAnswers = $this->getConfigHelper()->getConfig('faqs.data.answers');
        }

        // Load question/answers from a URL
        if ($source === self::DATA_SOURCE_REMOTE) {
            $remoteQuestions = json_decode(file_get_contents($this->getConfigHelper()->getConfig('faqs.data')));
            $userQuestions = $remoteQuestions->questions;
            $userAnswers = $remoteQuestions->answers;
        }

        // Merge questions and answers
        foreach ($userQuestions as $index => $question) {
            $faqs['questions'][] = $question;
            $faqs['answers'][] = $userAnswers[$index];
        }

        return $faqs;
    }
}
