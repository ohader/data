<?php
namespace TYPO3Incubator\Data\DataHandling\Aspect;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3Incubator\Data\DataHandling\Sequencer\AbstractMapSequencer;

abstract class AbstractAspect implements SingletonInterface
{

    /**
     * @return static|AbstractAspect
     */
    static public function create() {
        return GeneralUtility::makeInstance(get_called_class());
    }

    /**
     * @var string[]
     * @internal
     */
    protected $relevantItems;

    /**
     * @var string[]
     */
    protected $relevantItemNames;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var AbstractMapSequencer
     */
    protected $mapSequencer;

    /**
     * @var array
     */
    protected $map;

    abstract public function process();

    public function setDataHandler(DataHandler $dataHandler) {
        $this->dataHandler = $dataHandler;
    }

    public function setMapSequencer(AbstractMapSequencer $mapSequencer) {
        $this->mapSequencer = $mapSequencer;
    }

    public function setMap(array $map) {
        $this->map = $map;
    }

    public function getMap() {
        return $this->map;
    }

    /**
     * @param array $relevantItemNames
     * @return $this|AbstractAspect
     */
    public function setRelevantItemNames(array $relevantItemNames) {
        $this->relevantItemNames = $relevantItemNames;
        return $this;
    }

    /**
     * @return string[]
     */
    protected function getRelevantItems() {
        if (isset($this->relevantItems)) {
            return $this->relevantItems;
        }

        $this->relevantItems = array_combine(
            $this->relevantItemNames,
            $this->relevantItemNames
        );
        return $this->relevantItemNames;
    }

    /**
     * @param array $itemCollection
     * @return array
     */
    protected function processRelevantItems(array $itemCollection) {
        return array_intersect_key(
            $itemCollection,
            $this->getRelevantItems()
        );
    }

    /**
     * @param array $map
     * @return array
     */
    protected function purgeMap(array $map) {
        foreach ($map as $tableName => $elementIdCollection) {
            foreach ($elementIdCollection as $elementId => $itemCollection) {
                if (empty($itemCollection)) {
                    unset($map[$tableName][$elementId]);
                }
            }
            if (empty($map[$tableName])) {
                unset($map[$tableName]);
            }
        }
        return $map;
    }

}