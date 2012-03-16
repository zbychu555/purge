<?php

class Tx_Purge_Hooks_Tcemain {

	/**
	 * @param array $params
	 * @param t3lib_tcemain $parent
	 * @return void
	 */
	public function clearCacheCmd($params, &$parent) {
		if ($parent->admin || $parent->BE_USER->getTSConfigVal('options.clearCache.pages')) {
			switch ($params['cacheCmd']) {
				case 'pages':
					t3lib_div::devLog('clearCacheCmd() pages', 'purge');
					// break left out intentionally
				case 'all':
					t3lib_div::devLog('clearCacheCmd() all', 'purge');
					$cacheMgm = t3lib_div::makeInstance('Tx_Purge_Cachemgr'); /* @var $cacheMgm Tx_Purge_Cachemgr */
					$config = $this->getConfig();

					if ($this->hasOverrideDomains($config)) {
						foreach ($this->getOverrideDomains($config) as $domain) {
							$cacheMgm->clearCacheForUrl('.*', $domain);
						}
					} else {
						$cacheMgm->clearCacheForUrl('.*');
					}

					break;
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getConfig() {
		return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);
	}

	/**
	 * @param array $config
	 * @return bool
	 */
	protected function hasOverrideDomains(array $config) {
		return !empty($config['overrideDomains']);
	}

	/**
	 * @param array $config
	 * @return array
	 */
	protected function getOverrideDomains(array $config) {
		return t3lib_div::trimExplode(',', $config['overrideDomains']);
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