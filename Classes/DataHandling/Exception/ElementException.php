<?php
namespace TYPO3Incubator\Data\DataHandling\Exception;

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

class ElementException extends \RuntimeException
{

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var int
     */
    protected $id;

    public function setTableName($tableName) {
        $this->tableName = $tableName;
    }

    public function getTableName() {
        return $this->tableName;
    }

    public function setId($id) {
        $this->id = (int)$id;
    }

    public function getId() {
        return $this->id;
    }

}