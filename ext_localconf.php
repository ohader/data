<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['data']
    = \TYPO3Incubator\Data\Hooks\DataHandlerHook::className();
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['data']
    = \TYPO3Incubator\Data\Hooks\DataHandlerHook::className();
