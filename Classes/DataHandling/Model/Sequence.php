<?php
namespace TYPO3Incubator\Data\DataHandling\Model;

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
use TYPO3\CMS\Core\Utility\ArrayUtility;

class Sequence extends \ArrayObject
{

    /**
     * @return Sequence
     */
    static public function create() {
        return GeneralUtility::makeInstance(get_called_class());
    }

    public function purge() {
        foreach ($this as $tableName => $elementIdCollection) {
            foreach ($elementIdCollection as $elementId => $itemCollection) {
                if (empty($itemCollection)) {
                    unset($this[$tableName][$elementId]);
                }
            }
            if (empty($this[$tableName])) {
                unset($this[$tableName]);
            }
        }
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return (count($this) === 0);
    }

    /**
     * @param Sequence $data
     */
    public function replace(Sequence $data) {
        $this->exchangeArray($data->getArrayCopy());
    }

    /**
     * @param Sequence $data
     */
    public function mergeToEnd(Sequence $data) {
        $target = $this->getArrayCopy();
        ArrayUtility::mergeRecursiveWithOverrule($target, $data->getArrayCopy());
        $this->exchangeArray($target);
    }

    /**
     * @param Sequence $data
     */
    public function mergeToFront(Sequence $data) {
        $target = $data->getArrayCopy();
        ArrayUtility::mergeRecursiveWithOverrule($target, $this->getArrayCopy());
        $this->exchangeArray($target);
    }

}