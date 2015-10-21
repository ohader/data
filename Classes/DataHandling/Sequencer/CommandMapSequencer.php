<?php
namespace TYPO3Incubator\Data\DataHandling\Sequencer;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3Incubator\Data\DataHandling\Aspect;

class CommandMapSequencer extends AbstractMapSequencer
{

    public function __construct() {
        parent::__construct();
        $this->addAspect(
            Aspect\GenericAspect::create()
                ->setRelevantItemNames(array(
                    'move',
                    'copy',
                    'localize',
                    'inlineLocalizeSynchronize',
                    'delete',
                    'undelete'
                ))
        );
        $this->addAspect(
            Aspect\LocalizeAspect::create()
                ->setRelevantItemNames(array(
                    'localize'
                ))
        );
    }

    protected function prepare() {
        $this->map = $this->dataHandler->cmdmap;
    }

    protected function processSequences() {
        foreach ($this->getSequences() as $sequence) {
            if ($sequence->isEmpty()) {
                continue;
            }
            $this->dataHandler->start(array(), $sequence->get());
            $this->dataHandler->process_cmdmap();
        }
    }

    protected function finish() {
        $this->dataHandler->cmdmap = $this->map;
    }

}