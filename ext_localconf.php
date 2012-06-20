<?php

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);

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