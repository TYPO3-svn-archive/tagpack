<?php
/***************************************************************
 *	Copyright notice
 *
 *	(c) 2008-2009 Jo Hasenau, Benjamin Mack
 *	All rights reserved
 *
 *	This script is part of the TYPO3 project. The TYPO3 project is
 *	free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	The GNU General Public License can be found at
 *	http://www.gnu.org/copyleft/gpl.html.
 *
 *	This script is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	This copyright notice MUST APPEAR in all copies of the script!
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
	const tagTable		 = 'tx_tagpack_tags';
	/* this is a constant that matches the table where the tag relations are stored */
	const relationsTable = 'tx_tagpack_tags_relations_mm';


	/**
	 * Fetches the storage PID from the configuration, based on the storage PID
	 * that is set through the pages TSconfig
	 * It uses a one-time mechanism to only fetch the storage PID once and then
	 * stores it as a static variable
	 *
	 * @param	int	$pid	the uid of the page where the tsconfig is stored, needed for the backend
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
	 * Fetches the Descriptor Mode from the configuration, based on the Descriptor Mode
	 * that is set through the pages TSconfig
	 * It uses a one-time mechanism to only fetch the Descriptor Mode once and then
	 * stores it as a static variable
	 *
	 * @param	int		$pid	the uid of the page where the tsconfig is stored, needed for the backend
	 * @return	boolean			the Descriptor Mode to be used
	 */
	function getDescriptorMode($pid = 0) {
		static $descriptorMode;
		if (!$descriptorMode) {
			if (is_object($GLOBALS['TSFE'])) {
				// get the storage PID in the frontend
				$GLOBALS['TSFE']->getPagesTSconfig();
				$$descriptorMode = $GLOBALS['TSFE']->pagesTSconfig['tx_tagpack_tags.']['enableDescriptorMode'] ? TRUE : FALSE;
			} else {
				// get storage PID in the backend
				$TSconfig = t3lib_BEfunc::getPagesTSconfig($pid);
				$descriptorMode = $TSconfig['tx_tagpack_tags.']['enableDescriptorMode'] ? TRUE : FALSE;
			}
		}
		return $descriptorMode;
	}


	/**
	 * Fetches all pages that are containing at least one tag
	 *
	 * @return	array		the complete record-set of all container pages
	 */
	function getTagContainer() {
		$tagContainer = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'pages.*',
			'pages,'.tx_tagpack_api::tagTable.' AS tt',
			'pages.hidden = 0 AND pages.deleted = 0 '
			. 'AND pages.uid=tt.pid',
			'pages.uid',
			'pages.uid'
		);
		return $tagContainer;
	}


	/**
	 * Checks whether a tag already exists by creating a lookup on the tag uid
	 * and then returns.
	 *
	 * @param	int	$tagUid		the uid of the tag
	 * @return	bool			whether it exists or not
	 */
	function tagExists($tagUid) {
		$existingTag = tx_tagpack_api::getTagDataById($tagUid);
		return (count($existingTag) ? true : false);
	}


	/**
	 * Checks whether a tag already exists by creating a lookup on the tag name
	 * and then returns.
	 *
	 * @param	string	$tagName	the name of the tag
	 * @return	bool			whether it exists or not
	 */
	function tagNameExists($tagName) {
		$existingTag = tx_tagpack_api::getTagDataByTagName($tagName);
		return (count($existingTag) ? true : false);
	}



	/**
	 * Adds a new tag to the DB without any relationships yet.
	 *
	 * @param	string	$tagName	a string containing the name of the tag
	 * @param	int	$pid 		the uid of the current page
	 * @param	bool	$isStoragePID 	Set this to TRUE if thae page in $pid is the storage page as well
	 * @param	string	$elementTable	the name of the table containing the record that is currently tagged
	 * @return	int			the uid of the newly added tag, or zero if the tagName was empty
	 */
	function addTag($tagName,$pid=0,$isStoragePID=FALSE,$elementTable='') {
		$tagName = trim($tagName);
		if (!empty($tagName) && !tx_tagpack_api::tagNameExists($tagName)) {
			$storagePID = $isStoragePID ? $pid : tx_tagpack_api::getTagStoragePID($pid);
			$descriptorMode = tx_tagpack_api::getDescriptorMode($pid);

			// now we have to build the value array for the following insert action
			$newTagRow = array(
				'name'			   => $tagName,
				'tstamp'		   => time(),
				'crdate'		   => time(),
				'cruser_id'		   => 0,
				'pid'			   => $storagePID,
				'sys_language_uid' => 0,
				'deleted'		   => 0,
				'hidden'		   => 0,
				'relations'		   => 0
			);
			if($descriptorMode && $elementTable=='tx_tagpack_tags') {
			    $newTagRow['tagtype'] = 1;
			} else {
			    $newTagRow['tagtype'] = 0;
			}
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(tx_tagpack_api::tagTable, $newTagRow);
			return $GLOBALS['TYPO3_DB']->sql_insert_id();
		} else {
			return 0;
		}
	}


	/**
	 * Removes a tag from the DB
	 *
	 * @param	int	$tagUid			the unique identifier of the tag
	 * @param	bool	$removeRelations	a flag whether to remove the relations as well
	 * @param	int	$replacementId		an id to fill into relations as a replacement if relations are not removed
	 * @return	void
	 */
	function removeTag($tagUid, $removeRelations = true, $replacementId = 0) {
		$tagUid = intval($tagUid);
		if ($tagUid && tx_tagpack_api::tagExists($tagUid)) {
			if ($removeRelations) {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery(
					tx_tagpack_api::relationsTable,
					'uid_local = ' . $tagUid
				);
			} else if($replacementId) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					tx_tagpack_api::relationsTable,
					'uid_local = ' . $tagUid,
					array('uid_local' => $replacementId)
				);
				$attachedElements = count(tx_tagpack_api::getAttachedElementsForTagId($replacementId));
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					tx_tagpack_api::tagTable,
					'uid = ' . $replacementId,
					array('relations' => $attachedElements)
				);
			}
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			    tx_tagpack_api::tagTable,
			    'uid = ' . $tagUid
			);
		}
	}


	/**
	 * Sets the deleted flag for a tag (used only when a tagged element gets deleted itself)
	 *
	 * @param	int	$tagUid			the unique identifier of the tag
	 * @param	bool	$deleteRelations	a flag whether set the relations to deleted as well
	 * @return	void
	 */
	function deleteTag($tagUid, $deleteRelations = true) {
		$tagUid = intval($tagUid);
		if ($tagUid && tx_tagpack_api::tagExists($tagUid)) {
			if ($deleteRelations) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				    tx_tagpack_api::tagTable,
				    'uid_local = ' . $tagUid,
				    array('deleted' => 1)
				);
			}
			$storagePID = tx_tagpack_api::getTagStoragePID();
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			    tx_tagpack_api::tagTable,
			    'uid = ' . $tagUid . ' AND pid = ' . $storagePID,
			    array('deleted' => 1)
			);
		}
	}


	/**
	 * Returns an array full of all information about the tag
	 * found by the tagName
	 * Should be used while attaching tags to elements only(!)
	 * in any other case tags should be identified by their uid,
	 * which will always be known if a tag already has been attached to an element
	 *
	 * @param	string	$tagName	a string containing the name of the tag
	 * @param	int	$storagePID	the uid of the container page 
	 * @param	int	$limit		the MySQL limit (don't use the CSV syntax here!)
	 * @param	bool	$showHidden	a flag whether hidden entries should be shown as well
	 * @param	bool	$fromDate	a flag whether the starting date of a certain time frame should be used or not
	 * @param	bool	$toDate		a flag whether the ending date of a certain time frame should be used or not
	 * @param	int	$pid		the uid of the current page
	 * @return	array			the result row from the DB or an empty array if nothing was found
	 */
	function getTagDataByTagName($tagName, $storagePID='', $limit=1, $showHidden=FALSE, $fromDate=FALSE, $toDate=FALSE, $pid = 0) {
		$tagName = trim($tagName);
		$limitArray = t3lib_div::trimExplode(',',$limit);
		if($limitArray[1]) {
		    $limit = intval($limitArray[0]).','.intval($limitArray[1]);
		} else {
		    $limit = intval($limitArray[0]);
		}
		if($fromDate || $toDate) {
		    $fromDate = $fromDate ? strtotime($fromDate) : 0;
		    $toDate = $toDate ? strtotime($toDate)+(60*60*24)-1 : time();
		    $timeFrame = ' AND crdate > '.intval($fromDate).' AND crdate < '.intval($toDate);
		}
		if (!empty($tagName)) {
			$storagePID = $storagePID ? $storagePID : tx_tagpack_api::getTagStoragePID(intval($pid));
			$tagData = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				tx_tagpack_api::tagTable,
				($showHidden ? '' : 'hidden = 0 AND ')
				. 'deleted = 0 AND name LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tagName, $this->tagTable)
				. ($storagePID ?  ' AND pid IN (' . $storagePID .')' : '')
				. $timeFrame,
				'',
				'name ASC',
				$limit
			);
		}
		return $tagData;
	}


	/**
	 * Returns an array full of all information about the tag
	 * found by the tag UID
	 *
	 * @param	int	$tagUid		the ID in the DB indentifying the tag
	 * @return	array			the result row from the DB or an empty array if nothing was found
	 */
	function getTagDataById($tagUid) {
		$tagUid	 = intval($tagUid);
		if ($tagUid > 0) {
			$tagDataQuery = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				tx_tagpack_api::tagTable,
				'hidden = 0 AND deleted = 0 AND uid = ' . $tagUid,
				'',
				'',
				1 // limit to one result
			);
			if(!$GLOBALS['TYPO3_DB']->sql_error()) {
			    $tagData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tagDataQuery);
			    $GLOBALS['TYPO3_DB']->sql_free_result($tagDataQuery);
			}
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
	 * @param	int	$elementUid	the UID of the element
	 * @param	string	$elementTable	the table of the element
	 * @param	bool	$uidOnly	a flag whether the returning array should contain a full dataset or just the uid
	 * @param	bool	$showHidden	a flag whether hidden records should be shown as well
	 * @param	bool	$showDeleted	a flag whether deleted records should be shown as well
	 * @return	array			an array containing all tag UIDs
	 */
	function getAttachedTagIdsForElement($elementUid, $elementTable, $uidOnly = FALSE, $showHidden = FALSE, $showDeleted = FALSE) {
		$tagUidQuery = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			tx_tagpack_api::relationsTable,
			'tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($elementTable, tx_tagpack_api::relationsTable)
			 . ($showHidden ? '' : ' AND hidden = 0') . ($showDeleted ? '' : ' AND deleted = 0') . ' AND uid_foreign = ' . intval($elementUid)
		);
		if(!$GLOBALS['TYPO3_DB']->sql_error()) {
		    while ($tagUid = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tagUidQuery)) {
			if($uidOnly === TRUE) {
			    $tagUids[] = $tagUid['uid_local'];
			} else {
			    $tagUids[] = $tagUid;
			}
		    }
		    $GLOBALS['TYPO3_DB']->sql_free_result($tagUidQuery);
		}
		return $tagUids;
	}

	/**
	 * Returns an array with all the tags (and their data) that are attached
	 * to any element (UID / table pair) found in the DB
	 * The two parameters are basically something like
	 * $elementUid = 12, $elementTable = 'tt_news'. This function then returns all
	 * tags in form of an array that are attached to this element
	 *
	 * @param	int	$elementUid	the UID of the element
	 * @param	string	$elementTable	the table of the element
	 * @return	array			a multi-dimensional array containing all tagdata infos
	 */
	function getAttachedTagsForElement($elementUid, $elementTable) {
		$tags = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tg.*',
			tx_tagpack_api::relationsTable . ' AS mm, ' . tx_tagpack_api::tagTable . ' AS tg',
			'mm.uid_local = tg.uid '
			. ' AND mm.tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($elementTable, tx_tagpack_api::relationsTable)
			. ' AND mm.uid_foreign = ' . intval($elementUid),
			'mm.uid_local',
			'tg.name ASC'
		);
		return $tags;
	}


	/**
	 * Returns an array full of element pairs (UID / table) that are attached
	 * to a certain tagUid
	 *
	 * @param	int	$tagUid		an integer that uniquely identifies the tag in the DB table
	 * @param	string	$limitToTable	the name of a table if tags are allowed for more than one of them
	 * @return	array			an array containing pairs of "uid" and "table"
	 */
	function getAttachedElementsForTagId($tagUid, $limitToTable = '') {
		$elements = array();
		if ($tagUid > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid_foreign, tablenames',
				tx_tagpack_api::relationsTable,
				'AND uid_local = ' . intval($tagUid)
				. ($limitToTable ? ' AND tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($limitToTable, tx_tagpack_api::relationsTable) : '')
			);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
				if ($limitToTable) {
					$elements[] = $row[0];
				} else {
					$elements[] = array('uid' => $row[0], 'table' => $row[1]);
				}
			}
		}
		return $elements;
	}


	/**
	 * Returns an array full of tags that are attached to
	 * the same element(s) as a list of known tags
	 *
	 * @param	CSV	$tagUidList	a list of integers that uniquely identifie a tag in the DB table
	 * @param	int	$containerId	an integer that uniquely identifies the container for the related tags
	 * @param	int	$limit		the MySQL limit (don't use the CSV syntax here!)
	 * @return	array			an array with the tag data
	 */
	function getRelatedTagsForTags($tagUidList,$containerId=0,$limit=10) {
		$elements = array();
		$limitArray = t3lib_div::trimExplode(',',$limit);
		if($limitArray[1]) {
		    $limit = intval($limitArray[0]).','.intval($limitArray[1]);
		} else {
		    $limit = intval($limitArray[0]);
		}
		if ($tagUidList != '') {
			$elements = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'tags.*',
				tx_tagpack_api::relationsTable.' AS t1 JOIN '.
				tx_tagpack_api::relationsTable.' AS t2 ON (
				    t1.uid_foreign = t2.uid_foreign AND
				    t1.tablenames = t2.tablenames AND
				    t2.hidden = 0 AND t2.deleted = 0) JOIN '.
				tx_tagpack_api::tagTable.' AS tags ON (
				    t2.uid_local = tags.uid AND
				    tags.pid = '.intval($containerId).' AND
				    tags.hidden = 0 AND
				    tags.deleted = 0 AND
				    tags.uid NOT IN ('. $GLOBALS['TYPO3_DB']->quoteStr($tagUidList, tx_tagpack_api::relationsTable) .')
				)',
				't1.hidden = 0 AND
				 t1.deleted = 0 AND
				 t1.uid_local IN ('. $GLOBALS['TYPO3_DB']->quoteStr($tagUidList, tx_tagpack_api::relationsTable) .')',
				 'tags.uid',
				 'tags.relations DESC',
				 $limit
			);
		}
		return $elements;
	}


	/**
	 * Adds a tag to an existing element (a triple of uid, table and pid)
	 * if the tag does not exist yet, it will be created.
	 *
	 * @param	string	$tagName	a string containing the name of the tag
	 * @param	int	$elementUid	the UID of the element that will be used
	 * @param	string	$elementTable	the table of the element that will be used
	 * @param	int	$elementPid	the PID of the element that will be used (not in use right now)
	 * @param	int	$pid		the uid of the current page
	 * @param	int	$hidden		a flag whether the tagged element is currently hidden or not
	 * @return	void
	 */
	function attachTagToElement($tagUid=0, $tagName='', $elementUid, $elementTable, $pid, $hidden = FALSE) {
		// create the tag if it doesn't exist yet
		if($tagName && !$tagUid) {
		    $tagData = tx_tagpack_api::getTagDataByTagName($tagName, '', 1, FALSE, FALSE, FALSE, $pid);
		}
		if (!count($tagData) && !$tagUid) {
			$tagUid = tx_tagpack_api::addTag($tagName,$pid,FALSE,$elementTable);
		} else if (!$tagUid) {
			$tagUid = $tagData[0]['uid'];
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
				'uid_local'		   => $tagUid,
				'uid_foreign'	   => intval($elementUid),
				'tstamp'		   => time(),
				'crdate'		   => time(),
				'cruser_id'		   => 0,
				'sys_language_uid' => 0,
				'deleted'		   => 0,
				'hidden'		   => $hidden ? 1 : 0,
				'tablenames'	   => $GLOBALS['TYPO3_DB']->quoteStr($elementTable, tx_tagpack_api::relationsTable),
				'sorting'		   => 1
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(tx_tagpack_api::relationsTable, $newRelationRow);
		}
	}


	/**
	 * Removes a tag from an element pair (uid, table)
	 *
	 * @param	int	$tagUid		an integer containing the identifier of the tag
	 * @param	int	$elementUid	the UID of the element that will be used
	 * @param	string	$elementTable	the table of the element that will be used
	 * @param	int	$elementPid	the PID of the element that will be used (not in use right now)
	 * @return	void
	 */
	function removeTagFromElement($tagUid, $elementUid, $elementTable) {
		$tagUid = intval($tagUid);
		if ($tagUid && tx_tagpack_api::tagExists($tagUid)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				tx_tagpack_api::relationsTable,
				'uid_local = ' . $tagUid . ' AND uid_foreign = ' . intval($elementUid)
				. ' AND tablenames = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($elementTable, tx_tagpack_api::relationsTable)
			);

			// now we can count the number of records currently related to the tag
			$relations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'count(*) AS relations',
				tx_tagpack_api::relationsTable,
				'hidden = 0 AND deleted = 0 AND uid_local = ' . $tagUid
			);

			// and write back the value to the relations field of the tag
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(tx_tagpack_api::tagTable, 'uid = ' . $tagUid, $relations[0]);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/lib/class.tx_tagpack_api.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/lib/class.tx_tagpack_api.php']);
}

?>