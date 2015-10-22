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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Version\Dependency\DependencyResolver;
use TYPO3Incubator\Data\DataHandling\Aspect;
use TYPO3Incubator\Data\DataHandling\Model\Sequence;

abstract class AbstractMapSequencer
{

    /**
     * @return static|AbstractMapSequencer
     */
    static public function create() {
        return GeneralUtility::makeInstance(get_called_class());
    }

    /**
     * @var array
     */
    protected $map;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var DependencyResolver
     */
    protected $dependencyResolver;

    /**
     * @var Sequence[]
     */
    protected $orderedSequences;

    /**
     * @var Sequence
     */
    protected $finalSequence;

    /**
     * @var Aspect\AbstractAspect[]
     */
    protected $aspects;

    public function __construct() {
        $this->addAspect(Aspect\GenericAspect::create());
        $this->orderedSequences = new \ArrayObject();
        $this->finalSequence = Sequence::create();
    }

    public function setMap(array $map) {
        $this->map = $map;
    }

    public function getMap() {
        return $this->map;
    }

    /**
     * @return DataHandler
     */
    public function getDataHandler() {
        return $this->dataHandler;
    }

    public function getOrderedSequences() {
        return $this->orderedSequences;
    }

    /**
     * @param string $identifier
     * @param bool|false $autoCreate
     * @return null|Sequence
     */
    public function getOrderedSequence($identifier, $autoCreate = false) {
        $identifier = (string)$identifier;

        if ($this->orderedSequences->offsetExists($identifier)) {
            return $this->orderedSequences->offsetGet($identifier);
        }

        if (!$autoCreate) {
            return null;
        }

        $sequence = Sequence::create();
        $this->orderedSequences->offsetSet($identifier, $sequence);
        return $sequence;
    }

    public function getFinalSequence() {
        return $this->finalSequence;
    }

    /**
     * @return Sequence[]
     */
    public function getSequences() {
        /** @var Sequence[] $sequences */
        $sequences = array_merge(
            $this->orderedSequences->getArrayCopy(),
            array('final' => $this->finalSequence)
        );

        foreach ($sequences as $identifier => $sequence) {
            if ($sequence->isEmpty()) {
                unset($sequences[$identifier]);
            }
        }

        return $sequences;
    }

    public function process(DataHandler $dataHandler) {
        $this->dataHandler = $dataHandler;
        $this->prepare();

        if ($this->collectElements() > 0) {
            $this->processAspects();
            $this->processSequences();
            $this->finish();
        }

        return $this;
    }

    abstract protected function prepare();

    abstract protected function processSequences();

    abstract protected function finish();

    /**
     * @param Aspect\AbstractAspect $aspect
     */
    protected function addAspect(Aspect\AbstractAspect $aspect) {
        $this->aspects[get_class($aspect)] = $aspect;
    }

    /**
     * @return DependencyResolver
     */
    public function getDependencyResolver() {
        if (isset($this->dependencyResolver)) {
            return $this->dependencyResolver;
        }

        $this->dependencyResolver = $this->createDependencyResolver();

        return $this->dependencyResolver;
    }

    /**
     * @return DependencyResolver
     */
    public function createDependencyResolver() {
        /** @var DependencyResolver $dependencyResolver */
        $dependencyResolver = GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Dependency\\DependencyResolver');
        return $dependencyResolver;
    }

    /**
     * @return int
     */
    protected function collectElements() {
        foreach ($this->map as $tableName => $elementIDCollection) {
            foreach ($elementIDCollection as $elementId => $itemCollection) {
                // @todo Remove this check once DependencyResolver is able to work with meta-domain-models as well
                if (!MathUtility::canBeInterpretedAsInteger($elementId)) {
                    continue;
                }
                $this->getDependencyResolver()->addElement($tableName, $elementId, array('itemCollection' => $itemCollection));
            }
        }
        return count($this->getDependencyResolver()->getElements());
    }

    protected function processAspects() {
        foreach ($this->aspects as $aspect) {
            $aspect->setDataHandler($this->dataHandler);
            $aspect->setMapSequencer($this);
            $aspect->setMap($this->map);
            $aspect->process();
        }
    }

}