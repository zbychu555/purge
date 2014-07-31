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

class Tx_Purge_Finder_PageId extends Tx_Purge_Finder_Abstract {

	/**
	 * @param  int $pageUid
	 * @return array
	 */
	public function getURLFromPageID($pageUid) {
		$urls = array();
		$uidList = $this->expandUid($pageUid);

		foreach($uidList as $uid) {
			$url = array();
			foreach($this->getPageDomains($uid) as $domain) {
				// url is used as regular expression in varnish, so we have to escape '?'
				$url['path'] = '.*\\\?.*id=' . $uid;
				$url['domain'] = $domain;
				$urls[] = $url;
			}
		}

		return $urls;
	}

	private function getPageDomains($pid, $enableOverrideDomains = true) {
		$domains = array();

		if ($this->conf['overrideDomains'] && $enableOverrideDomains) {
			$domains = t3lib_div::trimExplode(',', $this->conf['overrideDomains']);
		} else {
			$domains[] =  t3lib_BEfunc::getViewDomain($pid);
		}

		return $domains;
	}
}
