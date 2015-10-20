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

class Sequence
{

    /**
     * @return Sequence
     */
    static public function create() {
        return GeneralUtility::makeInstance(get_called_class());
    }

    /**
     * @var array
     */
    protected $data = array();

    public function set(array $data) {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function get() {
        return $this->data;
    }

    public function isEmpty() {
        return empty($this->data);
    }

    /**
     * @param array $data
     */
    public function mergeToEnd(array $data) {
        ArrayUtility::mergeRecursiveWithOverrule($this->data, $data);
    }

    /**
     * @param array $data
     */
    public function mergeToFront(array $data) {
        ArrayUtility::mergeRecursiveWithOverrule($data, $this->data);
        $this->data = $data;
    }

}