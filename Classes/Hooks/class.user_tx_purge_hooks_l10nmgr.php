<?php

	/**
	 * Hooks
	 *
	 * @package TYPO3
	 * @subpackage purge
	 */
class user_tx_purge_hooks_l10nmgr {

		/**
		 * Disable purge requests during l10nmgr import runs
		 *
		 * @param array $hookParameters
		 * @param tx_l10nmgr_domain_importer_importer $importer
		 */
	public function disablePurgeRequests(array $hookParameters, tx_l10nmgr_domain_importer_importer &$importer) {

		$disablePurgeHooks = array(
			'clearCachePostProc',
			'clearPageCacheEval'
		);

		foreach ($disablePurgeHooks as $hook) {
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php'][$hook]['purge'])) {
				unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php'][$hook]['purge']);
			}
		}
	}
}