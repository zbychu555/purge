<?php

/**
 * Scheduler task to process cache queue
 */
class Tx_Purge_Scheduler_ProcessCacheQueue extends tx_scheduler_Task {

	/**
	 * Processes cache queue entries
	 */
	public function execute() {
		$repository = t3lib_div::makeInstance('Tx_Purge_Repository'); /* @var $repository Tx_Purge_Repository */
		$paths = $repository->getAndRemovePathsInCacheQueue();

		$cacheMgm = t3lib_div::makeInstance('Tx_Purge_Cachemgr'); /* @var $cacheMgm Tx_Purge_Cachemgr */
		$cacheMgm->addClearQueuePaths($paths);
		$cacheMgm->execute();

		return true;
	}

}