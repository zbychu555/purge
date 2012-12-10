<?php


interface Tx_Purge_Finder {
	/**
	 * Finds url for given page id
	 *
	 * @param int $pageUid
	 * @return array with keys 'url', 'domain'
	 */
	public function getURLFromPageID($uid);
}
