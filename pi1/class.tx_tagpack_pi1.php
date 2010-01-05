<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2009 JoH asenau <info@cybercraft.de>
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
	 
	require_once(PATH_tslib.'class.tslib_pibase.php');
	 
	/**
	* Plugin 'Tag Cloud' for the 'tagpack' extension.
	*
	* @author JoH asenau <info@cybercraft.de>
	* @package TYPO3
	* @subpackage tx_tagpack
	*/
	class tx_tagpack_pi1 extends tslib_pibase {
		var $prefixId = 'tx_tagpack_pi1';
		// Same as class name
		var $scriptRelPath = 'pi1/class.tx_tagpack_pi1.php'; // Path to this script relative to the extension dir.
		var $extKey = 'tagpack'; // The extension key.
		var $pi_checkCHash = true;
		 
		/**
		* The main method of the PlugIn
		*
		* @param string  $content: The PlugIn content
		* @param array  $conf: The PlugIn configuration
		* @return The  content that is displayed on the website
		*/
		function main($content, $conf) {
			$conf = $conf['userFunc.']['renderObj'] ? $conf['userFunc.'] :
			$conf;
			$elements = $conf['tagcloudElements.'];
			$record = t3lib_div::trimExplode(':', $this->cObj->currentRecord);
			$table = $record[0];
			if (($table == 'tt_content' && t3lib_div::inList($elements['enabledContent'], $this->cObj->data['CType'])) || t3lib_div::inList($conf['enabledRecords'], $table) || $this->cObj->data['CType'] == 'tagpack_pi1') {
				$tagcloud = $this->cObj->cObjGetSingle($conf['renderObj'], $conf['renderObj.']);
				 
				return $content.'
					'.$tagcloud;
			} else {
				return $content;
			}
		}
		 
		/**
		* [Describe function...]
		*
		* @param [type]  $content: ...
		* @param [type]  $conf: ...
		* @return [type]  ...
		*/
		function makeTagCloud($content, $conf) {
			$conf['maxFontSize'] = $conf['maxFontSize'] ? $conf['maxFontSize'] :
			 24;
			$conf['minFontSize'] = $conf['minFontSize'] ? $conf['minFontSize'] :
			 9;
			$conf['maxNumberOfSizes'] = $conf['maxNumberOfSizes'] ? $conf['maxNumberOfSizes'] :
			 10;
			$conf['fontColor'] = $conf['fontColor'] ? $conf['fontColor'] :
			 '#000000';
			$record = t3lib_div::trimExplode(':', $this->cObj->currentRecord);
			$getTagsFromPidList = $conf['tagPidList'] ? $conf['tagPidList'] :
			 0;
			$getTagsFromPidList = implode(',', t3lib_div::trimExplode(',', $getTagsFromPidList));
			$pid = 'tx_tagpack_tags.pid IN ('.$getTagsFromPidList.') AND ';
			if ($conf['singleItemCloud']) {
				$table = 'tx_tagpack_tags_relations_mm.tablenames=\''.$conf['tableName'].'\' AND ';
				$uid = 'tx_tagpack_tags_relations_mm.uid_foreign='.$this->cObj->data['uid'].' AND ';
			}
			if (count($conf)) {
				if ($this->piVars['filtermode'] === 'on' && !$conf['singleItemCloud'] && ($this->piVars['uid'] || $this->piVars['searchWord'])) {
					if ($this->piVars['uid']) {
						$selectedTags = t3lib_div::intExplode(',', $this->piVars['uid']);
					}
					if ($this->piVars['searchWord']) {
						$searchWordMatchTags = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid',
							'tx_tagpack_tags',
							'name LIKE '.$GLOBALS['TYPO3_DB']->fullQuoteStr('%'.$this->piVars['searchWord'].'%', 'tx_tagpack_tags').' AND NOT deleted AND NOT hidden' );
						if (!$GLOBALS['TYPO3_DB']->sql_error()) {
							while ($searchWordMatchTagUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($searchWordMatchTags)) {
								$selectedTags[] = $searchWordMatchTagUid['uid'];
							}
							$GLOBALS['TYPO3_DB']->sql_free_result($searchWordMatchTags);
						}
					}
					 
					if (count($selectedTags)) {
						foreach($selectedTags as $key => $selectedUid) {
							if ($selectedUid != t3lib_div::_GET('tx_tagpack_pi3_removeItems')) {
								$taggedItems = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'tx_tagpack_tags_relations_mm.uid_foreign,tx_tagpack_tags_relations_mm.tablenames',
									'tx_tagpack_tags_relations_mm JOIN tx_tagpack_tags ON ('.$table.$uid.'tx_tagpack_tags.uid=tx_tagpack_tags_relations_mm.uid_local AND tx_tagpack_tags_relations_mm.uid_local='.intval($selectedUid).')',
									$pid.'NOT tx_tagpack_tags.deleted AND NOT tx_tagpack_tags.hidden AND NOT tx_tagpack_tags_relations_mm.deleted AND NOT tx_tagpack_tags_relations_mm.hidden',
									'' );
								if (!$GLOBALS['TYPO3_DB']->sql_error()) {
									while ($item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($taggedItems)) {
										$itemArray[$item['tablenames']][intval($item['uid_foreign'])]++;
									}
									$GLOBALS['TYPO3_DB']->sql_free_result($taggedItems);
								}
							} else {
								unset($selectedTags[$key]);
							}
						}
					}
					$this->piVars['uid'] = implode(',', $selectedTags);
					if (count($itemArray)) {
						foreach($itemArray as $key => $valueArray) {
							$uidItems = '0';
							$filteritems .= $filteritems ? ' OR ' :
							'(';
							$filteritems .= '(tx_tagpack_tags_relations_mm.tablenames=\''.$key.'\' AND tx_tagpack_tags_relations_mm.uid_foreign IN(';
							foreach($valueArray as $uidValue => $isset) {
								if ($isset >= count($selectedTags)) {
									$uidItems .= ','.intval($uidValue);
								}
							}
							$filteritems .= $uidItems.'))';
						}
					}
					$filteritems .= $filteritems ? ') AND ' :
					 '';
				}
				$tagRelations = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_tagpack_tags.relations',
					'tx_tagpack_tags_relations_mm JOIN tx_tagpack_tags ON ('.$table.$uid.'tx_tagpack_tags.uid=tx_tagpack_tags_relations_mm.uid_local)',
					$pid.$filteritems.'NOT tx_tagpack_tags.deleted AND NOT tx_tagpack_tags.hidden AND NOT tx_tagpack_tags_relations_mm.deleted AND NOT tx_tagpack_tags_relations_mm.hidden',
					'tx_tagpack_tags.relations',
					'tx_tagpack_tags.relations DESC',
					intval($conf['maxNumberOfSizes']) );
				 
				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					while ($relations = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tagRelations)) {
						$max = $max ? $max :
						 intval($relations['relations']);
						$min = intval($relations['relations']);
						$relationRange .= $relationRange ? ','.intval($relations['relations']) :
						intval($relations['relations']);
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($tagRelations);
				}
				if (!$relationRange) {
					$relationRange = 0;
				}
				 
				$tagArray = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_tagpack_tags.uid,tx_tagpack_tags.name,tx_tagpack_tags.relations',
					'tx_tagpack_tags_relations_mm JOIN tx_tagpack_tags ON ('.$table.$uid.'tx_tagpack_tags.uid=tx_tagpack_tags_relations_mm.uid_local)',
					$pid.$filteritems.'NOT tx_tagpack_tags.deleted AND NOT tx_tagpack_tags.hidden AND tx_tagpack_tags.relations IN('.$relationRange.') AND NOT tx_tagpack_tags_relations_mm.deleted AND NOT tx_tagpack_tags_relations_mm.hidden',
					'tx_tagpack_tags.uid',
					'tx_tagpack_tags.name ASC',
					'' );
				$typolink['parameter'] = $conf['targetPid'];
				$typolink['useCacheHash'] = 1;
				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					while ($tagValues = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tagArray)) {
						$text = stripslashes($tagValues['name']);
						if ($this->piVars['filtermode'] === 'on') {
							$typolink['additionalParams'] = '&'.$this->prefixId.'[uid]='.($this->piVars['uid'] ? $this->piVars['uid'].','.$tagValues['uid'] : $tagValues['uid']);
						} else {
							$typolink['additionalParams'] = '&'.$this->prefixId.'[uid]='.$tagValues['uid'];
						}
						if (count($conf['keepGetVars.'])) {
							foreach($conf['keepGetVars.'] as $parameter => $value) {
								if (count($value)) {
									$parameter = str_replace('.', '', $parameter);
									$GPvalue = t3lib_div::_GET($parameter);
									foreach($value as $subparameter => $trigger) {
										if ($trigger) {
											$typolink['additionalParams'] .= '&'.$parameter.'['.$subparameter.']='.$GPvalue[$subparameter];
										}
									}
								} else {
									$typolink['additionalParams'] .= '&'.$parameter.'='.t3lib_div::_GET($parameter);
								}
							}
						}
						$typolink['title'] = $tagValues['relations'] > 1 ? $tagValues['relations'].' '.$conf['linkLabel.']['plural'].' '.$text :
						$tagValues['relations'].' '.$conf['linkLabel.']['singular'].' '.$text;
						$difference = $max-$min;
						$difference = $difference > 1 ? $difference :
						1;
						$percentage = (($tagValues['relations']-$min)/($difference));
						$size = intval(($conf['maxFontSize']-$conf['minFontSize']) * $percentage+$conf['minFontSize']);
						$typolink['ATagParams'] = 'style="color:'.$conf['fontColor'].'; font-size:'.$size.'px; line-height:'.ceil($conf['maxFontSize'] * 0.7).'px;"';
						$typolink['ATagParams'] .= t3lib_div::inList($this->piVars['uid'], $tagValues['uid']) ? ' class="active"' :
						'';
						$content .= '
							'.$this->cObj->stdWrap($this->cObj->typolink($text, $typolink), $conf['linkStdWrap.']).' ';
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($tagArray);
				}
				 
				$elements['linkBox'] = $content ? $this->cObj->stdWrap($content, $conf['linkBoxStdWrap.']) :
				'';
				 
				if ($conf['searchBox'] && !$conf['singleItemCloud']) {
					$elements['searchBox'] = $this->makeSearchBox($conf);
				}
				 
				if ($conf['modeSwitch'] && !$conf['singleItemCloud']) {
					$elements['modeSwitch'] = $this->makeModeSwitch($conf);
				}
				 
				if ($conf['calendar'] && !$conf['singleItemCloud']) {
					$elements['calendar'] = $this->makeCalendar($conf);
				}
				 
				if (!$conf['singleItemCloud']) {
					if ($conf['elementOrder']) {
						$elementOrder = t3lib_div::trimExplode(',', $conf['elementOrder']);
						foreach($elementOrder as $elementName) {
							$output .= $elements[$elementName];
						}
					} else {
						$output = $elements['searchBox'].$elements['linkBox'].$elements['modeSwitch'].$elements['calendar'];
					}
				} else {
					$output = $elements['linkBox'];
				}
				 
				return $output ? $this->cObj->stdWrap($output, $conf['generalStdWrap.']) :
				'';
			}
		}
		 
		/**
		* [Describe function...]
		*
		* @param [type]  $conf: ...
		* @return [type]  ...
		*/
		function makeModeSwitch($conf) {
			$firstUidArray = t3lib_div::intExplode(',', $this->piVars['uid']);
			foreach($firstUidArray as $key => $val) {
				if ($val != t3lib_div::_GET('tx_tagpack_pi3_removeItems')) {
					$firstUid[] = $val;
				}
			}
			$uidList = $uidList ? implode(',', $firstUid) :
			'';
			$typolink = array();
			$typolink['parameter'] = $GLOBALS['TSFE']->id;
			$typolink['additionalParams'] = ($uidList ? '&'.$this->prefixId.'[uid]='.$uidList : '').'&'.$this->prefixId.'[filtermode]=on';
			if (count($conf['keepGetVars.'])) {
				foreach($conf['keepGetVars.'] as $parameter => $value) {
					if (count($value)) {
						$parameter = str_replace('.', '', $parameter);
						$GPvalue = t3lib_div::_GET($parameter);
						foreach($value as $subparameter => $trigger) {
							if ($trigger) {
								$typolink['additionalParams'] .= '&'.$parameter.'['.$subparameter.']='.$GPvalue[$subparameter];
							}
						}
					} else {
						$typolink['additionalParams'] .= '&'.$parameter.'='.t3lib_div::_GET($parameter);
					}
				}
			}
			$typolink['ATagParams'] = $this->piVars['filtermode'] == 'on' ? 'class="active"' :
			'';
			$typolink['title'] = 'Filtermode on';
			$typolink['useCacheHash'] = 1;
			$modeSwitch .= $this->cObj->typolink('ON', $typolink).'&#160;&#124;&#160;';
			$typolink['additionalParams'] = '&'.$this->prefixId.'[filtermode]=off&'.$this->prefixId.'[uid]='.$firstUid[0];
			$typolink['title'] = 'Filtermode off';
			$typolink['ATagParams'] = $this->piVars['filtermode'] == 'on' ? '' :
			'class="active"';
			$modeSwitch .= $this->cObj->typolink('OFF', $typolink).'&#160;&#124;&#160;';
			$typolink['title'] = 'Reset all filters';
			$typolink['useCacheHash'] = 1;
			$typolink['additionalParams'] = '&'.$this->prefixId.'[filtermode]=&'.$this->prefixId.'[uid]=&'.$this->prefixId.'[from]=&'.$this->prefixId.'[to]=&'.$this->prefixId.'[searchWord]=';
			$typolink['ATagParams'] = '';
			$modeSwitch .= $this->cObj->typolink('RESET', $typolink);
			return $this->cObj->stdWrap($modeSwitch, $conf['modeSwitchStdWrap.']);
		}
		 
		/**
		* [Describe function...]
		*
		* @param [type]  $conf: ...
		* @return [type]  ...
		*/
		function makeSearchBox($conf) {
			$typolinkConf = array(
			'parameter' => $GLOBALS['TSFE']->id,
				'additionalParams' => '&tx_tagpack_pi1[searchWord]=',
				'returnLast' => 'url' );
			$typolink = $this->cObj->typolink('', $typolinkConf);
			$searchBox = '<form action="'.$typolink.'" class="tagpack-searchform" method="GET" target="_top">
				';
			foreach($this->piVars as $key => $value) {
				if ($key != 'searchWord') {
					$searchBox .= '<input type="hidden" name="'.$this->prefixId.'['.$key.']" id="hidden_'.$this->prefixId.'['.$key.']" value="'.$value.'""/>';
				}
			}
			if (count($conf['keepGetVars.'])) {
				foreach($conf['keepGetVars.'] as $parameter => $value) {
					if (count($value)) {
						$parameter = str_replace('.', '', $parameter);
						$GPvalue = t3lib_div::_GET($parameter);
						foreach($value as $subparameter => $trigger) {
							if ($trigger) {
								$searchBox .= '<input type="hidden" name="'.$parameter.'['.$subparameter.']" value="'.$GPvalue[$subparameter].'" />';
							}
						}
					} else {
						$searchBox .= '<input type="hidden" name="'.$parameter.'" value="'.t3lib_div::_GET($parameter).'" />';
					}
				}
			}
			$searchBox .= '<input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />';
			$searchBox .= '<label for="'.$this->prefixId.'[searchword]">'.($conf['searchWord'] ? $conf['searchWord'].'<br />' : '').'<input onBlur="submit();" onfocus="this.value=\'\';" type="text" class="inputfield" name="'.$this->prefixId.'[searchWord]" id="'.$this->prefixId.'[searchWord]" value="'.$this->piVars['searchWord'].'" size="20" /></label>';
			$searchBox .= '</form><br />';
			return $this->cObj->stdWrap($searchBox, $conf['searchBoxStdWrap.']);
		}
		 
		/**
		* [Describe function...]
		*
		* @param [type]  $conf: ...
		* @return [type]  ...
		*/
		function makeCalendar($conf) {
			if (t3lib_extMgm::isLoaded('netcos_jscalendar')) {
				$typolinkConf = array(
				'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => '&tx_tagpack_pi1[from]=&tx_tagpack_pi1[to]=',
					'returnLast' => 'url',
					);
				$typolink = $this->cObj->typolink('', $typolinkConf);
				$calendar = '<form action="'.$typolink.'" class="tagpack-calendarform" method="GET" target="_top">
					';
				foreach($this->piVars as $key => $value) {
					if ($key != 'from' && $key != 'to') {
						$calendar .= '<input type="hidden" name="'.$this->prefixId.'['.$key.']" id="hidden_'.$this->prefixId.'['.$key.']" value="'.$value.'" />';
					}
				}
				if (count($conf['keepGetVars.'])) {
					foreach($conf['keepGetVars.'] as $parameter => $value) {
						if (count($value)) {
							$parameter = str_replace('.', '', $parameter);
							$GPvalue = t3lib_div::_GET($parameter);
							foreach($value as $subparameter => $trigger) {
								if ($trigger) {
									$calendar .= '<input type="hidden" name="'.$parameter.'['.$subparameter.']" value="'.$GPvalue[$subparameter].'" />';
								}
							}
						} else {
							$calendar .= '<input type="hidden" name="'.$parameter.'" value="'.t3lib_div::_GET($parameter).'" />';
						}
					}
				}
				$calendar .= '<input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />';
				$calendar .= '<label for="'.$this->prefixId.'[from]">'.($conf['calendarFrom'] ? $conf['calendarFrom'].'<br />' : '').'<input type="text" class="inputfield" name="'.$this->prefixId.'[from]" id="'.$this->prefixId.'_from" value="'.$this->piVars['from'].'" size="10" /></label><span id="'.$this->prefixId.'_from_trigger" class="tx-tagpack-pi1-calendaricon" style="width:10px;height:10px;border-left:10px solid black;"><!--Calendar--></span> ';
				$calendar .= '<br /><label for="'.$this->prefixId.'_to">'.($conf['calendarTo'] ? $conf['calendarTo'].'<br />' : '').'<input type="text" class="inputfield" name="'.$this->prefixId.'[to]" id="'.$this->prefixId.'_to" value="'.$this->piVars['to'].'" size="10" /></label><span id="'.$this->prefixId.'_to_trigger" class="tx-tagpack-pi1-calendaricon" style="width:10px;height:10px;border-left:10px solid black;"><!--Calendar--></span>
					<script type="text/javascript">
					Calendar.setup({
					inputField     :    "'.$this->prefixId.'_from",     // id of the input field
					ifFormat       :    "'.$conf['dateFormat'].'",      // format of the input field
					button         :    "'.$this->prefixId.'_from_trigger",  // trigger for the calendar (button ID)
					align          :    "Tl",           // alignment (defaults to "Bl")
					singleClick    :    true
					});
					Calendar.setup({
					inputField     :    "'.$this->prefixId.'_to",     // id of the input field
					ifFormat       :    "'.$conf['dateFormat'].'",      // format of the input field
					button         :    "'.$this->prefixId.'_to_trigger",  // trigger for the calendar (button ID)
					align          :    "Tl",           // alignment (defaults to "Bl")
					singleClick    :    true
					});
					</script>
					';
				$calendar .= '<input type="submit" value="'.$conf['calendarSend'].'" class="calendarsubmit" /></form>';
			} else {
				$calendar = 'No calendar installed!';
			}
			return $this->cObj->stdWrap($calendar, $conf['calendarStdWrap.']);
		}
	}
	 
	 
	 
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi1/class.tx_tagpack_pi1.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi1/class.tx_tagpack_pi1.php']);
	}
	 
?>
