<?php

class Tx_Purge_Repository {

	/**
	 * Gives all page paths stored in queue database table and removes them afterwards
	 * @return array<string>
	 */
	public function getAndRemovePathsInCacheQueue($rowCount = 1000) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_purge_cachequeue', '', '', '', '0,'.$rowCount);
		$paths = array();
		foreach($rows as $row) {
			$paths[] = $row['path'];
		}
		if (!empty($paths)) {
			$paths = array_unique($paths);
			$in_paths = '';
			foreach($paths as $path) {
				$in_paths .= sprintf(
					"'%s', ",
					$GLOBALS['TYPO3_DB']->quoteStr($path, 'tx_purge_cachequeue')
				);
			}
			$in_paths = rtrim($in_paths, ',');
			// also remove duplicates
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'tx_purge_cachequeue',
				'path IN('.$in_paths.')'
			);
		}

		return $paths;
	}

	/**
	 * Clears cache queue table
	 */
	public function deleteAllPathsInCacheQueue() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_purge_cachequeue', '');
	}

	/**
	 * Adds given page paths to persistent cache queue
	 * @param array<string>
	 */
	public function addPathsToCacheQueue($paths) {
		$fields = array('path');

		$rows = array();
		foreach ($paths as $path) {
			$rows[] = array($path);
		}

		if (!empty($rows)) {
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_purge_cachequeue', $fields, $rows);
		}
	}

}