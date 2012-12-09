<?php

class Tx_Purge_Finder_PageId implements Tx_Purge_Finder, t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $conf;

	/**
	 * @throws Exception
	 */
	public function __construct() {
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);
	}

	/**
	 * @param int $pageUid
	 * @return array
	 */
	public function getURLFromPageID($pageUid) {
		$urls = array();
		$uidList = $this->expandUid($pageUid);
		foreach($uidList as $uid) {
			$url = array();
			$url['path'] = '.*\\\?.*id=' . $uid;
			$urls[] = $url;
		}
		return $urls;
	}

	/**
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
