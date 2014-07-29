<?php

$extensionClassesPath = t3lib_extMgm::extPath('purge') . 'Classes/';

return array(
	'tx_purge_cachemgr' => $extensionClassesPath . 'Cachemgr.php',
	'tx_purge_finder' => $extensionClassesPath . 'Finder.php',
	'tx_purge_repository' => $extensionClassesPath . 'Repository.php',
	'tx_purge_locator' => $extensionClassesPath . 'Locator.php',
	'tx_purge_finder_abstract' => $extensionClassesPath . 'Finder/Abstract.php',
	'tx_purge_finder_realurl' => $extensionClassesPath . 'Finder/Realurl.php',
	'tx_purge_finder_pageid' => $extensionClassesPath . 'Finder/PageId.php',
	'tx_purge_hooks_tcemain' => $extensionClassesPath . 'Hooks/Tcemain.php',
	'user_tx_purge_hooks_l10nmgr' => $extensionClassesPath . 'Hooks/class.user_tx_purge_hooks_l10nmgr.php',
);
