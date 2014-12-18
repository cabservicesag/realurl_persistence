<?php

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['encodeSpURL_postProc']['tx_realurlpersistence'] = 'EXT:realurl_persistence/Classes/Hooks/Realurl.php:&tx_RealurlPersistence_Hooks_Realurl->encode';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['decodeSpURL_preProc']['tx_realurlpersistence'] = 'EXT:realurl_persistence/Classes/Hooks/Realurl.php:&tx_RealurlPersistence_Hooks_Realurl->decodeSpURL_preProc';
