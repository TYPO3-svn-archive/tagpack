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

require_once(PATH_tslib . 'class.tslib_pibase.php');


/**
 * Plugin 'Tag Nominations' for the 'tagpack' extension.
 *
 * @author JoH asenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_tagpack
 */
class tx_tagpack_pi3 extends tslib_pibase
{
	var $prefixId = 'tx_tagpack_pi3';
	// Same as class name
	var $scriptRelPath = 'pi3/class.tx_tagpack_pi3.php'; // Path to this script relative to the extension dir.
	var $extKey = 'tagpack'; // The extension key.

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content, $conf)
	{
		$this->conf = $conf;
		$this->pi_loadLL();
		$this->pi1Vars = t3lib_div::_GP('tx_tagpack_pi1');
		$tagUidArray = t3lib_div::intExplode(',', $this->pi1Vars['uid']);
		foreach ($tagUidArray as $key => $value) {
			if ($value == t3lib_div::_GET('tx_tagpack_pi3_removeItems')) {
				unset($tagUidArray[$key]);
			}
		}
		$tagUid = implode(',', $tagUidArray);
		$tagUid = $tagUid ? $tagUid :
				0;
		$conf['renderObj.']['10'] = 'HTML';
		$tags = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_tagpack_tags',
			'uid IN(' . $tagUid . ')
				AND NOT deleted
				AND NOT hidden
				AND pid>0',
			'',
			'name ASC');

		$this->enableResultList = 0;

		if (count($tags)) {
			$this->enableResultList = 1;
		}

		if (count($this->pi1Vars)) {
			foreach ($this->pi1Vars as $value) {
				if ($value) $this->enableResultList = 1;
			}
		}

		if ($conf['taggedElements.']['enabledContent'] && $this->enableResultList) {
			$conf['renderObj.']['10.']['value'] .= $this->makeElementList('tt_content', $conf, $tags, $tagUid);
		}
		if ($conf['taggedElements.']['enabledRecords'] && $this->enableResultList) {
			$enabledRecords = t3lib_div::trimexplode(',', $conf['taggedElements.']['enabledRecords']);
			foreach ($enabledRecords as $table) {
				$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();
				if (($tables[$table]['Name'] == $table || $tables[$table] == $table)) {
					$conf['renderObj.']['10.']['value'] .= $this->makeElementList($table, $conf, $tags, $tagUid);
				}
			}
		}
		return $this->cObj->cObjGetSingle($conf['renderObj'], $conf['renderObj.']);
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @param	[type]		$tags: ...
	 * @param	[type]		$tagUid: ...
	 * @return	The		content that is displayed on the website
	 */
	function makeElementList($table, $conf, $tags, $tagUid)
	{

		$sortingTime = $conf['taggedElements.']['timeFields.'][$table] ? $conf['taggedElements.']['timeFields.'][$table]
				: 'tstamp';

		if ($tagUid) {
			$tagsSelected = ' AND tx_tagpack_tags_relations_mm.uid_local IN(' . $tagUid . ')';
		}

		if ($this->pi1Vars['from'] && $conf['taggedElements.']['timeFields.'][$table]) {
			$fromTime = ' BETWEEN ' . (strtotime($this->pi1Vars['from'])) . ' AND ' . (strtotime($this->pi1Vars['from']) + (3600 * 24) - 1);
			$calendarSettings = ' AND ' . $table . '.' . $conf['taggedElements.']['timeFields.'][$table] . $fromTime;
		}

		if ($this->pi1Vars['from'] && $this->pi1Vars['to'] && $conf['taggedElements.']['timeFields.'][$table]) {
			$fromTime = ' BETWEEN ' . (strtotime($this->pi1Vars['from'])) . ' AND ' . (strtotime($this->pi1Vars['to']) + (3600 * 24) - 1);
			$calendarSettings = ' AND ' . $table . '.' . $conf['taggedElements.']['timeFields.'][$table] . $fromTime;
		}

		if ($this->pi1Vars['searchWord'] && $conf['taggedElements.']['searchFields.'][$table]) {
			$searchFieldArray = t3lib_div::trimexplode(',', $conf['taggedElements.']['searchFields.'][$table]);
			foreach ($searchFieldArray as $searchField) {
				$searchSettings .= $searchSettings
						? (' OR ' . $table . '.' . $searchField . ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->pi1Vars['searchWord'] . '%', $table))
						:
						($table . '.' . $searchField . ' LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->pi1Vars['searchWord'] . '%', $table));
			}

			$searchTags = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_tagpack_tags',
				'name LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $this->pi1Vars['searchWord'] . '%', 'tx_tagpack_tags')
			);

			if (!$GLOBALS['TYPO3_DB']->sql_error()) {
				while ($searchTagUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($searchTags)) {
					$tagsSearched .= $tagsSearched ? ',' . $searchTagUid['uid']
							: 'tx_tagpack_tags_relations_mm.uid_local IN(' . $searchTagUid['uid'];
				}
			}


		}

		$tagsSearched .= $tagsSearched ? ')' : '';
		$searchSettings = $searchSettings ? ' AND (' . $searchSettings . ($tagsSearched ? ' OR ' . $tagsSearched
				: '') . ')' : ($tagsSearched ? ' AND ' . $tagsSearched : '');

		if ($conf['taggedElements.']['additionalFilters.'][$table . '.']) {
			$filters = $conf['taggedElements.']['additionalFilters.'][$table . '.'];
			foreach ($filters as $fieldName => $filterSettings) {
				$getVar = t3lib_div::_GET($filterSettings['GETvar']);
				if (is_array($getVar) && !$getVar[$filterSettings['GETvar.']['key']]) {
					$getVar = false;
				}
				else if ($getVar[$filterSettings['GETvar.']['key']]) {
					$getVars[] = $getVar[$filterSettings['GETvar.']['key']];
				}
			}
		}

		if ($conf['taggedElements.']['additionalFilters.'][$table . '.'] && count($getVars)) {
			$limit = '';
		} else {
			$limit = $conf['taggedElements.']['maxItems'];
		}
		$taggedElements = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$table . '.*,COUNT(tx_tagpack_tags_relations_mm.uid_foreign) AS counter',
			$table . ' JOIN tx_tagpack_tags_relations_mm ON (tx_tagpack_tags_relations_mm.uid_foreign=' . $table . '.uid AND tx_tagpack_tags_relations_mm.tablenames=\'' . $table . '\')',
			$table . '.pid>0
				' . $this->cObj->enableFields($table) . '
				' . $calendarSettings . $searchSettings . $tagsSelected,
			$table . '.uid',
			(($conf['taggedElements.']['sortFields.'][$table])
					? $table . '.' . $conf['taggedElements.']['sortFields.'][$table]
					: 'counter DESC,' . $table . '.uid,' . $table . '.' . $sortingTime . ' DESC'),
			$limit);
		if (!$GLOBALS['TYPO3_DB']->sql_error()) {
			if (count($tags)) {
				foreach ($tags as $key => $tag) {
					if ($tag['uid'] == t3lib_div::_GET('tx_tagpack_pi3_removeItems')) {
						unset($tags[$key]);
					} else {
						$newUidList .= $newUidList
								? ',' . $tag['uid']
								: $tag['uid'];
					}
				}
			}
			$filters = $conf['taggedElements.']['additionalFilters.'][$table . '.'];
			if (count($tags)) {
				foreach ($tags as $key => $tag) {
					if (count($tags) > 1) {
						$linkConf = array(
							'parameter' => $GLOBALS['TSFE']->id . ' - tx_tagack_pi3_removeitem',
							'additionalParams' => '&tx_tagpack_pi1[uid]=' . $newUidList . '&tx_tagpack_pi3_removeItems=' . $tag['uid'],
							'title' => $tag['name'] . ' ' . $this->pi_getLL('remove_item_from_list'),
							'useCacheHash' => 1,
							'wrap' => $conf['taggedElements.']['breadcrumbWrap'],
						);
					}
					$tagLink = $this->cObj->typolink($tag['name'], $linkConf);
					$tagname .= $tagname
							? (($key + 1) < count($tags)
									? ', ' . $tagLinka
									: ' ' . $this->pi_getLL('and') . ' ' . $tagLink)
							: $tagLink;
				}
			}
			if (count($filters)) {
				foreach ($filters as $fieldName => $filterSettings) {
					$getVar = t3lib_div::_GET($filterSettings['GETvar']);
					if (is_array($getVar) && !$getVar[$filterSettings['GETvar.']['key']]) {
						$getVar = false;
					} else if ($getVar[$filterSettings['GETvar.']['key']]) {
						$getVar = $getVar[$filterSettings['GETvar.']['key']];
						$linkConf['additionalParams'] .= '&' . $filterSettings['GETvar'] . '[' . $filterSettings['GETvar.']['key'] . ']=' . $getVar;
					} else {
						$linkConf['additionalParams'] .= '&' . $filterSettings['GETvar'] . '=' . $getVar;
					}
					if ($getVar) {
						$headerAppendix[$fieldName] = $filterSettings['label']
								? ' ' . $this->cObj->cObjgetSingle($filterSettings['label'], $filterSettings['label.'])
								: '';
					}
				}
			}
			if ($conf['taggedElements.']['additionalFilters.'][$table . '.']) {
				$filters = $conf['taggedElements.']['additionalFilters.'][$table . '.'];
			} else {
				$filters = array();
			}
			while ($taggedElement = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($taggedElements)) {
				foreach ($filters as $fieldName => $filterSettings) {
					$getVar = t3lib_div::_GET($filterSettings['GETvar']);
					if (is_array($getVar) && !$getVar[$filterSettings['GETvar.']['key']]) {
						$getVar = false;
					}
					else if ($getVar[$filterSettings['GETvar.']['key']]) {
						$getVar = $getVar[$filterSettings['GETvar.']['key']];
					}
					$fieldName = str_replace('.', '', $fieldName);
					if ((!$taggedElement[$fieldName] && $getVar) || ($taggedElement[$fieldName] != $getVar) && $getVar) {
						$taggedElement = array();
					}
					else if ($getVar && $filterSettings['foreign_table'] && !$filterSettings['mm_table'] && !t3lib_div::inList($taggedElement[$fieldName], $getVar)) {
						$taggedElement = array();
					}
					else if ($getVar && $filterSettings['foreign_table'] && $filterSettings['mm_table']) {
						$availableElementsSelect = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
							$table . '.uid',
							$table,
							$filterSettings['mm_table'],
							$filterSettings['foreign_table'],
							' AND ' . $table . '.uid=' . intval($taggedElement['uid']) . ' AND ' . $filterSettings['foreign_table'] . '.uid=' . intval($getVar));
						if (!$GLOBALS['TYPO3_DB']->sql_num_rows($availableElementsSelect)) {
							$taggedElement = array();
						}
					}
				}
				if ($taggedElement['counter'] >= count($tags)) {
					$this->localCObj = $this->cObj;
					$this->localCObj->data = $taggedElement;
					$itemList .= $this->cObj->wrap($this->localCObj->cObjGetSingle($conf['taggedElements.'][$table], $conf['taggedElements.'][$table . '.']), $conf['taggedElements.']['itemWrap']);
					$header = $this->pi_getLL('someItems') . ' ' . (
					$table == 'tt_content' ? $conf['taggedElements.']['contentLabel'] :
							$conf['taggedElements.']['recordLabels.'][$table]) . ' ' . (
					$tagUid ? (
					count($tags) > 1 ? $this->pi_getLL('taggedWith2') :
							$this->pi_getLL('taggedWith')
					) : '') . ' ' . $tagname;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($taggedElements);
			if (!$header) {
				$header = $this->pi_getLL('noItems') . ' ' . (
				$table == 'tt_content' ? $conf['taggedElements.']['contentLabel'] :
						$conf['taggedElements.']['recordLabels.'][$table]) . ' ' . (
				$tagUid ? (
				count($tags) > 1 ? $this->pi_getLL('taggedWith2') :
						$this->pi_getLL('taggedWith')
				) : '') . ' ' . $tagname;
			}
			if (count($headerAppendix)) {
				foreach ($headerAppendix as $appendixText) {
					$header .= $appendixText;
				}
			}
			$content .= $this->cObj->wrap($header, $conf['taggedElements.']['headerWrap']);
			$content .= $this->cObj->wrap($itemList, $conf['taggedElements.']['groupWrap']);
			return $content;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi3/class.tx_tagpack_pi3.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/pi3/class.tx_tagpack_pi3.php']);
}

?>