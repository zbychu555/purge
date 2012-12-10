<?php

abstract class Tx_Purge_Finder_Abstract implements Tx_Purge_Finder, t3lib_Singleton {

	/**
	 * Extension configuration
	 * @var array
	 */
	protected $conf;

	/**
	 * Loads extension configuration
	 */
	public function __construct() {
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);
	}

	/**
	 * Finds url for given page id
	 *
	 * @param int $pageUid
	 * @return array with keys 'url', 'domain'
	 */
	abstract public function getURLFromPageID($pageUid);

	/**
	 * Expand uid to list of uids based on extension configuration
	 *
	 * @param $uid
	 * @return array
	 */
	protected function expandUid($uid) {
		if(!isset($this->conf['expainsPids']) || empty($this->conf['expainsPids'])) {
			return array(intval($uid));
		}

		$expandList = t3lib_div::trimExplode(',', $this->conf['expainsPids']);
		$uidList = array();
		$uidList[] = intval($uid);
		foreach($expandList as $expand) {
			$matches=array();
			if (preg_match('/^(' . $uid . '|\*)>(\d+)$/',$expand, $matches)) {
				$uidList[] = intval($matches[1]);
			}
		}
		return $uidList;
	}
}
