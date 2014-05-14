<?php
namespace Causal\EmDeveloper\Slots;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Slot implementation to extend the list of actions in Extension Manager.
 *
 * @category    Slots
 * @package     TYPO3
 * @subpackage  tx_emdevelopers
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ExtensionManager {

	/**
	 * Extends the list of actions.
	 *
	 * @param array $extensionInfo
	 * @param array $actions
	 */
	public function processActions(array $extensionInfo, array &$actions) {
		if (!empty($extensionInfo['_md5_values_when_last_written'])) {
			$currentMd5Array = $this->serverExtensionMD5array($extensionInfo['key'], $extensionInfo);
			if ($extensionInfo['_md5_values_when_last_written'] !== serialize($currentMd5Array)) {
				$actions[] = 'MODIFIED!';
			}
		}
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 *
	 * @param string $extensionKey Extension key
	 * @param array $conf Extension information array
	 * @return array MD5-keys
	 */
	protected function serverExtensionMD5array($extensionKey, $conf) {
		// Creates upload-array - including file list
		$mUA = $this->makeUploadArray($extensionKey, $conf);

		$md5Array = array();
		if (is_array($mUA['FILES'])) {
			// Traverse files.
			foreach ($mUA['FILES'] as $fN => $d) {
				if ($fN !== 'ext_emconf.php') {
					$md5Array[$fN] = substr($d['content_md5'], 0, 4);
				}
			}
		} else {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug(array($mUA, $conf), 'serverExtensionMD5Array:' . $extensionKey);
		}
		return $md5Array;
	}

	/**
	 * Make upload array out of extension
	 *
	 * @param string $extensionKey Extension key
	 * @param array $conf Extension information array
	 * @return array|NULL Returns array with extension upload array on success, otherwise NULL.
	 */
	function makeUploadArray($extensionKey, $conf) {
		$extPath = $this->getExtPath($extensionKey, $conf['type']{0});

		if ($extPath) {

			// Get files for extension:
			$fileArr = array();
			$fileArr = GeneralUtility::getAllFilesAndFoldersInPath($fileArr, $extPath, '', 0, 99, $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging']);

			// Initialize output array:
			$uploadArray = array();
			$uploadArray['extKey'] = $extensionKey;
			//$uploadArray['EM_CONF'] = $conf['EM_CONF'];
			$uploadArray['misc']['codelines'] = 0;
			$uploadArray['misc']['codebytes'] = 0;

			// Read all files:
			foreach ($fileArr as $file) {
				$relFileName = substr($file, strlen($extPath));
				$fI = pathinfo($relFileName);
				if ($relFileName !== 'ext_emconf.php') { // This file should be dynamically written...
					$uploadArray['FILES'][$relFileName] = array(
						'name' => $relFileName,
						'size' => filesize($file),
						'mtime' => filemtime($file),
						'is_executable' => (TYPO3_OS == 'WIN' ? 0 : is_executable($file)),
						'content' => GeneralUtility::getUrl($file)
					);
					if (GeneralUtility::inList('php,inc', strtolower($fI['extension']))) {
						$uploadArray['FILES'][$relFileName]['codelines'] = count(explode(LF, $uploadArray['FILES'][$relFileName]['content']));
						$uploadArray['misc']['codelines'] += $uploadArray['FILES'][$relFileName]['codelines'];
						$uploadArray['misc']['codebytes'] += $uploadArray['FILES'][$relFileName]['size'];

						/*
						// locallang*.php files:
						if (substr($fI['basename'], 0, 9) === 'locallang' && strstr($uploadArray['FILES'][$relFileName]['content'], '$LOCAL_LANG')) {
							$uploadArray['FILES'][$relFileName]['LOCAL_LANG'] = tx_em_Tools::getSerializedLocalLang($file, $uploadArray['FILES'][$relFileName]['content']);
						}
						*/
					}
					$uploadArray['FILES'][$relFileName]['content_md5'] = md5($uploadArray['FILES'][$relFileName]['content']);
				}
			}

			// Return upload-array:
			return $uploadArray;
		}

		return NULL;
	}

	/**
	 * Returns the absolute path where the extension $extKey is installed (based on 'type' (SGL))
	 *
	 * @param string $extensionKey Extension key
	 * @param string $type Install scope type: L, G, S
	 * @return string Returns the absolute path to the install scope given by input $type variable. It is checked if the path is a directory. Slash is appended.
	 */
	protected function getExtPath($extKey, $type, $returnWithoutExtKey = FALSE) {
		if ($type === 'S') {
			$typePath = PATH_typo3 . 'sysext/';
		} elseif ($type === 'G') {
			$typePath = PATH_typo3 . 'ext/';
		} elseif ($type === 'L') {
			$typePath = PATH_typo3conf . 'ext/';
		} else {
			return '';
		}

		$path = $typePath . ($returnWithoutExtKey ? '' : $extKey . '/');
		return $path;
	}

}
