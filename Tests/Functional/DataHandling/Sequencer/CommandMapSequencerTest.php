<?php
namespace TYPO3Incubator\Data\Tests\Functional\DataHandling\Sequencer;

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

require_once dirname(dirname(__FILE__)) . '/AbstractActionTestCase.php';

use TYPO3Incubator\Data\Tests\Functional\DataHandling\AbstractActionTestCase;

class CommandMapSequencerTest extends AbstractActionTestCase
{

    /**
   	 * @var string
   	 */
   	protected $assertionDataSetDirectory = 'EXT:data/Tests/Functional/DataHandling/Sequencer/DataSet/CommandMapSequencer/';

    /**
     * @test
     */
    public function localizeContentElement() {
        parent::localizeContentElement();
        $this->assertAssertionDataSet('localizeContentElement');
    }

    /**
     * @test
     */
    public function localizeContentElementsWithChildrenWithoutAutomation() {
        parent::localizeContentElementsWithChildrenWithoutAutomation();
        $this->assertAssertionDataSet('localizeContentElementsWithChildrenWithoutAutomation');
    }

    /**
     * @test
     */
    public function localizeContentElementsWithChildrenWithAutomation() {
        parent::localizeContentElementsWithChildrenWithAutomation();
        $this->assertAssertionDataSet('localizeContentElementsWithChildrenWithAutomation');
    }

}