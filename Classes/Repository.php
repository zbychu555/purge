<?php

class Tx_Purge_Repository implements t3lib_Singleton {

	/**
	 * @var string
	 */
	private $queueTable = 'tx_purge_cachequeue';

	/**
	 * @var t3lib_db
	 */
	private $db;

	/**
	 * Initializes repository instance.
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Gives all page paths stored in queue database table and removes them afterwards.
	 *
	 * @param  int $rowCount    Maximum number of URLs to fetch.
	 * @return array            URLs to be purged.
	 */
	public function getAndRemovePathsInCacheQueue($rowCount = 1000) {
		$paths = array();
		$queueEntries = $this->db->exec_SELECTgetRows('uid, path', $this->queueTable, '', '', '', '0,'.$rowCount);

		if (!empty($queueEntries)) {
			$deleteEntryIDs = array();

			foreach($queueEntries as $queueEntry) {
				$deleteEntryIDs[] = $queueEntry['uid'];
				$paths[] = $queueEntry['path'];
			}

			$paths = array_unique($paths);
			$this->db->exec_DELETEquery($this->queueTable, 'uid IN('.implode(',', $deleteEntryIDs).')');
		}

		return $paths;
	}

	/**
	 * Clears cache queue table
	 */
	public function deleteAllPathsInCacheQueue() {
		$this->db->exec_DELETEquery($this->queueTable, '');
	}

	/**
	 * Adds given page paths to persistent cache queue.
	 * @param array<string>
	 */
	public function addPathsToCacheQueue($paths) {
		$fields = array('path');

		$rows = array();
		foreach ($paths as $path) {
			$rows[] = array($path);
		}

		if (!empty($rows)) {
			$this->db->exec_INSERTmultipleRows($this->queueTable, $fields, $rows);
		}
	}
}
