<?php

class Tx_Purge_Finder_Realurl implements Tx_Purge_Finder, t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $conf;

	/**
	 * @var array
	 */
	protected $cacheLookupTables=array();

	/**
	 * @throws Exception
	 */
	public function __construct() {
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);

			// default RealUrl
		$this->cacheLookupTables = array('tx_realurl_pathcache:page_id:rootpage_id:pagepath');
			// advanced RealUrl
		if (in_array('tx_realurl_cachehistory', array_keys($GLOBALS['TYPO3_DB']->admin_get_tables()))) {
			$this->cacheLookupTables = array(
				'tx_realurl_cache:pageid:rootpid:path',
				'tx_realurl_cachehistory:pageid:rootpid:path'
			);
		} elseif (t3lib_extMgm::isLoaded('aoe_realurlpath')) {
			throw new Exception('This extension is not compatible with aoe_realurlpath');
		}
	}

	/**
	 * @param int $uid
	 * @return array
	 */
	public function getURLFromPageID($uid) {
		$urls = array();

		$uidList = $this->expandUid($uid);
		foreach($this->cacheLookupTables as $tableCfg) {

			list($table,$pageId,$rootPid,$path) = explode(':', $tableCfg);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $pageId . ' IN ('. implode(',', $uidList) . ')');
			if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				foreach($this->getDomainsFromRootpageId($row[$rootPid]) as $domain) {
					$url = array();
					$url['path'] = $row[$path].'/';
					if($domain != '_DEFAULT') {
						$url['domain'] = $domain;
					}
					$urls[] = $url;
				}
			}
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

	/**
	 * Given a certain page id, looks through RealURL conf to find all domains with this page as root id.
	 * The method takes the override_domains Extconf option into account!
	 *
	 * @param int $uid
	 * @return array
	 */
	protected function getDomainsFromRootpageId($uid) {
		$domains = array();
		if($this->conf['overrideDomains']) {
			return t3lib_div::trimExplode(',',$this->conf['overrideDomains']);
		}

		foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'] as $domain=>$conf) {
			if($conf['pagePath']['rootpage_id'] == $uid) {
				$domains[] = $domain;
			}
		}

		return $domains;
	}
}
