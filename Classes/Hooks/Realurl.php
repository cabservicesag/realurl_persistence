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
 * realurl hook.
 */
class tx_RealurlPersistence_Hooks_Realurl implements t3lib_singleton {
	/**
	 * @var string The saved page error handler.
	 */
	protected $savedPageErrorHandler = '';

	/**
	 * @var tslib_fe The saved TSFE.
	 */
	protected $tsfe;
	
	/**
	 * @var array A per call cache to save the root pages per domain (domain => id).
	 */
	protected $rootPageCache = array();

	/**
	 * Hook while encoding the path to save all new paths.
	 *
	 * @param array $parameters The parameters sent to this function.
	 * @param tx_realurl $parent The calling parent object.
	 * @return void
	 */
	public function encode($parameters, $parent) {
		list($url, $ignored) = explode('#', $parameters['URL'], 2);
		$url = strtolower(trim(trim($url), '/'));
		
		if (stristr($url, '.php?') !== FALSE || $url === '') {
			return;
		}
		
		$parts = parse_url($url);
		$pid = $GLOBALS['TSFE']->domainStartPage;
		
		if (isset($parts['host'])) {
			$pid = $this->getRootPageId($parts['host']) ?: $GLOBALS['TSFE']->domainStartPage;
			$url = ltrim($parts['path'], '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
		}
		
		$insert = array(
			'pid' => $pid,
			'hash' => sha1($pid . ':' . $url),
			'url' => $url,
			'parameters' => serialize($parameters['params']),
			'tstamp' => time(),
			'crdate' => time(),
		);
		
		$this->update($insert);
	}

	/**
	 * Hook before decoding to find out the original parameters.
	 *
	 * @param array $params The parameters sent to this function.
	 * @param tx_realurl $parent The calling parent object.
	 * @return void
	 */
	public function decodeSpURL_preProc($params, $parent) {
		$this->tsfe = $params['pObj']->pObj;
		$this->savedPageErrorHandler = $this->tsfe->TYPO3_CONF_VARS['FE']['pageNotFound_handling'];
		$this->tsfe->TYPO3_CONF_VARS['FE']['pageNotFound_handling'] = 'USER_FUNCTION:EXT:realurl_persistence/Classes/Hooks/Fallback.php:tx_RealurlPersistence_Hooks_Fallback->decode';
	}
	
	/**
	 * Return the old page not found handler.
	 *
	 * @return void
	 */
	public function returnOldPageErrorHandler() {
		$this->tsfe->TYPO3_CONF_VARS['FE']['pageNotFound_handling'] = $this->savedPageErrorHandler;
	}
	
	/**
	 * Update the given row.
	 *
	 * @array $insert The record to update.
	 * @return void
	 */
	protected function update($insert) {
		// TODO: check if mysql >= 5.0.3 is active
		$query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_realurlpersistence_url', $insert) . ' ON DUPLICATE KEY UPDATE url = VALUES(url), parameters = VALUES(parameters), tstamp = VALUES(tstamp)';
		
		$GLOBALS['TYPO3_DB']->sql_query($query);
	}
	
	/**
	 * Finds the root page for a given domain. sys_page is not used, because that might issue a redirect.
	 *
	 * @param string $domain The domain to look for.
	 * @return int The root page id.
	 */
	public function getRootPageId($domain) {
		if (!isset($this->rootPageCache[$domain])) {
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'pid',
				'sys_domain',
				'hidden = 0 AND domainName = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($domain, 'sys_domain')
			);
			if (is_array($row)) {
				$this->rootPageCache[$domain] = intval($row['pid']);
			}
		}
		
		return $this->rootPageCache[$domain] ?: 0;
	}
}
