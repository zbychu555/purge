<?php

class Tx_Purge_Cachemgr {

	/**
	 * @var array
	 */
	protected $clearQueue = array();

	/**
	 * @param string $path
	 * @param string $domain
	 * @return void
	 */
	public function clearCacheForUrl($path, $domain = "") {
		t3lib_div::devLog('clearCacheForUrl', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, array($path));
		if ($domain) {
			if (substr($domain, -1) == '/' || substr($path, 0, 1) == '/') {
				$fullUrl = $domain . $path;
			} else {
				$fullUrl = $domain . '/' . $path;
			}
		} else {
			$fullUrl = t3lib_div::getIndpEnv('HTTP_HOST') . '/' . $path;
		}
		$this->clearQueue[] = $fullUrl;
		$this->clearQueue = array_unique($this->clearQueue);
	}

	/**
	 * @return void
	 */
	public function execute() {
		$curl_handles = array();
		t3lib_div::devLog('execute', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, $this->clearQueue, $this->clearQueue);

		if (count($this->clearQueue) > 0) {
			$mh = curl_multi_init();
			foreach ($this->clearQueue as $path) {
				$ch = $this->getCurlHandleForCacheClearing($path);
				$curl_handles[] = $ch;
				curl_multi_add_handle($mh, $ch);
			}

			// initialize all connections
			$active = null;
			do {
				$status = curl_multi_exec($mh, $active);
				t3lib_div::devLog('status init', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, array($status));
			} while ($status == CURLM_CALL_MULTI_PERFORM);

			t3lib_div::devLog('connections initialized, status: ' . $status, 'purge', t3lib_div::SYSLOG_SEVERITY_INFO);

			// now wait for activity on any connection (this blocks script execution)
			while ($active && $status == CURLM_OK) {
				t3lib_div::devLog('waiting for activity, status: ' . $status, 'purge', t3lib_div::SYSLOG_SEVERITY_INFO);
				$selectResult = curl_multi_select($mh);

				if ($selectResult === -1) {
					// error
					t3lib_div::devLog('curl_multi_select error', 'purge', t3lib_div::SYSLOG_SEVERITY_ERROR);
				}

				if ($selectResult === 0) {
					// timeout
					t3lib_div::devLog('curl_multi_select timeout', 'purge', t3lib_div::SYSLOG_SEVERITY_WARNING);
				}

				if ($selectResult > 0) {
					// activity detected
					t3lib_div::devLog('curl_multi_select activity', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO);

					do {
						$status = curl_multi_exec($mh, $active);
						t3lib_div::devLog('status activity', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, array($status));
					} while ($status == CURLM_CALL_MULTI_PERFORM);

					$mhinfo = curl_multi_info_read($mh);
					if ($mhinfo !== false) {
						$chinfo = curl_getinfo($mhinfo['handle']);
						t3lib_div::devLog('mh info', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, $mhinfo);
						t3lib_div::devLog('ch info', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, $chinfo);
					}
				}
			}

			foreach ($curl_handles as $ch) {
				if (curl_errno($ch) !== 0) {
					t3lib_div::devLog('error', 'purge', t3lib_div::SYSLOG_SEVERITY_ERROR, curl_error($ch));
				} else {
					$info = curl_getinfo($ch);
					t3lib_div::devLog('info', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, $info);
				}
				curl_multi_remove_handle($mh, $ch);
				curl_close($ch);
			}

			curl_multi_close($mh);
			$this->clearQueue = array();
		}
	}

	/**
	 * @param string $url
	 * @return resource
	 */
	protected function getCurlHandleForCacheClearing($url) {
		t3lib_div::devLog('getCurlHandleForCacheClearing', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, array($url));
		$curlHandle = curl_init();
		curl_setopt($curlHandle, CURLOPT_URL, $url);
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'PURGE');
		curl_setopt($curlHandle, CURLOPT_HEADER, 0);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);

		return $curlHandle;
	}

	/**
	 * @param $cmd string
	 */
	public function clearCache($cmd) {
		switch ($cmd) {
			case 'pages':
				t3lib_div::devLog('clearCacheCmd() pages', 'purge');
			// break left out intentionally
			case 'all':
				t3lib_div::devLog('clearCacheCmd() all', 'purge');

				$config = $this->getConfig();

				if ($this->hasOverrideDomains($config)) {
					foreach ($this->getOverrideDomains($config) as $domain) {
						$this->clearCacheForUrl('.*', $domain);
					}
				} else {
					$this->clearCacheForUrl('.*');
				}

				// clear cache queue in database (entries are obsolete after clearing complete cache)
				if ($config['enableAsynchronousProcessing']) {
					$repository = t3lib_div::makeInstance('Tx_Purge_Repository'); /* @var $repository Tx_Purge_Repository */
					$repository->deleteAllPathsInCacheQueue();
				}

				break;
		}
	}

	/**
	 * Used for TYPO3 clear cache menu hook
	 */
	public function clearCompleteCache() {
		$this->clearCache('all');
	}

	/**
	 * @param array $config
	 * @return bool
	 */
	protected function hasOverrideDomains(array $config) {
		return !empty($config['overrideDomains']);
	}

	/**
	 * @param array $config
	 * @return array
	 */
	protected function getOverrideDomains(array $config) {
		return t3lib_div::trimExplode(',', $config['overrideDomains']);
	}

	/**
	 * @param array $paths
	 */
	public function addClearQueuePaths($paths) {
		$this->clearQueue = array_merge($this->clearQueue, $paths);
	}

	/**
	 * @return array
	 */
	protected function getConfig() {
		return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['purge']);
	}

	public function __destruct() {
		$config = $this->getConfig();
		if ($config['enableAsynchronousProcessing']) {
			$repository = t3lib_div::makeInstance('Tx_Purge_Repository'); /* @var $repository Tx_Purge_Repository */
			$repository->addPathsToCacheQueue($this->clearQueue);
		} else {
			$this->execute();
		}
	}

}