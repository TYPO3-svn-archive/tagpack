<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2007 Johannes Künsebeck <kuensebeck@googlemail.com>
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
	 
	/**
	* the wizard class for the "tagpack" extension.
	* based on the "ajaxgroupsearch" extension.
	*
	*
	* @author Johannes Künsebeck <kuensebeck@googlemail.com>
	* @modifications for the tagpack JoH asenau <info@cybercraft.de>
	* @package TYPO3
	* @subpackage tagpack
	*/
	class tx_tagpack_ajaxsearch_client {
		var $xajax;
		var $extKey = 'tagpack';
		 
		/**
		* renders the input field and result list for the ajax search
		*
		* @param  array $PA  parameter array from tceForm
		* @param t3lib_TCEforms $fobj tceForm Object
		* @return html of the wizard
		**/
		function renderAjaxSearch($PA, $fobj) {
			
			if($PA['table'] == 'pages') {
			    $TSCpid = $PA['uid'];
			} else {
			    $TSCpid = $PA['pid'];
			}		
		
			$TSconfig = t3lib_befunc::getModTSConfig($TSCpid,'tx_tagpack_tags');
			$getTagsFromPid = $TSconfig['properties']['getTagsFromPid'] ? $TSconfig['properties']['getTagsFromPid'] : 0;
		
			$this->init();
			 
			$params = $PA['params'];
			 
			$row = $PA['row'];
			 
			$name = $PA['itemName'];
			 
			if (!$params['client']['startLength']) $params['client']['startLength'] = 3;
			$jsParams = $this->getJSON($params['client']);
			 
			 
			return '<div class="typoSuggest" style="'.$params['wrapStyle'].'">
				<input
				id="'.$name.'_ajaxsearch" style="'.$params['inputStyle'].'" type="text"
				onfocus=\'window.tx_tagpack_ajaxsearch_lazyCreator.get(this,'.$jsParams.').onfocus();\'
				size="20" autocomplete="off" class="search" value="" title="Tags'.$getTagsFromPid.'" />
				<ul class="results" style="'.$params['itemListStyle'].'" id="'.$name.'_ajaxsearch_results"></ul>
				</div>';
		}
		 
		/**
		* add javascript / css includes to the page, if not already done
		* @param void
		* @return void
		**/
		function init() {
			$this->t3Version = t3lib_div::int_from_ver(TYPO3_version);
			// in < 4.1 there is no prototype loaded
			if ($this->t3Version < 4001000) {
				// we can use doc here because there is no irre yet
				$this->doc->JScodeArray['prototypeJS'] = '<script type="text/javascript" src="'.$this->backPath.'contrib/prototype/prototype.js"></script>';
			}
			if (!$GLOBALS['SOBE']->tceforms->additionalCode_pre['tx_tagpack_ajaxsearch']) {
				// normally we should use $this->doc->JScodeArray but for irre children there is no $doc
				$GLOBALS['SOBE']->tceforms->additionalCode_pre['tx_tagpack_ajaxsearch'] = '<script type="text/javascript">window.tx_tagpack_ajaxsearch_server = "'.t3lib_extMgm::extRelPath($this->extKey).'class.tx_tagpack_ajaxsearch_server.php";</script>'. '<script type="text/javascript" src="'.t3lib_extMgm::extRelPath($this->extKey).'res/ajaxgroupsearch.js"></script>'. '<link rel="stylesheet" type="text/css" href="'.t3lib_extMgm::extRelPath($this->extKey).'res/ajaxgroupsearch.css" />';
			}
		}
		 
		/**
		* encodes an array to JSON (Javascript Object Notation) format, for passing it to javascript
		*
		* @param array $jsonArray array to encode to json
		* @return string JSON-encoded string
		*/
		function getJSON($jsonArray) {
			if ($this->t3Version >= 4002000) {
				return t3lib_div::array2json($jsonArray);
			} else {
				if (!$GLOBALS['JSON']) {
					require_once(PATH_typo3.'contrib/json.php');
					$GLOBALS['JSON'] = t3lib_div::makeInstance('Services_JSON');
				}
				return $GLOBALS['JSON']->encode($jsonArray);
			}
		}
	}
	 
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/class.tx_tagpack_ajaxsearch_client.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/class.tx_tagpack_ajaxsearch_client.php']);
	}
	 
?>
