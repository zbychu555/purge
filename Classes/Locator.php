<?php

class Tx_Purge_Locator {

	/**
	 * @var array
	 */
	protected $finders = array();

	/**
	 * Find the configured finder services from the configuration
	 */
	public function __construct() {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['purge']['finder'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['purge']['finder'] as $finderClass) {
				$this->finders[] = t3lib_div::makeInstance($finderClass);
			}
		}
	}


	/**
	 * @param Tx_Purge_Finder $finder
	 * @return void
	 */
	public function addFinder(Tx_Purge_Finder $finder) {
		$this->finders[] = $finder;
	}

	/**
	 * Return URL from pageID.
	 * Will look through all registered services, and use the first one that finds the URL.
	 * It returns an array where each entry is an associative array with domain and pagepath.
	 * If the service that locates the URL is unable to determine from which domain this URL is found from, the
	 * domain key is not set.
	 * @param int $uid
	 * @return array An array of all found URL for this page id.
	 */
	public function getUrlFromPageID($uid) {
		foreach($this->finders as $finder) {
			/**
			 * @var Tx_Purge_Finder $finder
			 */
			if ($urls = $finder->getURLFromPageID($uid)) {
				t3lib_div::devLog('found urls to purge', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, $urls);
				return $urls;
			}
		}
		
		t3lib_div::devLog('Unable to determine pageURL for page with uid ' . $uid, 'purge', t3lib_div::SYSLOG_SEVERITY_WARNING);
		return array();
	}

	/**
	 * Normalizes a path, makes sure that the path always contains a trailing slash.
	 * 
	 * @param string $path
	 * @return string
	 */
	protected function normalizePath($path) {
		if(substr($path, -1,1) != "/") {
			$path .= "/";
		}
		return $path;
	}
}
