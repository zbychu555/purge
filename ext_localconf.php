<?php

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);

// add clear cache menu
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/ClearCacheMenu.php:&Tx_Purge_Hooks_ClearCacheMenu';
$TYPO3_CONF_VARS['BE']['AJAX']['tx_purgeclearcache::clear'] = 'EXT:purge/Classes/Cachemgr.php:Tx_Purge_Cachemgr->clearCompleteCache';

if($confArr['enablePurgeCalls']) {
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval']['purge'] = 'Tx_Purge_Hooks_Tcemain->clearCacheForListOfUids';
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['purge'] = 'Tx_Purge_Hooks_Tcemain->clearCacheCmd';
}

if (t3lib_extMgm::isLoaded('l10nmgr') && $confArr['disableL10nmgrPurgeRequests']) {
	$TYPO3_CONF_VARS['SC_OPTIONS']['l10nmgr/domain/importer/class.tx_l10nmgr_domain_importer_importer.php']['preprocessImportRun'][] = 'user_tx_purge_hooks_l10nmgr->disablePurgeRequests';
}

if (t3lib_extMgm::isLoaded('realurl')) {
	$TYPO3_CONF_VARS['EXTCONF']['purge']['finder'][] = 'Tx_Purge_Finder_Realurl';
}

if (TYPO3_MODE == 'BE') {
	// configure scheduler-tasks
	require_once t3lib_extMgm::extPath($_EXTKEY).'/Classes/Scheduler/ProcessCacheQueue.php';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Purge_Scheduler_ProcessCacheQueue'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Language/locallang_db.xml:schedulerTask.processCacheQueue.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Language/locallang_db.xml:schedulerTask.processCacheQueue.description',
	);
}