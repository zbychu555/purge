<?php

class Tx_Purge_Hooks_Tcemain {

	/**
	 * @param array $params
	 * @param t3lib_tcemain $parent
	 * @return void
	 */
	public function clearCacheCmd($params, &$parent) {
		if ($parent->admin || $parent->BE_USER->getTSConfigVal('options.clearCache.pages')) {
			$cacheMgm = t3lib_div::makeInstance('Tx_Purge_Cachemgr'); /* @var $cacheMgm Tx_Purge_Cachemgr */
			$cacheMgm->clearCache($params['cacheCmd']);
		}
	}

	/**
	 * Called when TYPO3 clears a list of uid's
	 *
	 * @param array $params
	 * @param t3lib_tcemain $parent
	 * @return void
	 */
	public function clearCacheForListOfUids($params, &$parent) {
		$locator = t3lib_div::makeInstance('Tx_Purge_Locator');
		/* @var $cacheMgm Tx_Purge_Cachemgr */
		$cacheMgm = t3lib_div::makeInstance('Tx_Purge_Cachemgr');
		t3lib_div::devLog('clearCacheForListOfUids()', 'purge', 0, $params);
		foreach ($params['pageIdArray'] as $uid) {
			t3lib_div::devLog('clearCacheForListOfUids() uid: ' . $uid, 'purge');
			foreach ($locator->getUrlFromPageID($uid) as $url) {
				$cacheMgm->clearCacheForUrl($url['path'], $url['domain']);
			}
		}
	}
}