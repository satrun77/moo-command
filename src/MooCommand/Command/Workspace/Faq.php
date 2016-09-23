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
        $faqs = $this->getConfigHelper()->getResource('faqs.yml');

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
}
