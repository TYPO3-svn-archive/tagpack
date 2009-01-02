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
 
 
/**
 * Hook Functions for the 'tagpack' extension.
 *
 * @author JoH asenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_tagpack
 */
class tx_tagpack_tceforms_addtags {
	 
	/**
	 * Changes the TCA setup for the currently rendered table and appends a virtual field "tx_tagpack_tags" to it
	 * This happens before any field gets rendered so the tecforms engine will "think" this virtual field really exists
	 *
	 * @param [type]  $table: The table that is currently rendered
	 * @param [type]  $row: Dataset of the current record
	 * @param [type]  $caller: $this of the parent object
	 * @return [type]  Nothing since the changes happen on a global level
	 */
	function getMainFields_preProcess($table, $row, $caller) {
		 
		global $TCA;
		 
		// first we get the TSconfig for the current page to check if tagging is enabled for any table
		// either it's the page itself or the parent page of the currently rendered record
		if ($table == 'pages') {
			$TSCpid = $row['uid'];
		} else {
			$TSCpid = $row['pid'];
		}
		 
		$TSconfig = t3lib_befunc::getModTSconfig($TSCpid,'tx_tagpack_tags');			
		$allowedTables = $TSconfig['properties']['taggedTables'];
		$getTagsFromPid = $TSconfig['properties']['getTagsFromPid'] ? $TSconfig['properties']['getTagsFromPid'] : '0';
		 
		// if tagging is allowed set the appropriate TCA values
		if (t3lib_div::inList($allowedTables, $table)) {
			 
			// first lets fetch the TCA of the tag table
			t3lib_div::loadTCA('tx_tagpack_tags');
			 
			// now we can append the settings from the relations field of that table
			// to the TCA of the table which is currently rendered
			// only two differences: the allowed table is not "*" but the tags table
			// and the label changes
			$TCA[$table]['columns']['tx_tagpack_tags'] = $TCA['tx_tagpack_tags']['columns']['relations'];
			$TCA[$table]['columns']['tx_tagpack_tags']['exclude'] = 0;
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['allowed'] = 'tx_tagpack_tags';
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['MM_table_where'] = $getTagsFromPid ? 'tx_tagpack_tags.pid='.$getTagsFromPid :
			 '';
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['prepend_tname'] = 0;
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['_VALIGN'] = 'top';
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['ajax_search']['type'] = 'userFunc';
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['ajax_search']['userFunc'] = 'tx_tagpack_ajaxsearch_client->renderAjaxSearch';
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['ajax_search']['params']['client']['startLength'] = 2;
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['searchFields'] = 'name';
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['enabledOnly'] = true;
			$TCA[$table]['columns']['tx_tagpack_tags']['config']['wizards']['ajax_search']['params']['tables']['tx_tagpack_tags']['label'] = '###name###';
			$TCA[$table]['columns']['tx_tagpack_tags']['label'] = 'Tags';
			 
			// lets make sure the new virtual field shows up for every type of this table
			if (count($TCA[$table]['types'])) {
				foreach($TCA[$table]['types'] as $key => $val) {
					if (strpos($TCA[$table]['types'][$key]['showitem'], 'tx_tagpack_tags') === FALSE) {
						$TCA[$table]['types'][$key]['showitem'] .= ',--div--;Tags;;;5-5-5,tx_tagpack_tags,--div--;Groups,';
					}
				}
			}
			 
		}
	}
	 
	/**
	 * Renders the option list for the selectbox of the form
	 * the original form for the newly created field based on $TCA
	 * won't contain any values since it's not a real DB field
	 *
	 * @param [type]  $table: The table currently rendered
	 * @param [type]  $field: The field currenlty rendered
	 * @param [type]  $row: The data of the currently rendered record
	 * @param [type]  $out: The current output of tceforms for this field
	 * @param [type]  $PA: Some Parameters
	 * @param [type]  $caller: $this of the parent object
	 * @return [type]  Nothing since $out is passed by reference
	 */
	function getSingleField_postProcess($table, $field, $row, &$out, $PA, $caller) {
		 

		$table = trim(stripslashes($table));


		// we only want this to happen for our virtual field
		if ($field == 'tx_tagpack_tags' && strpos($row['uid'], 'NEW') === false) {
			 
			// get the related records that are already assigned as tags to the current record
			$mmRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid_local',
				'tx_tagpack_tags_relations_mm',
				'tablenames=\''.mysql_real_escape_string($table).'\'
				AND NOT deleted
				AND NOT hidden
				AND uid_foreign='.intval($row['uid']) );
			// if there are any records, create a list of their uids
			if (count($mmRows)) {
				foreach($mmRows as $mmVal) {
					$uidList .= $uidList ? ','.intval($mmVal['uid_local']) :
					intval($mmVal['uid_local']);
				}
			}
			 
			// fetch the real records just to make sure they are available
			// and because we need their names as a label
			if ($uidList) {
				$itemRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
					'tx_tagpack_tags',
					'uid IN ('.$uidList.')',
					'',
					'name' );
			}
			if (count($itemRows)) {
				foreach($itemRows as $key => $val) {
					 
					//create the option list
					$optionList .= '<option value="'.$val['uid'].'">'.$val['name'].'</option>
						';
					// and create the list of uids for the hidden input field
					$hiddenList .= $hiddenList ? ','.$val['uid'] :
					$val['uid'];
				}
			}
			 
			// add both of the newly created lists to $out
			$out = str_replace('</select>', $optionList.'</select>', $out);
			$out = str_replace('<input type="hidden" name="data['.$table.']['.$row['uid'].'][tx_tagpack_tags]" value=""', '<input type="hidden" name="data['.$table.']['.$row['uid'].'][tx_tagpack_tags]" value="'.$hiddenList.'"', $out);
		}
	}
	 
	/**
	 * If there are values saved to the DB this function makes sure the corresponding tag relations are too
	 * This happens after the datamap of the tagged record has been processed so all the necessary information is already available
	 *
	 * @param [type]  $$incomingFieldArray: POST data of the saved element
	 * @param [type]  $table: The DB table we are currentl working on
	 * @param [type]  $id: The uid of the element we are working on
	 * @param [type]  $caller: $this of the parent object
	 * @return [type]  Nothing since it's only performing some DB operations
	 */
	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $caller) {
		
		

		$id = (strpos($id, 'NEW') === false) ? $id : $caller->substNEWwithIDs[$id];
		 
		// first we need to get the pid for the current record
		$pid = $caller->checkValue_currentRecord['pid'];
		 
		// Now we get the selected tags for the current record
		$selectedUids = t3lib_div::trimexplode(',', $caller->datamap[$table][key($caller->datamap[$table])]['tx_tagpack_tags']);
		
		// if there are any we can create an array and hand it over to the function which is responsible for the DB actions
		if (count($selectedUids)>1) {
			foreach($selectedUids as $selectedUid) {
				// if there are any prefixes, we must strip them first
				$selectedUid = str_replace('tx_tagpack_tags_', '', $selectedUid);
				 
				// if there is a real uid we will use this as an array key and set the value to 1
				// this makes it easier to unset the keys for those uids later on
				// which were already available in the relations table
				if (strpos($selectedUid, 'new_') !== false) {
					$selectedTagUids[str_replace('new_', '', $selectedUid)] = 'new';
				}
				else if (intval($selectedUid)) {
					$selectedTagUids[$selectedUid] = 1;
				}
			}
			// now lets call the DB action
			$this->delete_update_insert_relations($selectedTagUids, $table, $id, $pid, $command, $caller);				 
		}
	}
	 
	/**
	 * After certain actions this function makes sure that the related tags are treated the same way as their parent record(s)
	 *
	 * @param [type]  $command: The action that happened before
	 * @param [type]  $table: The table the action has been applied to
	 * @param [type]  $id: The uid of the parent record
	 * @param [type]  $value: The possible new uid of the new parent record
	 * @param [type]  $caller: $this of the parent object
	 * @return [type]  Nothing, since it's only performing some DB operations
	 */
	function processCmdmap_postProcess($command, $table, $id, $value, $caller) {
	
		$table = trim(stripslashes($table));

		// First let's check which command was executed before
		switch($command) {
			 
			// if the record was localized, the core engine fills the same array as if it was copied
			// so from a tagging point of view these actions are basically the same
			case 'copy':
			case 'localize':
			if (count($caller->copyMappingArray)) {
				 
				// any record that has been copied during the action before
				// will be in the so called copyMappingArray
				// which contains some arrays with the tablename as a key
				// which again hold an array of old_uid / new_uid pairs
				foreach($caller->copyMappingArray as $tablename => $uidArray) {
					 
					// if there are uids that changed lets copy the tags from the old record to the new one too
					if (count($uidArray)) {
						 
						// The copying action has to be applied to any single new Uid
						foreach($uidArray as $oldUid => $newUid) {
							 
							// first we get an array of the related tags for the old uid
							$current_MM_Rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							'*',
								'tx_tagpack_tags_relations_mm',
								'tablenames=\''.$tablename.'\'
								AND NOT hidden
								AND uid_foreign='.intval($oldUid) );
							if (count($current_MM_Rows)) {
								 
								// now we can build the selectedTagUids array just as if somebody had selected the tags in a form
								$selectedTagUids = array();
								foreach($current_MM_Rows as $key => $valueArray) {
									$selectedTagUids[$valueArray['uid_local']] = 1;
								}
								 
								// we need the pid from the new record as well, since it might have changed
								// due to a recursive copy action of a parent page of the new record
								$newRecordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								'*',
									$tablename,
									'uid='.intval($newUid) );
								$newPid = $newRecordData[0]['pid'];
								 
								// now we can execute the DB operations
								$this->delete_update_insert_relations(
								$selectedTagUids,
									$tablename,
									intval($newUid),
									intval($newPid),
									$command,
									$caller );
							};
						};
					};
				};
			}
			break;
			 
			case 'move':
			 
			// if the record was moved the only thing that has changed will be it's pid
			// the rest of the relations will stay as is
			// the new pid can be found in $value so we just have to get the related tags of the current record
			$current_MM_Rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
				'tx_tagpack_tags_relations_mm',
				'tablenames=\''.mysql_real_escape_string($table).'\'
				AND NOT hidden
				AND uid_foreign='.intval($id) );
			 
			// fill the selectedTagUids Array
			if (count($current_MM_Rows)) {
				$selectedTagUids = array();
				foreach($current_MM_Rows as $key => $valueArray) {
					$selectedTagUids[$valueArray['uid_local']] = 1;
				}
			}
			 
			// and execute the Db operations
			$this->delete_update_insert_relations(
			$selectedTagUids,
				$table,
				intval($id),
				intval($value),
				$command,
				$caller );
			break;
			 
			case 'delete':
			 
			// if a record has been deleted we can ignore the pid
			// but we have to hand over an empty array for the selectedTagUids before we execute the DB operations
			$this->delete_update_insert_relations(array(), $table, intval($id), '', $command, $caller);
			break;
			 
			case 'undelete':
			 
			// in case of an undelete the related records are already in the DB table but marked deleted
			// so we just have to get them all
			$current_MM_Rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid_local,pid_foreign',
				'tx_tagpack_tags_relations_mm',
				'tablenames=\''.mysql_real_escape_string($table).'\'
				AND NOT hidden
				AND uid_foreign='.intval($id) );
			 
			// fill the selectedTagUid Array
			if (count($current_MM_Rows)) {
				foreach($current_MM_Rows as $valueArray) {
					$selectedTagUids[$valueArray['uid_local']] = 1;
				}
			}
			 
			// and execute the DB operations with the current pid of the record as "new" pid
			// since there can be only one undeleted record at once
			$this->delete_update_insert_relations(
			$selectedTagUids,
				$table,
				intval($id),
				intval($valueArray['pid_foreign']),
				$command,
				$caller );
			break;
		};
	}
	 
	/**
	 * Database Operations for the Tagging of Records
	 *
	 * @param [type]  $selectedTagUids: Array of Tag uids that are related to the current record
	 * @param [type]  $table: Table the current record belongs to
	 * @param [type]  $id: uid of the current record
	 * @param [type]  $command: certain command that might have been executed before
	 * @param [type]  $level: Counter to make sure that recursive options don't end up in an endless loop
	 * @return [type]  Nothing, since it's only performing some DB operations
	 */
	function delete_update_insert_relations($selectedTagUids, $table, $id, $pid, $command = '', $caller='', $level = 0) {
	
		$table = trim(stripslashes($table));
	
		// level counter is used up to a maximum of 100 which should be the maximum number of recursive copies anyway
		$level++;
		if ($level > 100) {
			return;
		}
		 
		// first we get all the tags that were related to the current record before the upcoming actions
		if ($id === intval($id)) {
			$current_MM_Rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
				'tx_tagpack_tags_relations_mm',
				'tablenames=\''.mysql_real_escape_string($table).'\'
				AND NOT hidden
				AND uid_foreign='.intval($id).'
				AND 1=1' );
		};
		 
		// this one is needed to set the current crdate and tstamp values
		$timeNow = time();
		 
		// if there are any related tags at all we have to check their status and probably
		// delete some of them, if they have been removed from the parent record's tag list
		// or mark them as deleted, if the parent record itself has been deleted
		if (count($current_MM_Rows)) {
			foreach($current_MM_Rows as $key => $valueArray) {
				$where = 'uid='.intval($valueArray['uid_local']);
				 
				// if there are no tags in the taglist anymore we have to make sure they are removed or marked deleted
				if (!$selectedTagUids[$valueArray['uid_local']]) {
					 
					// now we must get the number of relations of this tag and decrement it
					$currentRelations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) AS relations',
						'tx_tagpack_tags_relations_mm',
						'NOT hidden
						AND NOT deleted
						AND uid_local='.$valueArray['uid_local'] );
					$currentRelations[0]['relations']--;
					$relations = $currentRelations[0];
					 
					// the new number of relations has to be saved back again
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tx_tagpack_tags',
						$where,
						$relations );
					 
					// if the command has been 'delete'
					if ($command == 'delete') {
						 
						// the records have to be marked as deleted
						// and the tstamp has to be set
						$current_MM_Rows[$key]['deleted'] = 1;
						$current_MM_Rows[$key]['tstamp'] = $timeNow;
						 
						// if the table the record belonged to was the 'pages' table,
						// we have to make a recursive call of this function for any sub-record as well
						if ($table == 'pages') {
							 
							// first we get all the relations for any child element of the record
							$sub_MM_Rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							'*',
								'tx_tagpack_tags_relations_mm',
								'NOT hidden
								AND pid_foreign='.$id );
							 
							// if there are any we can call this function with the command 'delete'
							if (count($sub_MM_Rows)) {
								foreach($sub_MM_Rows as $subKey => $subValueArray) {
									$this->delete_update_insert_relations(
									array(),
										$subValueArray['tablenames'],
										intval($subValueArray['uid_foreign']),
										intval($id),
										'delete',
										$caller,
										$level );
								}
							}
						}
						// if there was no 'delete' command, this simply means that there are no tags related to this record anymore
					} else {
						// so we just unset the corresponding array key
						unset($current_MM_Rows[$key]);
						 
						// and remove the relation from the table
						$GLOBALS['TYPO3_DB']->exec_DELETEquery(
						'tx_tagpack_tags_relations_mm',
							'uid_local='.intval($valueArray['uid_local']).'
							AND uid_foreign='.intval($valueArray['uid_foreign']).'
							AND tablenames=\''.$valueArray['tablenames'].'\'' );
					}
				} else {
					// if there are tags in the taglist we have to check for the 'undelete' command
					if ($command == 'undelete') {
						 
						// any undelete action means we have to set the deleted flag to 0
						// and fill in the appropriate timestamp and pid
						$current_MM_Rows[$key]['deleted'] = 0;
						$current_MM_Rows[$key]['tstamp'] = $timeNow;
						$current_MM_Rows[$key]['pid_foreign'] = $pid;
						 
						// now we can count the number of records currently related to the tag
						$currentRelations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'count(*) AS relations',
							'tx_tagpack_tags_relations_mm',
							'NOT hidden
							AND NOT deleted
							AND uid_local='.intval($valueArray['uid_local']) );
						$relations = $currentRelations[0];
						 
						// and write back the value to the relations field of the tag
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tx_tagpack_tags',
							$where,
							$relations );
					} else {
						// in any other case we simply have to update all related tags with the valuleArray we have built before
						$where = 'uid_local='.$valueArray['uid_local'].' AND uid_foreign='.$valueArray['uid_foreign'].' AND tablenames=\''.$valueArray['tablenames'].'\'';
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						    'tx_tagpack_tags_relations_mm',
						    $where,
						    $current_MM_Rows[$key] );
				 
					}
				}
				 
				// if the uid is in the array of selected tags we have to remove it now
				// to make sure, that it won't be inserted as a knew relation in the next step
				if ($selectedTagUids[$valueArray['uid_local']]) {
					unset($selectedTagUids[$valueArray['uid_local']]);
				}
			}
		}
		 
		// if there are still uids left in the selectedTagUids array
		// this means we have to create new relations for them
		// because they were not in the array of currently related tags before
		if (count($selectedTagUids)) {
			// for each of them we have to perform the same operations
			foreach($selectedTagUids as $selectedUid => $switch) {
				if ($switch != 'new') {
					$where = 'uid='.$selectedUid;
					 
					// first we have to increase the number of relations for the current tag
					$currentRelations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) AS relations',
						'tx_tagpack_tags_relations_mm',
						'NOT hidden
						AND NOT deleted
						AND uid_local='.intval($selectedUid) );
					$currentRelations[0]['relations']++;
					$relations = $currentRelations[0];
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tx_tagpack_tags',
						$where,
						$relations );
					 
					// now we have to build the value array for the following insert action
					$new_MM_Row = array (
					'uid_local' => $selectedUid,
						'uid_foreign' => intval($id),
						'pid_foreign' => intval($pid),
						'tstamp' => intval($timeNow),
						'crdate' => intval($timeNow),
						'cruser_id' => '0',
						'sys_language_uid' => '0',
						'deleted' => '0',
						'hidden' => '0',
						'tablenames' => mysql_real_escape_string($table),
						'sorting' => '1' );
					// and insert the new record to the relation table
					$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_tagpack_tags_relations_mm',
						$new_MM_Row );
					unset($selectedTagUids[$selectedUid]);
				}
			}
		}
		// if there are still uids left in the selectedTagUidsArray
		// this means we have to create new items and additional relations for them
		// because they didn exist as a tag before
		if (count($selectedTagUids)) {
			 
			$TSconfig = t3lib_befunc::getModTSconfig($pid,'tx_tagpack_tags');
			$getTagsFromPid = $TSconfig['properties']['getTagsFromPid'] ? 'AND pid='.intval($TSconfig['properties']['getTagsFromPid']) :
			 '0';
			 
			foreach($selectedTagUids as $tagName => $switch) {
				$tagName = mysql_real_escape_string(trim(stripslashes($tagName)));
				if ($switch == 'new') {
					 
					// first lets check if the name entered in the searchbox already exists
					$existingTags = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid',
						'tx_tagpack_tags',
						'NOT hidden
						AND NOT deleted
						AND name=\''.$tagName.'\''. $getTagsFromPid );
					 
					// if it exists, we have to create just a new relation
					// because this one seems to be entered as a new item without clicking
					// on the dynamic list but simply hitting ENTER instead
					if (count($existingTags)) {
						 
						$where = 'uid='.intval($existingTags[0]['uid']);
						// now we can count the number of records currently related to the tag
						$currentRelations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'count(*) AS relations',
							'tx_tagpack_tags_relations_mm',
							'NOT hidden
							AND NOT deleted
							AND uid_local='.intval($existingTags[0]['uid']) );
						$relations = $currentRelations[0];
						$relations['relations']++;
						 
						// and write back the value to the relations field of the tag
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'tx_tagpack_tags',
							$where,
							$relations );
						 
						$new_MM_Row = array (
						'uid_local' => $existingTags[0]['uid'],
							'uid_foreign' => intval($id),
							'pid_foreign' => intval($pid),
							'tstamp' => intval($timeNow),
							'crdate' => intval($timeNow),
							'cruser_id' => '0',
							'sys_language_uid' => '0',
							'deleted' => '0',
							'hidden' => '0',
							'tablenames' => mysql_real_escape_string($table),
							'sorting' => '1' );
						// and insert the new record to the relation table
						$GLOBALS['TYPO3_DB']->exec_INSERTquery(
						'tx_tagpack_tags_relations_mm',
							$new_MM_Row );
						unset($selectedTagUids[$tagName]);
					}
					 
					// if it doesn't, we have to create a new tag
					else
					{
						// now we have to build the value array for the following insert action
						$new_tag_Row = array (
						'name' => $tagName,
							'tstamp' => intval($timeNow),
							'crdate' => intval($timeNow),
							'cruser_id' => '0',
							'pid' => $TSconfig['properties']['getTagsFromPid'] ? intval($TSconfig['properties']['getTagsFromPid']) : '0',
							'sys_language_uid' => '0',
							'deleted' => '0',
							'hidden' => '0',
							'relations' => '1' );
						// and insert the new record to the relation table
						$GLOBALS['TYPO3_DB']->exec_INSERTquery(
						'tx_tagpack_tags',
							$new_tag_Row );
						// now we have to build the value array for the following insert action
						 
						$new_MM_Row = array (
						'uid_local' => $GLOBALS['TYPO3_DB']->sql_insert_id(),
							'uid_foreign' => intval($id),
							'pid_foreign' => intval($pid),
							'tstamp' => intval($timeNow),
							'crdate' => intval($timeNow),
							'cruser_id' => '0',
							'sys_language_uid' => '0',
							'deleted' => '0',
							'hidden' => '0',
							'tablenames' => mysql_real_escape_string($table),
							'sorting' => '1' );
						// and insert the new record to the relation table
						$GLOBALS['TYPO3_DB']->exec_INSERTquery(
						'tx_tagpack_tags_relations_mm',
							$new_MM_Row );
						unset($selectedTagUids[$tagName]);
					}
				}
				 
			}
		}
	}
}
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/class.tx_tagpack_tceforms_addtags.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/class.tx_tagpack_tceforms_addtags.php']);
}
 
 
?>