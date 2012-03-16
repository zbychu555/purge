<?php

class Tx_Purge_Cachemgr {

    /**
     * @var array
     */
	protected $clearQueue = array();

	/**
	 * @param string $url
	 * @param string $domain
	 * @param string $scheme
	 * @return void
	 */
	public function clearCacheForUrl($url, $domain = "", $scheme = "http://") {
        t3lib_div::devLog('clearCacheForUrl', 'purge', t3lib_div::SYSLOG_SEVERITY_INFO, array($url));
		if ($domain) {
			if (substr($domain, -1) == '/' || substr($url, 0, 1) == '/') {
				$path = $scheme . $domain . $url;
			} else {
				$path = $scheme . $domain . '/' . $url;
			}
		} else {
			$path = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' . $url;
		}
		$this->clearQueue[] = $path;
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
				curl_close($ch);
				curl_multi_remove_handle($mh, $ch);
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
	 *
	 */
	public function __destruct() {
		$this->execute();
	}
}