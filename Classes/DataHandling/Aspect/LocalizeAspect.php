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
use TYPO3Incubator\Data\DataHandling\Exception\DependencyException;

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
        $candidateElements = $this->determineCandidateElements($parentElement);
        $reverseCandidateElements = array_reverse($candidateElements);

        // Validate commands
        foreach ($candidateElements as $candidateElement) {
            $this->validate($candidateElement, $sequence);
        }

        // Process child element bottom-up since they might rely
        // on process instructions that are defined for the parent
        foreach ($reverseCandidateElements as $candidateElement) {
            $this->reduce($candidateElement, $sequence);
            $this->correct($candidateElement, $sequence);
        }

        // Purge sequence
        $sequence->purge();
    }

    /**
     * @param Dependency\ElementEntity $candidateElement
     * @param Sequence $sequence
     */
    protected function reduce(Dependency\ElementEntity $candidateElement, Sequence $sequence) {
        $parentReference = $this->getInlineParentReference($candidateElement);
        // Skip if no inline parent reference was found
        if (empty($parentReference)) {
            return;
        }

        $candidateTableName = $candidateElement->getTable();
        $candidateId = $candidateElement->getId();

        if ($this->shallRemoveChildElement($parentReference, $sequence)) {
            unset($sequence[$candidateTableName][$candidateId]['localize']);
        }

        // Purge sequence
        $sequence->purge();
    }

    /**
     * @param Dependency\ElementEntity $candidateElement
     * @param Sequence $sequence
     */
    protected function correct(Dependency\ElementEntity $candidateElement, Sequence $sequence) {
        $parentReference = $this->getInlineParentReference($candidateElement);
        // Skip if no inline parent reference was found
        if (empty($parentReference)) {
            return;
        }

        $candidateTableName = $candidateElement->getTable();
        $candidateId = $candidateElement->getId();

        if ($this->shallLocalizeForParentElement($parentReference, $candidateElement, $sequence)) {
            $parentTableName = $parentReference->getElement()->getTable();
            $parentId = $parentReference->getElement()->getId();

            if (empty($sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'])) {
                $itemCollection = $candidateElement->getDataValue('itemCollection');
                $sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'] = array(
                    'field' => $parentReference->getField(),
                    'language' => $itemCollection['localize'],
                    'ids' => array(),
                );
            }

            $sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize']['ids'][] = $candidateId;
            unset($sequence[$candidateTableName][$candidateId]['localize']);
        }

        // Purge sequence
        $sequence->purge();
    }

    /**
     * @param Dependency\ElementEntity $candidateElement
     * @param Sequence $sequence
     */
    protected function validate(Dependency\ElementEntity $candidateElement, Sequence $sequence) {
        $parentReference = $this->getInlineParentReference($candidateElement);
        // Skip if no inline parent reference was found
        if (empty($parentReference)) {
            return;
        }

        if ($this->shallFail($parentReference, $candidateElement, $sequence)) {
            $exception = new DependencyException('Element "' . $candidateElement . '" cannot be localized independently');
            $exception->setTableName($candidateElement->getTable());
            $exception->setId($candidateElement->getId());
            throw $exception;
        }
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
        $fieldConfiguration = $this->resolveFieldConfiguration($parentReference);

        $parentTableName = $parentReference->getElement()->getTable();
        $parentId = $parentReference->getElement()->getId();

        // If parent is part of the initial map and
        // children shall be localized automatically with parent using the selective mode
        if (!empty($sequence[$parentTableName][$parentId]['localize'])
            && (
                !empty($fieldConfiguration['behaviour']['localizeChildrenAtParentLocalization'])
                && !empty($fieldConfiguration['behaviour']['localizationMode'])
                && $fieldConfiguration['behaviour']['localizationMode'] === 'select'
            )
        ) {
            return true;
        }

        // If parent is localized already and generic
        // inlineLocalizeSynchronize is activated for that field
        #if (!empty($sequence[$parentTableName][$parentId]['localize'])
        #)

        return false;
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
        $fieldConfiguration = $this->resolveFieldConfiguration($parentReference);

        $parentTableName = $parentReference->getElement()->getTable();
        $parentId = $parentReference->getElement()->getId();
        $childTableName = $childElement->getTable();
        $childId = $childElement->getId();

        // Child element shall not be localized
        if (empty($sequence[$childTableName][$childId]['localize'])) {
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
            $parentTableName,
            $parentId,
            $language
        );

        // If neither parent localization does exist nor is parent part of the initial map
        if (empty($parentLocalizationRecord) &&
            empty($sequence[$parentTableName][$parentId]['localize'])) {
            return false;
        }

        return true;
    }

    protected function shallFail(Dependency\ReferenceEntity $parentReference, Dependency\ElementEntity $childElement, Sequence $sequence) {
        $parentTableName = $parentReference->getElement()->getTable();
        $parentId = $parentReference->getElement()->getId();
        $childTableName = $childElement->getTable();
        $childId = $childElement->getId();

        // Child element shall not be localized
        if (empty($sequence[$childTableName][$childId]['localize'])) {
            return false;
        }

        // Parent element gets localized
        if (!empty($sequence[$parentTableName][$parentId]['localize'])) {
            return false;
        }

        // Modernized inlineLocalizeSynchronize commands
        if (!empty($sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'])
            && is_array($sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'])
            && $sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize']['field'] === $parentReference->getField()
            && in_array($childId, $sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize']['ids'])
        ) {
            return false;
        }

        // Legacy inlineLocalizeSynchronize commands
        if (!empty($sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'])
            && is_string($sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'])
            && in_array($sequence[$parentTableName][$parentId]['inlineLocalizeSynchronize'], array(
                $parentReference->getField() . ',localize',
                $parentReference->getField() . ',synchronize',
                $parentReference->getField() . ',' . $childId,
            ))
        ) {
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