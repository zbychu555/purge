<?php

class Tx_Purge_Repository {

	/**
	 * Gives all page paths stored in queue database table and removes them afterwards
	 * @return array<string>
	 */
	public function getAndRemovePathsInCacheQueue($rowCount = 1000) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_purge_cachequeue', '', '', '', '0,'.$rowCount);
		$uids = array();
		$paths = array();
		foreach($rows as $row) {
			$uids[] = $row['uid'];
			$paths[] = $row['path'];
		}
		if (!empty($uids)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_purge_cachequeue', 'uid IN('.implode(',',$uids).')');
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
			// check if path is already in queue
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_purge_cachequeue', 'path = "'.$path.'"');
			if ($count === 0) {
				$row = array($path);
				$rows[] = $row;
			}
		}
		if (!empty($rows)) {
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_purge_cachequeue', $fields, $rows);
		}
	}

}