<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 JoH asenau <info@cybercraft.de>
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
 * Plugin 'TagItemsOutput' for the 'tagpack' extension.
 *
 * @author JoH asenau <info@cybercraft.de>
 * @author Thomas Allmer <at@delusionworld.com>
 * @package TYPO3
 * @subpackage tx_tagpack
 */
class tx_tagpack_pi3 extends tslib_pibase {
	var $prefixId = 'tx_tagpack_pi3';
	// Same as class name
	var $scriptRelPath = 'pi3/class.tx_tagpack_pi3.php'; // Path to this script relative to the extension dir.
	var $extKey = 'tagpack'; // The extension key.

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string $content: The PlugIn content
	 * @param	array $conf: The PlugIn configuration
	 * @return string: The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->pi_loadLL();
		$this->pi1Vars = t3lib_div::_GP('tx_tagpack_pi1');
		
		$conf['selectedTags'] = $this->pi1Vars['selectedTags'] ? t3lib_div::intExplode(',', $this->pi1Vars['selectedTags']) : t3lib_div::intExplode(',', $conf['selectedTags']);
		if( $conf['selectedTags'][0] == 0 )
			$conf['selectedTags'] = array();
		else {
			$selectedTags = array();
			foreach( $conf['selectedTags'] as $id => $tag )
				$selectedTags[$tag] = $tag;
			$conf['selectedTags'] = $selectedTags;
		}
		
		$conf['enabledRecords'] = t3lib_div::trimexplode(',', $conf['enabledRecords']);
		
		if( $this->pi1Vars['searchWord'] )
			$conf['searchWord'] = $this->pi1Vars['searchWord'];

		// override mode if set with get param or session
		if ( $this->pi1Vars['mode'] ) {
			$GLOBALS['TSFE']->fe_user->setKey( 'ses', 'TagItemsOutputMode', $this->pi1Vars['mode'] );
			$GLOBALS['TSFE']->fe_user->storeSessionData();
		}
		if( $GLOBALS['TSFE']->fe_user->getKey( 'ses', 'TagItemsOutputMode' ) !== 'reset' )
			$conf['mode'] = $GLOBALS['TSFE']->fe_user->getKey( 'ses', 'TagItemsOutputMode' );
		
		$content = '';
		if ( count($conf['enabledRecords']) ) {
			foreach( $conf['enabledRecords'] as $table) {
				$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();
				if (($tables[$table]['Name'] == $table || $tables[$table] == $table)) {
					$conf['searchFields.'][$table] = t3lib_div::trimexplode(',', $conf['searchFields.'][$table]);
					$content .= $this->cObj->wrap( $this->renderTagItemList($conf, $table), $conf['tableWrap'] );
				}
			}
		}
		
		return $this->cObj->stdWrap($content, $conf['stdWrap.']);
	}
	
	/**
	 * it filters the given taggedElements acordingly to the config
	 *
	 * @param	array	$taggedElements: the elements to filter
	 * @param	array $conf: the config to use
	 * @return array: the filtered elements
	 */	
	function filterElements( $taggedElements, $conf ) {
		$els = array();
		foreach ( $taggedElements as $el ) {
			if( !isset($els[$el['uid']]) ) {
				$el['uid_local'] = array($el['uid_local']);
				$els[$el['uid']] = $el;
			} else {
				if ( is_array($els[$el['uid']]['uid_local']) )
					array_push( $els[$el['uid']]['uid_local'], $el['uid_local'] );
				else
					$els[$el['uid']]['uid_local'] = array($els[$el['uid']]['uid_local'], $el['uid_local']);
			}
		}
		
		foreach( $els as $el ) {
			sort( $el['uid_local'] );
		}
		
		foreach( $els as $id => $el ) {
			if ( count(array_diff($conf['selectedTags'], $el['uid_local'])) !== 0 )
				unset( $els[$id] );
		}
		
		return $els;
	}
	
	/**
	 * gives all the Elements you get for the given config and table
	 *
	 * @param	array	$conf: the config to use
	 * @param	string $table: use this table to get the elements
	 * @return array: the Elements
	 */	
	function getElements( $conf, $table ) {
		$els = array();
		$search = '';
		
		if ($conf['searchWord'] && $conf['searchFields.'][$table]) {
			foreach( $conf['searchFields.'][$table] as $searchField ) {
				if( $search !== '' )
					$search .= ' OR ';
				$search .= $table . '.' . $searchField . ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%'.$conf['searchWord'].'%',$table);
			}
			if( $search !== '' )
				$search = ' AND (' . $search . ')';
		}
		
		// xxx Calendar to implement?
		// if ($this->pi1Vars['from'] && $conf['timeFields.'][$table]) {
			// $fromTime = ' BETWEEN '.(strtotime($this->pi1Vars['from'])).' AND '.(strtotime($this->pi1Vars['from'])+(3600 * 24)-1);
			// $calendarSettings = ' AND '.$table.'.'.$conf['timeFields.'][$table].$fromTime;
		// }

		// if ($this->pi1Vars['from'] && $this->pi1Vars['to'] && $conf['timeFields.'][$table]) {
			// $fromTime = ' BETWEEN '.(strtotime($this->pi1Vars['from'])).' AND '.(strtotime($this->pi1Vars['to'])+(3600 * 24)-1);
			// $calendarSettings = ' AND '.$table.'.'.$conf['timeFields.'][$table].$fromTime;
		// }
		
		if ( count($conf['selectedTags']) ) {
			$tagsSelected = ' AND mm.uid_local IN(' . implode(',', $conf['selectedTags']) . ')';
			
			$groupBy = ($conf['mode'] === 'filter') ? '' : $table . '.uid';
			
			$elsQuery = array(
				$table.'.*, mm.uid_local, mm.uid_foreign, mm.pid_foreign, mm.tablenames',
				$table.' JOIN tx_tagpack_tags_relations_mm AS mm',
				'mm.uid_foreign='.$table.'.uid
					AND mm.tablenames=\''.$table.'\'
					AND '.$table.'.pid>0
					'.$this->cObj->enableFields($table).'
					'.$calendarSettings.$search.$tagsSelected,
				$groupBy,
				$table.'.' . ($conf['orderFields.'][$table] ? $conf['orderFields.'][$table] : $conf['orderFields.']['default']),
				$conf['maxItems'] 
			);
			
			$taggedElements = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows( $elsQuery[0], $elsQuery[1], $elsQuery[2], $elsQuery[3], $elsQuery[4], $elsQuery[5] );
			
			if ( $conf['mode'] === 'filter')
				$els = $this->filterElements( $taggedElements, $conf );
			else
				$els = $taggedElements;
				
		} elseif ( $conf['mode.'][$conf['mode'].'.']['defaultView'] == 'all' ) {
			$elsQuery = array(
				'*',
				$table,
				$table.'.pid>0'.$this->cObj->enableFields($table).'
					'.$calendarSettings.$search,
				$table.'.uid',
				$table.'.' . ($conf['orderFields.'][$table] ? $conf['orderFields.'][$table] : $conf['orderFields.']['default']),
				$conf['maxItems']
			);
			
			$els = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows( $elsQuery[0], $elsQuery[1], $elsQuery[2], $elsQuery[3], $elsQuery[4], $elsQuery[5] ); 
		}
		
		return $els;
	}

	/**
	 * renders the Items you get for the given config from the given table
	 *
	 * @param	array	$conf: the config to use
	 * @param	string $table: render the list for this table
	 * @return string: the List to output
	 */
	function renderTagItemList($conf, $table) {
		$content = '';

		$els = $this->getElements($conf, $table);
		
		if (count($els)) {
			foreach($els as $el) {
				$this->localCObj = $this->cObj;
				$this->localCObj->data = $el;
				
				$content .= $this->localCObj->cObjGetSingle($conf[$table], $conf[$table.'.']);
			}
		} else {
			$content = $this->pi_getLL('noItems');
		}
		
		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi3/class.tx_tagpack_pi3.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi3/class.tx_tagpack_pi3.php']);
}

?>