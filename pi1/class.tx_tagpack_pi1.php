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
 * Plugin 'TagControl' for the 'tagpack' extension.
 *
 * @author JoH asenau <info@cybercraft.de>
 * @author Thomas Allmer <at@delusionworld.com>
 * @package TYPO3
 * @subpackage tx_tagpack
 */
class tx_tagpack_pi1 extends tslib_pibase {
	var $prefixId = 'tx_tagpack_pi1'; // Same as class name
	var $scriptRelPath = 'pi1/class.tx_tagpack_pi1.php'; // Path to this script relative to the extension dir.
	var $extKey = 'tagpack'; // The extension key.
	var $pi_checkCHash = true;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content, $conf) {
		$conf['selectedTags'] = $this->piVars['selectedTags'] ? t3lib_div::intExplode(',', $this->piVars['selectedTags']) : t3lib_div::intExplode(',', $conf['selectedTags']);
		if( $conf['selectedTags'][0] == 0 )
			$conf['selectedTags'] = array();
		else {
			$selectedTags = array();
			foreach( $conf['selectedTags'] as $id => $tag )
				$selectedTags[$tag] = $tag;
			$conf['selectedTags'] = $selectedTags;
		}

		$conf['tagPidList'] = t3lib_div::trimExplode(',', $conf['tagPidList']);
		$conf['enabledRecords'] = t3lib_div::trimExplode(',', $conf['enabledRecords']);
		$conf['typolink.']['parameter'] = $conf['typolink.']['parameter'] ? $conf['typolink.']['parameter'] : $GLOBALS['TSFE']->id;
		$conf['active.']['typolink.']['parameter'] = $conf['active.']['typolink.']['parameter'] ? $conf['active.']['typolink.']['parameter'] : $conf['typolink.']['parameter'];
		
		$conf['type'] = t3lib_div::trimExplode(',', $conf['type']);
		if( $this->piVars['searchWord'] )
			$conf['searchWord'] = $this->piVars['searchWord'];
		
		// override mode if set with get param or session
		if ( $this->piVars['mode'] ) {
			$GLOBALS['TSFE']->fe_user->setKey( 'ses', 'TagItemsOutputMode', $this->piVars['mode'] );
			$GLOBALS['TSFE']->fe_user->storeSessionData();
		}
		if( $GLOBALS['TSFE']->fe_user->getKey( 'ses', 'TagItemsOutputMode' ) !== 'reset' )
			$conf['mode'] = $GLOBALS['TSFE']->fe_user->getKey( 'ses', 'TagItemsOutputMode' );
		
		$content = '';
		foreach( $conf['type'] as $type ) {
			$typeCall = 'renderTag' . ucfirst($type);
			$conf['type'] = $type;
			
			// set parameter to current page if not set
			$conf['type.'][$conf['type'].'.']['typolink.']['parameter'] = $conf['type.'][$conf['type'].'.']['typolink.']['parameter'] ? $conf['type.'][$conf['type'].'.']['typolink.']['parameter'] : $GLOBALS['TSFE']->id;
			$conf['type.'][$conf['type'].'.']['active.']['typolink.']['parameter'] = $conf['type.'][$conf['type'].'.']['active.']['typolink.']['parameter'] ? $conf['type.'][$conf['type'].'.']['active.']['typolink.']['parameter'] : $GLOBALS['TSFE']->id;
			$content .=  $this->cObj->stdWrap( $this->$typeCall($conf), $conf['type.'][$conf['type'].'.']['stdWrap.'] );
		}
			
		return $this->cObj->stdWrap($content, $conf['stdWrap.']);
	}
	
	/**
	 * returns a well formated array containing all the tags you get with the given config
	 *
	 * @param	array $conf - the config to use
	 * @return array - the tags 
	 */		
	function getTagList($conf) {

		$onlyTagsWithContent = '';
		if( $conf['type.'][$conf['type'].'.']['onlyTagsWithContent'] ) {
			$onlyTagsWithContent = '
					AND uid IN (select uid_local
				from tx_tagpack_tags_relations_mm
				where tablenames IN ("' . implode('", "', $conf['enabledRecords']) . '"))
			';
		}

		$dbTagsQuery = array(
			'*',
			'tx_tagpack_tags',
			'pid IN(' . implode(',', $conf['tagPidList']) . ')
				' . $onlyTagsWithContent . '
				AND NOT deleted
				AND NOT hidden
				AND pid > 0',
			'',
			'uid'
		);
		
		$dbTags = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows( $dbTagsQuery[0], $dbTagsQuery[1], $dbTagsQuery[2], $dbTagsQuery[3], $dbTagsQuery[4], $dbTagsQuery[5] );
		
		$tags = array();
		foreach( $dbTags as $id => $tag ) {
			if( in_array($tag['uid'], $conf['selectedTags']) ) {
				$tag['active'] = true;
				// remove these tags from the selecteTags
				if ($conf['mode'] === 'filterGroup')
					unset($conf['selectedTags'][$tag['uid']]);
			}
			$tags[$tag['uid']] = $tag;
		}
		
		// set the according links
		foreach( $tags as $id => $tag ) {
			$tags[$id]['typolink.'] = $conf['type.'][$conf['type'].'.']['typolink.'];
			if( $tag['active'] )
				$tags[$id]['typolink.'] = $conf['type.'][$conf['type'].'.']['active.']['typolink.'];
			
			if ( ($conf['mode'] === 'filter') || ($conf['mode'] === 'filterGroup') || ($conf['mode'] === 'select') ) {
				if( !$tag['active'] )
					$tags[$id]['typolink.']['additionalParams'] = '&' . $this->prefixId . '[selectedTags]=' . implode(',',$conf['selectedTags']) . ($conf['selectedTags'] ? ',' : '') . $tag['uid'];
				else {
					$tmp = $conf['selectedTags'];
					unset($tmp[$tag['uid']]);
					$tags[$id]['typolink.']['additionalParams'] = '&' . $this->prefixId . '[selectedTags]=' . implode(',', $tmp);
				}
			} else {
				$tags[$id]['typolink.']['additionalParams'] = '&' . $this->prefixId . '[selectedTags]=' . $tag['uid'];
			}
			$tags[$id]['typolink.']['additionalParams'] .= $this->keepGetVars( $conf, 'uid' );
			$tags[$id]['typolink.']['title'] = $tag['relations'] > 1 ? $tag['relations'].' items tagged with ' . $tag['name'] : $tag['relations'].' item tagged with ' . $tag['name'];
		}
		
		return $tags;
	}
	
	/**
	 * get's the get vars and add them either to a typolink or to a form
	 *
	 * @param	array $conf: the config to use
	 * @param string $unset: you need to manually say what not to set again
	 * @param string $mode: add it to a typolink (default) or to a form
	 * @return string - the code to be added to the typolink or to the form
	 */
	function keepGetVars($conf, $unset, $mode = 'typolink') {
		$content = '';
		unset( $conf['keepGetVars.'][$unset] );
		
		foreach( $conf['keepGetVars.'] as $keepGetVar => $value ) {
			if( $this->piVars[$keepGetVar] ) {
				if ($mode === 'typolink')
					$content .= '&' . $this->prefixId . '[' . $keepGetVar . ']=' . $this->piVars[$keepGetVar];
				if ($mode === 'form')
					$content .= '<input type="hidden" name="' . $this->prefixId . '[' . $keepGetVar . ']" value="' . $this->piVars[$keepGetVar] . '" />';
			}
		}
		return $content;
	}
	
	/**
	 * create a typolink for every tag
	 *
	 * @param	array $conf - the config to use
	 * @return string - the rendered tags
	 */	
	function renderTags($tags) {
		$content = '';
		foreach( $tags as $tag ) {
			$content .= $this->cObj->typolink($tag['name'], $tag['typolink.']);
		}
		return $content;
	}
	
	/**
	 * get the tags and just outputs it...
	 *
	 * @param	array $conf - the config to use
	 * @return string - the rendered TagList
	 */	
	function renderTagList($conf) {
		$tags = $this->getTagList( $conf );
		return $this->renderTags( $tags );
	}

	/**
	 * get the Tags and give the Tags different sizes (a.k.a Tag Cloud)
	 *
	 * @param	array $conf - the config to use
	 * @return string - the rendered TagCloud
	 */
	function renderTagCloud($conf) {
		$tags = $this->getTagList( $conf );
		
		$tagRelations = array();
		foreach( $tags as $tag ) {
			$tagRelations[$tag['relations']] = $tag;
		}
		
		$max = end( $tagRelations );
		$max = $max['relations'];
		for( $i = 0; $i < $conf['type.']['cloud.']['maxNumberOfSizes']; $i++ )
			prev( $tagRelations );
		
		$min = current( $tagRelations );
		$min = $min['relations'];
		if( !$min )
			$min = 0;
			
		foreach( $tags as $id => $tag ) {
			$difference = ($max - $min) > 1 ? ($max - $min) : 1;
			$percentage = ($tag['relations'] - $min) / $difference;
			$size = intval(($conf['type.']['cloud.']['maxFontSize'] - $conf['type.']['cloud.']['minFontSize']) * $percentage + $conf['type.']['cloud.']['minFontSize']);
			
			$tags[$id]['typolink.']['ATagParams'] .= 'style="font-size: ' . $size . 'px; line-height: ' . ceil( $conf['type.']['cloud.']['maxFontSize'] * 0.7 ) . 'px;"';
		}
		
		return $this->renderTags( $tags );
	}

	/**
	 * gives a simple way to switch between modes
	 *
	 * @param	array $conf - the config to use
	 * @return string - rendered form for output
	 */
	function renderTagSwitch($conf) {
		$content = '';
		$conf['type.']['switch.']['allow'] = t3lib_div::trimExplode(',', $conf['type.']['switch.']['allow']);
		
		foreach( $conf['type.']['switch.']['allow'] as $filterMode ) {
			$typolink = ($conf['mode'] == $filterMode) ? $conf['type.']['switch.']['active.']['typolink.'] : $conf['type.']['switch.']['typolink.'];
			$typolink['additionalParams'] = '&' . $this->prefixId . '[mode]=' . $filterMode;
			
			switch( $filterMode ) {
				case 'simple': $typolink['additionalParams'] .= '&' . $this->prefixId . '[selectedTags]=' . reset( $conf['selectedTags'] ); break;
				case 'reset': break;
				default: $typolink['additionalParams'] .= '&' . $this->prefixId . '[selectedTags]=' . implode(',',$conf['selectedTags']); $typolink['additionalParams'] .= $this->keepGetVars( $conf, 'mode' );
			}
			$content .= $this->cObj->typolink( $conf['modeTitle.'][$filterMode], $typolink);
		}
		
		return $content;
	}

	/**
	 * renders a form with an input field
	 *
	 * @param	array $conf - the config to use
	 * @return string - rendered form for output
	 */
	function renderTagSearch($conf) {
		$content = '';
		$typolink = $conf['type.']['search.']['typolink.'];
		
		$content .= $this->cObj->wrap( $this->cObj->typolink('', $typolink), $conf['type.']['search.']['formWrap'] );
		$content .= '<input type="text" name="' . $this->prefixId . '[searchWord]" value="' . $conf['searchWord'] . '" />';
		
		$content .= $this->keepGetVars( $conf, 'searchWord', 'form' );
	
		return $content;
	}

	/**
	 * outputs 2 inputs to select a date range
	 *
	 * @param	array $conf - the config to use
	 * @return string - rendere form for output
	 */
	function renderTagCalendar($conf) {
		if (t3lib_extMgm::isLoaded('rlmp_dateselectlib')) {
			require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');
			tx_rlmpdateselectlib::includeLib();
			
			$typolink = $conf['type.']['calendar.']['typolink.'];
			
			$content .= $this->cObj->wrap( $this->cObj->typolink('', $typolink), $conf['type.']['calendar.']['formWrap'] );
			
			$content .= '<input type="text" name="'.$this->prefixId.'[from]" id="'.$this->prefixId.'[from]" value="'.$this->piVars['from'].'" />'. tx_rlmpdateselectlib::getInputButton($this->prefixId.'[from]', $dateSelectorConf);
			$content .= '<input type="text" name="'.$this->prefixId.'[to]" id="'.$this->prefixId.'[to]" value="'.$this->piVars['to'].'" />'. tx_rlmpdateselectlib::getInputButton($this->prefixId.'[to]', $dateSelectorConf);
		} else {
			$content = 'No rlmp_dateselectlib installed!';
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi1/class.tx_tagpack_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi1/class.tx_tagpack_pi1.php']);
}

?>