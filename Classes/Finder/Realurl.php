<?php

class Tx_Purge_Finder_Realurl extends Tx_Purge_Finder_Abstract {

	/**
	 * @var array
	 */
	protected $cacheLookupTables=array();

	/**
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();

		// default RealUrl
		$this->cacheLookupTables = array('tx_realurl_pathcache:page_id:rootpage_id:pagepath');

		if (!$this->conf['forceDefaultCacheLookupTables']) {
			// advanced RealUrl
			if (in_array('tx_realurl_cachehistory', array_keys($GLOBALS['TYPO3_DB']->admin_get_tables()))) {
				$this->cacheLookupTables = array(
					'tx_realurl_cache:pageid:rootpid:path',
					'tx_realurl_cachehistory:pageid:rootpid:path'
				);
			}
		}

		if (t3lib_extMgm::isLoaded('aoe_realurlpath')) {
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
		foreach ($this->cacheLookupTables as $tableCfg) {
			list($table,$pageId,$rootPid,$pathKey) = explode(':', $tableCfg);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $pageId . ' IN ('. implode(',', $uidList) . ')');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				foreach ($this->getDomainsFromRootpageId($row[$rootPid]) as $domain) {
					$realDomains = $this->getDomainsFromRootpageId($row[$rootPid], FALSE);
					$pagePath = $this->prefixLanguage($row['languageid'], $realDomains, $row[$pathKey] . '/');
					$pagePath = $this->suffixHtml($realDomains, $pagePath);
					$urls[] = array(
						'domain'	=> $domain,
						'path' 		=> $pagePath,
					);
				}
			}
		}
		return $urls;
	}

	/**
	 * @param string $language
	 * @param array $domains
	 * @param string $path
	 * @return string
	 */
	protected function prefixLanguage($language, array $domains, $path) {
		$valueMap = $this->getRealUrlValueMap(current($domains));
		$id2Language = array_flip($valueMap);
		if (isset($id2Language[$language])) {
			$path = ltrim($id2Language[$language] . '/' . $path, '/');
		}
		return $path;
	}

	/**
	 * @param string $domain
	 * @return array
	 */
	protected function getRealUrlValueMap($domain) {
		$valueMap = array();
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['preVars'] as $index => $configuration) {
			if (array_key_exists('GETvar', $configuration) && $configuration['GETvar'] === 'L' && $configuration['valueMap']) {
				$valueMap = $configuration['valueMap'];
			}
		}
		return $valueMap;
	}

	protected function suffixHtml(array $domains, $path) {
		if ($this->hasRealUrlPathHtmlSuffix(current($domains))) {
			$path = rtrim($path, '/').'.html';
		}
		return $path;
	}

	protected function hasRealUrlPathHtmlSuffix($domain) {
		$hasHtmlSuffix = false;

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain])) {
			if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['fileName']['defaultToHTMLsuffixOnPrev'])) {
				$hasHtmlSuffix = (bool) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['fileName']['defaultToHTMLsuffixOnPrev'];
			}
		} elseif (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['fileName']['defaultToHTMLsuffixOnPrev'])) {
			$hasHtmlSuffix = (bool) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['fileName']['defaultToHTMLsuffixOnPrev'];
		}

		return $hasHtmlSuffix;
	}

	/**
	 * Given a certain page id, looks through RealURL conf to find all domains with this page as root id.
	 * The method takes the override_domains Extconf option into account!
	 *
	 * @param int $uid
	 * @param bool $enableOverrideDomains
	 * @return array
	 */
	protected function getDomainsFromRootpageId($uid, $enableOverrideDomains=TRUE) {
		$domains = array();
		if($this->conf['overrideDomains'] && $enableOverrideDomains) {
			return t3lib_div::trimExplode(',', $this->conf['overrideDomains']);
		}

		foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'] as $domain=>$conf) {
			if($conf['pagePath']['rootpage_id'] == $uid) {
				$domains[] = $domain;
			}
		}

		return $domains;
	}
}
