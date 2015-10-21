<?php
namespace TYPO3Incubator\Data\Tests\Functional\DataHandling;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Functional test for handling data
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase {

	const VALUE_PageId = 89;
	const VALUE_PageIdTarget = 90;
	const VALUE_PageIdWebsite = 1;
	const VALUE_ContentIdFirst = 297;
	const VALUE_ContentIdLast = 298;
	const VALUE_HotelIdFirst = 3;
	const VALUE_HotelIdSecond = 4;
	const VALUE_HotelIdThird = 5;
	const VALUE_LanguageId = 1;
	const VALUE_WorkspaceId = 0;

	const TABLE_Page = 'pages';
	const TABLE_Content = 'tt_content';
	const TABLE_Hotel = 'tx_irretutorial_1nff_hotel';
	const TABLE_Offer = 'tx_irretutorial_1nff_offer';
	const TABLE_Price = 'tx_irretutorial_1nff_price';

	const FIELD_ContentHotel = 'tx_irretutorial_1nff_hotels';
	const FIELD_HotelOffer = 'offers';
	const FIELD_OfferPrice = 'prices';

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory = 'EXT:data/Tests/Functional/DataHandling/DataSet/';

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array(
		'fluid',
		'version',
		'workspaces',
	);

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $dataHandler;

	public function setUp() {
	   $this->testExtensionsToLoad = array_merge(
			array('typo3conf/ext/data'),
			$this->testExtensionsToLoad
	   );

		parent::setUp();

		$this->importScenarioDataSet('LiveDefaultPages');
		$this->importScenarioDataSet('LiveDefaultElements');
		$this->importScenarioDataSet('ReferenceIndex');

		$this->setUpFrontendRootPage(
			1,
			array(
				'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts',
				'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/ExtbaseJsonRenderer.ts',
			)
		);
		$this->backendUser->workspace = self::VALUE_WorkspaceId;
		$this->dataHandler = $this->createDataHandler();
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->dataHandler);
	}

	protected function localizeContentElement() {
		$commandMap = array();
		$commandMap[static::TABLE_Content][static::VALUE_ContentIdFirst]['localize'] = static::VALUE_LanguageId;

		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	protected function localizeContentElementsWithChildrenWithoutAutomation() {
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizeChildrenAtParentLocalization'] = false;
		$GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['localizeChildrenAtParentLocalization'] = false;
		$GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['localizeChildrenAtParentLocalization'] = false;

		static::localizeContentElementsWithChildren();
	}

	protected function localizeContentElementsWithChildrenWithAutomation() {
		$GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentHotel]['config']['behaviour']['localizeChildrenAtParentLocalization'] = true;
		$GLOBALS['TCA'][self::TABLE_Hotel]['columns'][self::FIELD_HotelOffer]['config']['behaviour']['localizeChildrenAtParentLocalization'] = true;
		$GLOBALS['TCA'][self::TABLE_Offer]['columns'][self::FIELD_OfferPrice]['config']['behaviour']['localizeChildrenAtParentLocalization'] = true;

		static::localizeContentElementsWithChildren();
	}

	protected function localizeContentElementsWithChildren() {
		$commandMap = array();

		$shuffleTableNames = array(static::TABLE_Offer, static::TABLE_Hotel, static::TABLE_Price);
		$orderedTableNames = array(static::TABLE_Content);

		foreach ($this->getShuffledItems($shuffleTableNames) as $shuffledItem) {
			$tableName = $shuffledItem['tableName'];
			$elementId = $shuffledItem['id'];
			$commandMap[$tableName][$elementId]['localize'] = static::VALUE_LanguageId;
		}
		foreach ($this->getAllElementIds($orderedTableNames) as $tableName => $elementIds) {
			foreach ($elementIds as $elementId) {
				$commandMap[$tableName][$elementId]['localize'] = static::VALUE_LanguageId;
			}
		}

		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function createDataHandler() {
		$dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\DataHandling\\DataHandler'
		);
		$this->dataHandler = $dataHandler;
		return $dataHandler;
	}

	/**
	 * @param array $tableNames
	 * @return array
	 */
	protected function getAllElementIds(array $tableNames) {
		$elementIds = array();

		foreach ($tableNames as $tableName) {
			$records = $this->getDatabaseConnection()->exec_SELECTgetRows('uid', $tableName, '', '', '', '', 'uid');
			if (!empty($records)) {
				$elementIds[$tableName] = array_keys($records);
			}
		}

		return $elementIds;
	}

	/**
	 * @param array $tableNames
	 * @return array
	 */
	protected function getShuffledItems(array $tableNames) {
		$shuffledItems = array();

		foreach ($this->getAllElementIds($tableNames) as $tableName => $elementIds) {
			foreach ($elementIds as $elementId) {
				$shuffledItems[] = array('tableName' => $tableName, 'id' => $elementId);
			}
		}

		shuffle($shuffledItems);
		return $shuffledItems;
	}

}
