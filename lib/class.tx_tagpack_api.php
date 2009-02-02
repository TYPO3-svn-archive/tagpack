<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2009 Jo Hasenau, Benjamin Mack
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
 * Base library for looking for tags, adding or removing them etc
 * Includes a bunch of static functions that can be called separately
 *
 * @author Jo Hasenau
 * @author Benjamin Mack (benni add typo3 org)
 * @package TYPO3
 * @subpackage tx_tagpack
 */

class tx_tagpack_api {
	/* this is a constant that matches the table where the tags are stored */
	const tagTable       = 'tx_tagpack_tags';
	/* this is a constant that matches the table where the tag relations are stored */
	const relationsTable = 'tx_tagpack_tags_relations_mm';


	/**
	 * Fetches the storage PID from the configuration, based on the storage PID
	 * that is set through the pages TSconfig
	 * It uses a one-time mechanism to only fetch the storage PID once and then
	 * stores it as a static variable
	 *
	 * @param	$pid	the integer to the page where the tsconfig is stored, needed for the backend
	 * @return	int		the storage PID where all tags are stored
	 */
	function getTagStoragePID($pid = 0) {
		static $storagePID;
		if (!$storagePID) {
			if (is_object($GLOBALS['TSFE'])) {
				// get the storage PID in the frontend
				$GLOBALS['TSFE']->getPagesTSconfig();
				$storagePID = $GLOBALS['TSFE']->pagesTSconfig['tx_tagpack_tags.']['getTagsFromPid'];
			} else {
				// get storage PID in the backend
				$TSconfig = t3lib_BEfunc::getPagesTSconfig($pid);
				$storagePID = $TSconfig['tx_tagpack_tags.']['getTagsFromPid'];
			}
		}
		return intval($storagePID);
	}

	/**
	 * Checks whether a tag already exists by creating a lookup on the tag
	 * and then returns.
	 * 
	 * @param	$tagName	a string containing the name of the tag
	 * @return	bool		whether it exists or not
	 */
	function tagExists($tagName) {
		$existingTag = tx_tagpack_api::getTagDataByTagName($tagName);
		return (count($existingTag) ? true : false);
	}
	

	/**
	 * Adds a new tag to the DB without any relationships yet.
	 * 
	 * @param	$tagName	a string containing the name of the tag
	 * @return	int			the uid of the newly added tag, or zero if the tagName was empty
	 */
	function addTag($tagName) {
		$tagName = trim($tagName);
		if (!empty($tagName) && !tx_tagpack_api::tagExists($tagName)) {
			$storagePID = tx_tagpack_api::getTagStoragePID();

			// now we have to build the value array for the following insert action
			$newTagRow = array(
				'name'             => $tagName,
				'tstamp'           => time(),
				'crdate'           => time(),
				'cruser_id'        => 0,
				'pid'              => $storagePID,
				'sys_language_uid' => 0,
				'deleted'          => 0,
				'hidden'           => 0,
				'relations'        => 0
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(tx_tagpack_api::tagTable, $newTagRow);
			return $GLOBALS['TYPO3_DB']->sql_insert_id();
		} else {
			return 0;
		}
	}


	/**
	 * Removes a tag from the DB
	 * 
	 * @param	$tagName    a string containing the tag name
	 * @param	$deleteRelations	a flag whether to delete the relations as well
	 * @return	void
	 */
	function deleteTag($tagName, $deleteRelations = true) {
		$tagData = tx_tagpack_api::getTagDataByTagName($tagName);
		$tagUid = intval($tagData['uid']);
		if ($tagUid) {
            if ($deleteRelations) {
		        $elements = tx_tagpack_api::getAttachedElementsForTag($tagUid);
		        foreach ($elements as $element) {
		            tx_tagpack_api::removeTagFromElement($tagName, $element['uid'], $element['table']);
		        }
		    }
			$storagePID = tx_tagpack_api::getTagStoragePID();
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(tx_tagpack_api::tagTable, 'uid = ' . $tagUid . ' AND pid = ' . $storagePID);
		}
	}


	/**
	 * Returns an array full of all information about the tag
	 * found by the tagName
	 * 
	 * @param	$tagName	a string containing the name of the tag
	 * @return	array		the result row from the DB or an empty array if nothing was found
	 */
	function getTagDataByTagName($tagName) {
		$tagData = array();
		$tagName = trim($tagName);
		if (!empty($tagName)) {
			$storagePID   = tx_tagpack_api::getTagStoragePID();
			$addCondition = ($storagePID > 0 ?  ' AND pid = ' . $storagePID : '');

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				tx_tagpack_api::tagTable,
				'hidden = 0 AND deleted = 0
					AND name = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tagName, $this->tagTable) .
					$addCondition,
				'',
				'',
				1 // limit to one result
			);
			$tagData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $tagData;
	}

	/**
	 * Returns an array full of all information about the tag
	 * found by the tag UID
	 * 
	 * @param	$tagUid		the ID in the DB indentifying the tag
	 * @return	array		the result row from the DB or an empty array if nothing was found
	 */
	function getTagDataById($tagUid) {
		$tagData = array();
		$tagUid  = intval($tagUid);
		if ($tagUid > 0) {
			$storagePID = tx_tagpack_api::getTagStoragePID();
			$addCondition = ($storagePID > 0 ?  ' AND pid = ' . $storagePID : '');

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				tx_tagpack_api::tagTable,
				'hidden = 0 AND deleted = 0 AND uid = ' . $tagUid .
				$addCondition,
				'',
				'',
				1 // limit to one result
			);
			$tagData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $tagData;
	}

	/**
	 * Returns an array with tag UIDs that are attached to any element (UID / table pair)
	 * found in the DB
	 * The two parameters are basically something like
	 * $elementUid = 12, $elementTable = 'tt_news'. This function then returns all
	 * tag UIDs that are attached to this element
	 * 
	 * @param	$elementUid		the UID of the element
	 * @param	$elementTable	the table of the element
	 * @return	array			an array containing all tag UIDs 
	 */
	function getAttachedTagsForElement($elementUid, $elementTable) {
		$tagUids = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid_local',
			tx_tagpack_api::relationsTable,
			'tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($elementTable, 'tx_tagpack_tags_relations_mm') . '
				AND hidden = 0 AND deleted = 0 AND uid_foreign = ' . intval($elementUid)
		);
		while ($res = $GLOBALS['TYPO3_DB']->sql_fetch_row($res) {
			$tagUids[] = $res[0];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $tagUids;
	}


	/**
	 * Returns an array with full of element pairs (UID / table) that are attached
	 * to a certain tagName
	 * 
	 * @param	$tagName		a string containing the name of the tag
	 * @return	array			an array containing pairs of "uid" and "table"
	 */
	function getAttachedElementsForTagName($tagName, $limitToTable = '') {
		$tagData = tx_tagpack_api::getTagDataByName($tagName);
		return tx_tagpack_api::getAttachedElementsForTagId($tagData['uid'], $limitToTable);
	}


	/**
	 * Returns an array with full of element pairs (UID / table) that are attached
	 * to a certain tagUid
	 * 
	 * @param	$tagUid		an integer that uniquely identifies the tag in the DB table
	 * @return	array		an array containing pairs of "uid" and "table"
	 */
	function getAttachedElementsForTagId($tagUid, $limitToTable = '') {
		$elements = array();
		if ($tagUid > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid_foreign AS uid, tablenames AS table',
				tx_tagpack_api::relationsTable,
				'AND hidden = 0 AND deleted = 0 AND uid_local = ' . intval($tagUid)
			    . ($limitToTable ? ' AND tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($limitToTable, tx_tagpack_api::relationsTable) : '')
			);
		}
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($limitToTable) {
				$elements[] = $row['uid_foreign'];
			} else {
				$elements[] = array('uid' => $row['uid_foreign'], 'table' => $row['tablenames']);
			}
 		}
		return $elements;
	}


	/**
	 * Adds a tag to an existing element (a triple of uid, table and pid)
	 * if the tag does not exist yet, it will be created.
	 * 
	 * @param	$tagName		a string containing the name of the tag
	 * @param	$elementUid		the UID of the element that will be used
	 * @param	$elementTable	the table of the element that will be used
	 * @param	$elementPid		the PID of the element that will be used (not in use right now)
	 * @return	void
	 */
	function attachTagToElement($tagName, $elementUid, $elementTable) {
		// create the tag if it doesn't exist yet
		$tagData = tx_tagpack_api::getTagDataByTagName($tagName);
		if (!count($tagData)) {
			$tagUid = tx_tagpack_api::addTag($tagName);
		} else {
			$tagUid = $tagData['uid'];
		}
		$tagUid = intval($tagUid);

        if ($tagUid) {
		    // now we can count the number of records currently related to the tag
		    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				    'count(*) AS relations',
				    tx_tagpack_api::relationsTable,
				    'hidden = 0 AND deleted = 0 AND uid_local = ' . $tagUid
		    );
		    $relations = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		    $relations['relations']++;
		    $GLOBALS['TYPO3_DB']->sql_free_result($res);

		    // and write back the value to the relations field of the tag
		    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(tx_tagpack_api::tagTable, 'uid = ' . $tagUid, $relations);

		    // and insert the new record to the relation table
		    $newRelationRow = array(
			    'uid_local'        => $tagUid,
			    'uid_foreign'      => intval($elementUid),
			    'tstamp'           => time(),
			    'crdate'           => time(),
			    'cruser_id'        => 0,
			    'sys_language_uid' => 0,
			    'deleted'          => 0,
			    'hidden'           => 0,
			    'tablenames'       => $GLOBALS['TYPO3_DB']->quoteStr($elementTable, tx_tagpack_api::relationsTable),
			    'sorting'          => 1
		    );
		    $GLOBALS['TYPO3_DB']->exec_INSERTquery(tx_tagpack_api::relationsTable, $newRelationRow);
        }
	}


	/**
	 * Removes a tag from an element pair (uid, tbale)
	 * 
	 * @param	$tagName		a string containing the name of the tag
	 * @param	$elementUid		the UID of the element that will be used
	 * @param	$elementTable	the table of the element that will be used
	 * @param	$elementPid		the PID of the element that will be used (not in use right now)
	 * @return	void
	 */
	function removeTagFromElement($tagName, $elementUid, $elementTable) {
		$tagData = tx_tagpack_api::getTagDataByTagName($tagName);
		$tagUid = intval($tagData['uid']);

        if ($tagUid) {
		    $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			    tx_tagpack_api::relationsTable,
			    'uid_local = ' . $tagUid . ' AND uid_foreign = ' . intval($elementUid) .
				    ' AND tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($elementTable, tx_tagpack_api::relationsTable)
		    );

		    // now we can count the number of records currently related to the tag
		    $relations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			    'count(*) AS relations',
			    tx_tagpack_api::relationsTable,
			    'hidden = 0 AND deleted = 0 AND uid_local = ' . $tagUid
		    );
		    $relations = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		    $relations['relations']--;
		    $GLOBALS['TYPO3_DB']->sql_free_result($res);

            if ($relations['relations'] == 0) {
                tx_tagpack_api::deleteTag($tagUid, true);
            } else {
		        // and write back the value to the relations field of the tag
		        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(tx_tagpack_api::tagTable, 'uid = ' . $tagUid, $relations);    
    	    }
        }
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/lib/class.tx_tagpack_api.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/lib/class.tx_tagpack_api.php']);
}

?>