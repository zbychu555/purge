<?php

require_once(PATH_typo3 . 'interfaces/interface.backend_cacheActionsHook.php');

class Tx_Purge_Hooks_ClearCacheMenu implements backend_cacheActionsHook {

	/**
	 * Adds menu item to TYPO3 clear cache menu
	 *
	 * @param $cacheActions
	 * @param $optionValues
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:purge/Resources/Language/locallang_db.xml:clearCacheMenu_purgeClearCache');
		$imagePath = $GLOBALS['TYPO3_LOADED_EXT']['purge']['siteRelPath'];
		if(strpos($imagePath,'typo3conf') !== false) $imagePath = '../'.$imagePath;
		$cacheActions[] = array(
			'id'    => 'realurl_cache',
			'title' => $title,
			'href' => 'ajax.php?ajaxID=tx_purgeclearcache::clear',
			'icon'  => '<img src="'.$imagePath.'ext_icon.gif" title="'.$title.'" alt="'.$title.'" />',
		);
		$optionValues[] = 'clearPurgeCache';
	}

}