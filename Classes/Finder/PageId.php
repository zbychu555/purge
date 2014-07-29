<?php

class Tx_Purge_Finder_PageId extends Tx_Purge_Finder_Abstract {

	/**
	 * @param int $pageUid
	 * @return array
	 */
	public function getURLFromPageID($pageUid) {
		$urls = array();
		$uidList = $this->expandUid($pageUid);
		foreach($uidList as $uid) {
			$url = array();
			// url is used as regular expression in varnish, so we have to escape '?'
			$url['path'] = '.*\\\?.*id=' . $uid;
			$url['domain'] = t3lib_BEfunc::getViewDomain($pageUid);
			$urls[] = $url;
		}
		return $urls;
	}
}
