<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 AOE GmbH <dev@aoe.com>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_Purge_Finder_Realurl extends Tx_Purge_Finder_Abstract {

	/**
	 * @var array
	 */
	protected $cacheLookupTables = array();

	/**
	 * @var t3lib_db
	 */
	private $db;

	/**
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();

		$this->db = $GLOBALS['TYPO3_DB'];

		// default RealUrl
		$this->cacheLookupTables = array('tx_realurl_pathcache:page_id:rootpage_id:pagepath');

		if (!$this->conf['forceDefaultCacheLookupTables']) {
			// advanced RealUrl
			if (in_array('tx_realurl_cachehistory', array_keys($this->db->admin_get_tables()))) {
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
	 * @param  int $uid
	 * @return array
	 */
	public function getURLFromPageID($uid) {
		$urls = array();
		$uidList = $this->expandUid($uid);

		foreach ($this->cacheLookupTables as $tableCfg) {
			list($table,$pageId,$rootPid,$pathKey) = explode(':', $tableCfg);
			$res = $this->db->exec_SELECTquery('*', $table, $pageId . ' IN ('. implode(',', $uidList) . ')');
			while ($row = $this->db->sql_fetch_assoc($res)) {
				foreach ($this->getDomainsFromRootpageId($row[$rootPid]) as $domain) {
					$realDomains = $this->getDomainsFromRootpageId($row[$rootPid], FALSE);
					$pagePath = $this->prefixLanguage($row['languageid'], $realDomains, $row[$pathKey]) . '.*';
					$urls[] = array('domain' => $domain, 'path' => $pagePath);
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
