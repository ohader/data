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

use TYPO3\CMS\Version\Dependency;

class GenericAspect extends AbstractAspect
{

    public function process() {
        foreach ($this->mapSequencer->getDependencyResolver()->getOuterMostParents() as $outerMostParent) {
            $sequenceMap = $this->createMap($outerMostParent);
            if (empty($sequenceMap)) {
                continue;
            }
            if (count($outerMostParent->getNestedChildren()) > 0) {
                $this->mapSequencer->getOrderedSequence($outerMostParent, true)->set($sequenceMap);
            } else {
                $this->mapSequencer->getFinalSequence()->mergeToEnd($sequenceMap);
            }
        }

        $this->mapSequencer->setMap($this->map);
    }

    /**
     * @param Dependency\ElementEntity $parentElement
     * @return array
     */
    protected function createMap(Dependency\ElementEntity $parentElement) {
        $sequenceMap = array();
        $resolver = $this->mapSequencer->getDependencyResolver();

        /** @var Dependency\ElementEntity $nestedElement */
        foreach ($resolver->getNestedElements($parentElement) as $nestedElement) {
            $relevantItems = array_intersect_key(
                $nestedElement->getDataValue('itemCollection'),
                $this->getRelevantItems()
            );

            if (empty($relevantItems)) {
                continue;
            }

            $tableName = $nestedElement->getTable();
            $elementId = $nestedElement->getId();
            $sequenceMap[$tableName][$elementId] = $relevantItems;

            $this->map[$tableName][$elementId] = array_diff_key(
                $this->map[$tableName][$elementId],
                $this->getRelevantItems()
            );

            if (empty($this->map[$tableName][$elementId])) {
                unset($this->map[$tableName][$elementId]);
            }
        }

        return $sequenceMap;
    }

}