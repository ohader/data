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
use TYPO3Incubator\Data\DataHandling\Model\Sequence;

class GenericAspect extends AbstractAspect
{

    public function process() {
        foreach ($this->getSortedOuterMostParents() as $outerMostParent) {
            $sequence = $this->createSequence($outerMostParent);
            if ($sequence->isEmpty()) {
                continue;
            }
            if (count($outerMostParent->getNestedChildren()) > 0) {
                $this->mapSequencer->getOrderedSequence($outerMostParent, true)->mergeToFront($sequence);
            } else {
                $this->mapSequencer->getFinalSequence()->mergeToEnd($sequence);
            }
        }

        $this->mapSequencer->setMap($this->map);
    }

    /**
     * @param Dependency\ElementEntity $parentElement
     * @return Sequence
     */
    protected function createSequence(Dependency\ElementEntity $parentElement) {
        $sequence = Sequence::create();
        $resolver = $this->mapSequencer->getDependencyResolver();

        /** @var Dependency\ElementEntity $nestedElement */
        foreach ($resolver->getNestedElements($parentElement) as $nestedElement) {
            $tableName = $nestedElement->getTable();
            $elementId = $nestedElement->getId();

            // Skip if element was not set in initial map
            if (empty($this->map[$tableName][$elementId])) {
                continue;
            }

            $itemCollection = $nestedElement->getDataValue('itemCollection');
            // Skip if item collection is empty
            if (empty($itemCollection)) {
                continue;
            }

            $relevantItems = $this->processRelevantItems($itemCollection);
            // Skip if relevant items are empty
            if (empty($relevantItems)) {
                continue;
            }

            // Add to local sequence
            $sequence[$tableName][$elementId] = $relevantItems;
            // Remove from initial  map
            $this->map[$tableName][$elementId] = array_diff_key(
                $this->map[$tableName][$elementId],
                $this->getRelevantItems()
            );
        }

        // Purge from initial map
        $this->map = $this->purgeMap($this->map);
        return $sequence;
    }

}