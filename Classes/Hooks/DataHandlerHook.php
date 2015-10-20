<?php
namespace TYPO3Incubator\Data\Hooks;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3Incubator\Data\DataHandling\Sequencer;

class DataHandlerHook implements SingletonInterface
{

    /**
     * @return string
     */
    static public function className() {
        return __CLASS__;
    }

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @return bool
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler) {
        if ($this->isActive()) {
            return;
        }

        $this->active = true;
        Sequencer\DataMapSequencer::create()->process($dataHandler);
        $this->active = false;
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_beforeStart(DataHandler $dataHandler) {
        if ($this->isActive()) {
            return;
        }

        $this->active = true;
        Sequencer\CommandMapSequencer::create()->process($dataHandler);
        $this->active = false;
    }

}