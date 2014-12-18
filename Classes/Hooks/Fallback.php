<?php
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Nils Blattner <nb@cabag.ch>, cab services ag
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

/**
 * Fallback hook.
 */
class tx_RealurlPersistence_Hooks_Fallback {
	/**
	 * Check if the URL was actually decoded.
	 *
	 * @param array $notFoundParameters The parameters for the hook.
	 * @param tslib_fe $tsfe TypoScriptFrontendController or tslib_fe.
	 * @return void Setting internal variables.
	 */
	public function decode($notFoundParameters, $tsfe) {
		$url = strtolower(trim(trim($notFoundParameters['currentUrl']), '/'));
		
		if ($url !== '') {
			$realurlHook = t3lib_div::makeInstance('tx_RealurlPersistence_Hooks_Realurl');
			$pid = $realurlHook->getRootPageId(t3lib_div::getIndpEnv('HTTP_HOST'));
			$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'parameters',
				'tx_realurlpersistence_url',
				'deleted = 0 AND hash = \'' . sha1($pid . ':' . $url) . '\''
			);
		}
		
		if ($url === '' || !is_array($record)) {
			$this->pageNotFound($tsfe, $notFoundParameters['reasonText']);
		}
		
		$parameters = unserialize($record['parameters']);
		
		if (!is_array($parameters) || !is_array($parameters['LD'])) {
			$this->pageNotFound($tsfe, $notFoundParameters['reasonText']);
		}
		
		$this->fakeFrontend($parameters);
		$realurl = t3lib_div::makeInstance('tx_realurl');
		
		$parameters['TCEmainHook'] = 1;
		
		$realurl->encodeSpURL($parameters);
		
		$newUrl = strtolower(trim(trim($parameters['LD']['totalURL']), '/'));
		
		if ($newUrl === $url) {
			$this->pageNotFound($tsfe, $notFoundParameters['reasonText']);
		}
		
		@ob_end_clean();
		header('HTTP/1.1 307 realurl_persistence redirect');
		header('Location: ' . t3lib_div::locationHeaderUrl($newUrl));
		exit;
	}
	
	/**
	 * Fake a frontend that allows realurl to work.
	 *
	 * @param array $parameters The parameters for the link.
	 * @return void
	 */
	protected function fakeFrontend($parameters) {
		$GLOBALS['TSFE']->clear_preview();
		$GLOBALS['TSFE']->config = array('config' => array('typolinkEnableLinksAcrossDomains' => 1));
		$id = is_array($parameters['args']) && is_array($parameters['args']['page']) ? $parameters['args']['page']['uid'] : 0;
		$GLOBALS['TSFE']->id = $id;
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->initTemplate();
	}
	
	/**
	 * Exit back to the normal page not found handler.
	 *
	 * @param tslib_fe $tsfe TypoScriptFrontendController or tslib_fe.
	 * @param string $message The page not found message.
	 * @return void
	 */
	protected function pageNotFound($tsfe, $message) {
		$realurlHook = t3lib_div::makeInstance('tx_RealurlPersistence_Hooks_Realurl');
		$realurlHook->returnOldPageErrorHandler();
		$tsfe->pageNotFoundAndExit($message);
	}
}
