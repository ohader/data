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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Version\Dependency;
use TYPO3Incubator\Data\DataHandling\Model\Sequence;

class LocalizeAspect extends AbstractAspect
{

    public function process() {
        foreach ($this->getSortedOuterMostParents() as $outerMostParent) {
            $sequence = $this->mapSequencer->getOrderedSequence($outerMostParent);
            if ($sequence !== null) {
                $this->adjust($outerMostParent, $sequence);
            }
        }
    }

    /**
     * @param Dependency\ElementEntity $parentElement
     * @param Sequence $sequence
     */
    protected function adjust(Dependency\ElementEntity $parentElement, Sequence $sequence) {
        $sequenceMap = $sequence->get();

        foreach ($this->determineCandidateElements($parentElement) as $candidateElement) {
            $parentReference = $this->getInlineParentReference($candidateElement);
            // Skip if no inline parent reference was found
            if (empty($parentReference)) {
                continue;
            }

            $candidateTableName = $candidateElement->getTable();
            $candidateId = $candidateElement->getId();

            if ($this->shallRemoveChildElement($parentReference, $sequence)) {
                unset($sequenceMap[$candidateTableName][$candidateId]['localize']);
            } elseif ($this->shallLocalizeForParentElement($parentReference, $candidateElement, $sequence)) {
                $parentTableName = $parentReference->getElement()->getTable();
                $parentId = $parentReference->getElement()->getId();

                $sequenceMap[$parentTableName][$parentId]['inlineLocalizeSynchronize']
                    = $parentReference->getField() . ',' . $candidateId;
                unset($sequenceMap[$candidateTableName][$candidateId]['localize']);
            }
        }

        // Purge from initial map
        $sequenceMap = $this->purgeMap($sequenceMap);
        $sequence->set($sequenceMap);
    }

    /**
     * Determines candidate elements that shall be localized in general.
     *
     * @param Dependency\ElementEntity $parentElement
     * @return Dependency\ElementEntity[]
     */
    protected function determineCandidateElements(Dependency\ElementEntity $parentElement) {
        $candidates = array();
        $resolver = $this->mapSequencer->getDependencyResolver();

        /** @var Dependency\ElementEntity $nestedElement */
        foreach ($resolver->getNestedElements($parentElement) as $nestedElement) {
            $itemCollection = $nestedElement->getDataValue('itemCollection');
            // Skip if item collection is empty
            if (empty($itemCollection)) {
                continue;
            }

            $relevantItems = $this->processRelevantItems($itemCollection);
            // Skip if relevant items are empty
            if (empty($relevantItems) || empty($relevantItems['localize'])) {
                continue;
            }

            $candidates[(string)$nestedElement] = $nestedElement;
        }

        return $candidates;
    }

    /**
     * Gets the parent reference being of type "inline" (IRRE).
     *
     * @param Dependency\ElementEntity $childElement
     * @return null|Dependency\ReferenceEntity
     */
    protected function getInlineParentReference(Dependency\ElementEntity $childElement) {
        $parentReferences = $childElement->getParents();
        if (count($parentReferences) !== 1) {
            return null;
        }

        $parentReference = $parentReferences[0];
        $fieldConfiguration = $this->resolveFieldConfiguration($parentReference);

        if (empty($fieldConfiguration)
            || !GeneralUtility::inList('field,list', $this->dataHandler->getInlineFieldType($fieldConfiguration))
        ) {
            return null;
        }

        return $parentReference;
    }

    /**
     * Determines whether a child element can be removed from being localized.
     * That's the case if the parent element is localized in the same run which
     * has a TCA setting that automatically triggers localization of children.
     *
     * @param Dependency\ReferenceEntity $parentReference
     * @param Sequence $sequence
     * @return bool
     */
    protected function shallRemoveChildElement(Dependency\ReferenceEntity $parentReference, Sequence $sequence) {
        $sequenceMap = $sequence->get();
        $parentElement = $parentReference->getElement();
        $fieldConfiguration = $this->resolveFieldConfiguration($parentReference);

        // If children shall not be localized automatically with parent using the selective mode
        if (empty($fieldConfiguration['localizeReferencesAtParentLocalization'])
            && empty($fieldConfiguration['behaviour']['localizeChildrenAtParentLocalization'])
            && (empty($fieldConfiguration['behaviour']['localizationMode'])
                || $fieldConfiguration['behaviour']['localizationMode'] !== 'select')
        ) {
            return false;
        }

        // If parent is not part of the initial map
        if (empty($sequenceMap[$parentElement->getTable()][$parentReference->getField()]['localize'])) {
            return false;
        }

        return true;
    }

    /**
     * Determines whether a child element shall be localized for its parent element instead.
     * That's the case if child records are not localized automatically in this run, but can
     * be localized for an (almost) existing parent element.
     *
     * @param Dependency\ReferenceEntity $parentReference
     * @param Dependency\ElementEntity $childElement
     * @param Sequence $sequence
     * @return bool
     */
    protected function shallLocalizeForParentElement(Dependency\ReferenceEntity $parentReference, Dependency\ElementEntity $childElement, Sequence $sequence) {
        $sequenceMap = $sequence->get();
        $parentElement = $parentReference->getElement();
        $fieldConfiguration = $this->resolveFieldConfiguration($parentReference);

        // If child shall be removed instead
        // (just to ensure the process flow has not mixed up)
        if ($this->shallRemoveChildElement($parentReference, $sequence)) {
            return false;
        }

        // If children shall not be localized using the selective mode
        if (empty($fieldConfiguration['behaviour']['localizationMode'])
            || $fieldConfiguration['behaviour']['localizationMode'] !== 'select'
        ) {
            return false;
        }

        $itemCollection = $childElement->getDataValue('itemCollection');
        $language = (int)$itemCollection['localize'];
        $parentLocalizationRecord = BackendUtility::getRecordLocalization(
            $parentReference->getElement()->getTable(),
            $parentReference->getElement()->getId(),
            $language
        );

        // If neither parent localization does exist nor is parent part of the initial map
        if ($parentLocalizationRecord === false &&
            empty($sequenceMap[$parentElement->getTable()][$parentReference->getField()]['localize'])) {
            return false;
        }

        return true;
    }

    /**
     * @todo Resolve FlexForm configurations
     * @param Dependency\ReferenceEntity $referenceEntity
     * @return array
     */
    protected function resolveFieldConfiguration(Dependency\ReferenceEntity $referenceEntity) {
        $fieldConfiguration = BackendUtility::getTcaFieldConfiguration(
            $referenceEntity->getElement()->getTable(),
            $referenceEntity->getField()
        );

        return $fieldConfiguration;
    }

}