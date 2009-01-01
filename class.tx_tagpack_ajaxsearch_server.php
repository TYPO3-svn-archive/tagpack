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
* the ajax server for the "tagpack" extension.
* based on the "ajaxgroupsearch" extension.
*
* @author Johannes Künsebeck <kuensebeck@googlemail.com>
* @modifications for the tagpack JoH asenau <info@cybercraft.de>
* @package TYPO3
* @subpackage tagpack
*/
 
/*
* find home to /typo3/ and load init.php, so the whole api is loaded
*/
if (preg_match('#/(typo3conf/ext)/?#', $_SERVER['SCRIPT_NAME'], $matches)) {
	define('TYPO3_MOD_PATH', '../'.$matches[1].'/tagpack/');
}
else if (preg_match('#/(typo3/ext)/?#', $_SERVER['SCRIPT_NAME'], $matches)) {
	define('TYPO3_MOD_PATH', 'ext/tagpack/');
} else {
	die('Unable to detect install location for '.$_SERVER['SCRIPT_NAME'].', none of <ul><li>typo3conf/ext</li><li>typo3/ext</li></ul>');
}
 
 
$BACK_PATH = '../../../typo3/';
 
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
 
$LANG->includeLLFile('EXT:tagpack/locallang.xml');
 
class tx_tagpack_ajaxsearch_server {
	/**
	* get a local reference to the db for convinience, that all for now
	*
	* @param void
	* @return void
	*/
	function init() {
		$this->db = $GLOBALS['TYPO3_DB'];
		 
		 
	}
	 
	/**
	* entry point: fetch the field-configuration for the request (by analyzing $_GET['id']) and calls
	* the right function to serve the request (by a lookup in $_GET['function'], for now only 'groupsearch'
	* is implemented but maybe there will be a function for files and text-autocomplete later)
	*
	* @param array the pure GET - Request
	* @return string HTML for the ajax response
	*/
	function main($request) {
		// scary, but splits $_GET['id'] in to the needed fields
		$request['id'] = str_replace ('_ajaxsearch', '', $request['id']);
		$idArr = array_map(array(&$this, 'trim'), t3lib_div::trimExplode('[', $request['id']));
		 
		array_shift($idArr);
		$this->parentTable = array_shift($idArr);
		$this->uid = array_shift($idArr);
		$this->field = array_shift($idArr);
		$this->flexPath = $idArr;
		 
		t3lib_div::loadTCA('tx_tagpack_tags');
		// fetch the tca entry for this field,
		// TODO: add support for flexforms
		$fieldConfig = $GLOBALS['TCA']['tx_tagpack_tags']['columns']['relations']['config'];
		$fieldConfig['allowed'] = 'tx_tagpack_tags';
		$fieldConfig['prepend_tname'] = 0;
		$fieldConfig['wizards']['_VALIGN'] = 'top';
		$fieldConfig['wizards']['ajax_search']['type'] = 'userFunc';
		$fieldConfig['wizards']['ajax_search']['userFunc'] = 'tx_tagpack_ajaxsearch_client->renderAjaxSearch';
		$fieldConfig['wizards']['ajax_search']['params']['client']['startLength'] = 2;
		$fieldConfig['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['searchFields'] = 'name';
		$fieldConfig['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['enabledOnly'] = true;
		$fieldConfig['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['additionalWhere'] = 'tx_tagpack_tags.pid IN ('.$request['pid'].')';
		$fieldConfig['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['label'] = '###name###';
		 
		 
		if ($fieldConfig['type'] == 'flex') {
			$curRecord = t3lib_BEfunc::getRecord($this->parentTable, $this->uid);
			$flexds = t3lib_BEfunc::getFlexFormDS($fieldConfig, $curRecord, $this->parentTable);
			$fieldConfig = $flexds['sheets'][$this->flexPath[1]]['ROOT']['el'][$this->flexPath[3]]['TCEforms']['config'];
		}
		// call the function
		switch ($request['function']) {
			case 'groupsearch':
			return $this->ajaxGroupSearch($request, $fieldConfig);
			break;
		}
	}
	 
	/**
	* helper for trimming the id-string, @see tx_tagpack_ajaxsearch_server::main
	*
	* @param string the string to trim the ]'s off
	* @return string the right trimmed string
	**/
	function trim($str) {
		return rtrim($str, ']');
	}
	 
	/**
	* performs the search in all allowed tables, assemble the results and
	* render them through @see tx_tagpack_ajaxsearch_server::renderResults
	*
	* @param array the $_GET request
	* @param array the TCA entry for this field
	* @return string HTML for the ajax response
	**/
	function ajaxGroupSearch($request, $fieldConfig) {
		global $LANG;
		 
		$searchWord = $request['value'];
		$fieldId = $request['id'];
		$tableConfig = $fieldConfig['wizards']['ajax_search']['params']['tables'];
		$data = array();
		switch ($fieldConfig['type']) {
			case 'group' :
			{
				$lookupTables = t3lib_div::trimExplode(',', $fieldConfig['allowed']);
				$this->prefixTables = true;
			}
			break;
			case 'select' :
			{
				if ($fieldConfig['foreign_table']) {
					$lookupTables = array($fieldConfig['foreign_table']);
					if (!$tableConfig[$fieldConfig['foreign_table']]['additionalWhere']) {
						$tableConfig[$fieldConfig['foreign_table']]['additionalWhere'] = $fieldConfig['foreign_table_where'];
					}
				}
				else
					$lookupTables = array();
			}
			break;
			default:
			return '<li><em class="error">'.$LANG->getLL('ajaxgroupsearch_error_typeUnsupported').'</em>'.t3lib_div::debug($fieldConfig).'</li>';
			 
		}
		foreach ($lookupTables as $lookupTable) {
			$data[$lookupTable] = $this->searchTable($lookupTable, $searchWord, $tableConfig[$lookupTable]);
		}
		return $this->renderResults($data, $tableConfig, $fieldId, $searchWord);
	}
	 
	/**
	* look up the search term in one table
	*
	* @param string $table  the table to search in
	* @param string $searchWord the term to look up
	* @param mixed $config  an array of search options or simply 'true' for enabling this table
	* @return array an array of the found db-records
	**/
	function searchTable($table, $searchWord, $config = array()) {
		if ($config === 0 || $config === '0')
			return array();
		$tableTCActrl = $GLOBALS['TCA'][$table]['ctrl'];
		 
		 
		$conditions = array();
		$searchFieldsCSV = $config['searchFields'] ? $config['searchFields'] :
		$tableTCActrl['label'].','.$tableTCActrl['label_alt'];
		$searchFields = t3lib_div::trimExplode(',', $searchFieldsCSV, 1);
		 
		//access management
		$tableStatement = $table;
		if ($table != 'pages') {
			$tableStatement = $table.' JOIN pages ON ('.$table.'.pid = pages.uid)';
		}
		$conditions[] = $GLOBALS['BE_USER']->getPagePermsClause(1); //check read access
		 
		 
		$conditions[] = $this->db->searchQuery(array($searchWord), $searchFields, $table);
		$conditions[] = '1=1'.t3lib_BEfunc::deleteClause($table);
		 
		if ($config['enabledOnly']) {
			$conditions[] = '1=1'.t3lib_BEfunc::BEenableFields($table);
		}
		if ($config['additionalWhere']) {
			$conditions[] = $config['additionalWhere'];
		}
		 
		$data = array();
		 
		$fields = $table.'.*';
		$where = join(' AND ', $conditions);
		$limit = '0,'.intval($config['limit'] ? $config['limit'] : 10);
		 
		 
		 
		$res = $this->db->exec_SELECTquery($fields, $tableStatement, $where, '', '', $limit);
		while ($row = $this->db->sql_fetch_assoc($res)) {
			$data[] = $row;
		}
		 
		return $data;
	}
	 
	/**
	* render the results as list items
	*
	* @param array $data   the search results as '$tablename' => array($record1,$record2,...)
	* @param array $tableConfig the render configuration
	* @param string $id    the id of the result list for javascript reference
	* @param string the rendered list items HTML
	*/
	function renderResults($data = array(), $tableConfig, $id, $searchWord) {
		global $LANG;
		 
		if (0 == count($data))
			return '<li><em class="error">'.$LANG->getLL('ajaxgroupsearch_error_noTablesConfigured').'</em></li>';
		 
		 
		$content = '';
		$fieldId = 'data'.substr($id, strpos($id, '['));
		foreach($data as $table => $rows) {
			$tableTCActrl = $GLOBALS['TCA'][$table]['ctrl'];
			$config = $tableConfig[$table];
			$searchFieldsCSV = $config['searchFields'] ? $config['searchFields'] :
			$tableTCActrl['label'].','.$tableTCActrl['label_alt'];
			$searchFields = t3lib_div::trimExplode(',', $searchFieldsCSV, 1);
			 
			if (0 == count($rows))
				continue;
			 
			foreach ($rows as $row) {
				// build label
				if ($config['label']) {
					$label = htmlspecialchars(strip_tags($this->template($config['label'], $row)));
				} else {
					$label = htmlspecialchars(strip_tags(t3lib_BEfunc::getRecordTitle($table, $row, 1)));
				}
				
				
				 
				 
				// build title as concatenation of all search fields (so you know why you found it)
				if (is_array($searchFields)) {
					$titles = array();
					foreach ($searchFields as $sf) {
						if ($row[$sf]) $titles[] = $row[$sf];
					}
					$title = htmlspecialchars(strip_tags(join(', ', $titles)));
				}
				 
				// build js - value
				 
				 
				 
				$value = $row['uid'];
				//use tableprefix if we serve multiple table
				if ($this->prefixTables === true)
					$value = $table.'_'.$row['uid'];
				 
				$icon = t3lib_iconWorks::getIconImage($table, $row, '', 'title="'.t3lib_BEfunc::getRecordIconAltText($row, $table).'"');
				$onclick = 'setFormValueFromBrowseWin(\''.$fieldId.'\',\''.$value.'\',\''.$label.'\');return true;';
				$label = str_replace($searchWord,'<strong>'.$searchWord.'</strong>',$label);
				$label = str_replace(strtolower($searchWord),'<strong>'.strtolower($searchWord).'</strong>',$label);
				$content .= '<li><a href="#" title="'.$title.'" onclick="'.$onclick.'" >'.$icon.'<span>'.$label.'</span></a></li>';
			}
		}
		if (!$content)
			$content = '<li><em>'.$LANG->getLL('ajaxgroupsearch_error_noResults').'</em></li>';
		return $content;
		 
	}
	 
	/**
	* a simple template function, used for generating userdefined labels
	*
	* @param string $string a string with template markers ###FIELDNAME###, you can define optional enclosings with ###|a prefix|FIELDNAME|a postfix|###
	*       the parts between the "|" will only be printed if FIELDNAME does not evaluate to false
	* @param array $data the data as an array with entries in the format 'FIELDNAME' => 'value'
	* @return string with the markers replaced
	*/
	function template($string, $data) {
		if (preg_match_all('/###((\|.+\|)?(\w+)(\|.+\|)?)###/', $string, $matches, PREG_SET_ORDER)) {
			$mapping = array();
			foreach ($matches as $match) {
				$wholeMatch = $match[0];
				$field = $match[3];
				$prefix = trim($match[2], '|');
				$postfix = trim($match[4], '|');
				$mapping[$match[0]] = $data[$field] ? $prefix.$data[$field].$postfix :
				'';
			}
			return str_replace(array_keys($mapping), array_values($mapping), $string);
		} else {
			return $string;
		}
	}
}
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/class.tx_tagpack_ajaxsearch_server.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/class.tx_tagpack_ajaxsearch_server.php']);
}
 
$SOBE = t3lib_div::makeInstance('tx_tagpack_ajaxsearch_server');
$SOBE->init();
echo $SOBE->main(t3lib_div::_GET());
 
 
?>