<?php

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);

if($confArr['enablePurgeCalls']) {
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval']['purge'] = 'Tx_Purge_Hooks_Tcemain->clearCacheForListOfUids';
	$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['purge'] = 'Tx_Purge_Hooks_Tcemain->clearCacheCmd';
}

if (t3lib_extMgm::isLoaded('realurl')) {
	$TYPO3_CONF_VARS['EXTCONF']['purge']['finder'][] = 'Tx_Purge_Finder_Realurl';
}