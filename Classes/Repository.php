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
		$resource = $this->db->exec_SELECTquery('DISTINCT path', $this->queueTable, '', '', '', '0,'.$rowCount);

		if ($resource) {
			while($row = $this->db->sql_fetch_assoc($resource)) {
				$paths[] = $this->db->quoteStr($row['path'], $this->queueTable);
			}

			$this->db->exec_DELETEquery($this->queueTable, 'path IN("'.implode('","', $paths).'")');
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
