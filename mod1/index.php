<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2009 JoH asenau <jh@eqony.com>
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
	 
	 
	// DEFAULT initialization of a module [BEGIN]
	unset($MCONF);
	require_once('conf.php');
	require_once($BACK_PATH.'init.php');
	require_once($BACK_PATH.'template.php');
	 
		$LANG->includeLLFile('EXT:tagpack/mod1/locallang.xml');		
		
	require_once(PATH_t3lib.'class.t3lib_scbase.php');
	$BE_USER->modAccess($MCONF, 1); // This checks permissions and exits if the users has no permission for entry.
	
	include_once(t3lib_extMgm::extPath('tagpack') . 'lib/class.tx_tagpack_api.php');

	// DEFAULT initialization of a module [END]
	 
	 
	 
	/**
	* Module 'Site Generator' for the 'tagpack' extension.
	*
	* @author JoH asenau <jh@eqony.com>
	* @package TYPO3
	* @subpackage tx_tagpack
	*/
	class tx_tagpack_module1 extends t3lib_SCbase {
		var $pageinfo;
		 
		/**
		* Initializes the Module
		*
		* @return void
		*/
		function init() {
			global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
			 
			parent::init();
			 
			/*
			if (t3lib_div::_GP('clear_all_cache')) {
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
			}
			*/
		}
		 
		/**
		* Adds items to the->MOD_MENU array. Used for the function menu selector.
		*
		* @return void
		*/
		function menuConfig() {
			global $LANG;
			$this->MOD_MENU = Array (
			'function' => Array (
			'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2'),
				'3' => $LANG->getLL('function3'),
				)
			);
			parent::menuConfig();
		}
		 
		/**
		* Main function of the module. Write the content to $this->content
		* If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
		*
		* @return [type]  ...
		*/
		function main() {
			global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
			 
			// Access check!
			// The page will show only if there is a valid page and if this page may be viewed by the user
			$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
			$access = is_array($this->pageinfo) ? 1 : 0;
			 
			if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id)) {
				
				$this->tpm = t3lib_div::_GP('tpm');
				$this->tpm = $this->tpm ? $this->tpm : $BE_USER->getModuleData('user_txtagpackM1/tpm');
				
				$BE_USER->pushModuleData('user_txtagpackM1/tpm',$this->tpm);
				$this->tagContainer = tx_tagpack_api::getTagContainer();
				
				if($this->tpm['merge_now']['submit'] && $this->tpm['merge_now']['new_name'] && count($this->tpm['to_be_merged'])) {
				    $this->mergeNow = $this->mergeTags($this->tpm['to_be_merged'],$this->tpm['merge_now']['new_id'],$this->tpm['merge_now']['new_name'],$this->tpm['container_page'][3][0]);
				    unset($this->tpm['to_be_merged']);
				    unset($this->tpm['merge_now']);
				}
				
			 
				// Draw the header.
				$this->doc = t3lib_div::makeInstance('bigDoc');
				$this->doc->backPath = $BACK_PATH;
				$this->doc->JScode .= '
				<link rel="stylesheet" type="text/css" href="css/tagmanager.css" />';
				$this->doc->JScode .= '
				<script type="text/javascript" src="/typo3/contrib/prototype/prototype.js"><!--PROTOTYPE--></script>';
				$this->doc->JScode .= '
				<script type="text/javascript" src="/typo3/contrib/scriptaculous/scriptaculous.js"><!--SCRIPTACULOUS--></script>';
				$this->doc->JScode .= '
				<script type="text/javascript" src="js/tabMenuFunctions.js"><!--TABMENU--></script>';
				$this->doc->form = '<form id="tagmanager_form" action="index.php" method="POST">';
				 
				$this->content .= $this->doc->startPage($LANG->getLL('title'));

				if ($this->tagpack['save']) {
					//Save Pagetree
					$this->savePageTree();
				} else {
					// Render content:
					$this->moduleContentDynTabs();
				}
				 
				 
				// ShortCut
				if ($BE_USER->mayMakeShortcut()) {
					$this->content .= '<div id="shortcuticon">'.$this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name'])).'</div>';
				}
				 
			} else {
				// If no access or if ID == zero
				 
				$this->doc = t3lib_div::makeInstance('mediumDoc');
				$this->doc->backPath = $BACK_PATH;
				 
				$this->content .= $this->doc->startPage($LANG->getLL('title'));
				$this->content .= $this->doc->header($LANG->getLL('title'));
				$this->content .= $this->doc->spacer(5);
				$this->content .= $this->doc->spacer(10);
			}
		}
		 
		/**
		* [Describe function...]
		*
		* @return [type]  ...
		*/
		function moduleContentDynTabs() {
		
		    $this->content .= '
		    	<script type="text/javascript">window.tx_tagpack_ajaxsearch_server = "../class.tx_tagpack_ajaxsearch_server.php";</script>
			<script type="text/javascript" src="../res/ajaxgroupsearch.js"><!--AJAXGROUPSEARCH--></script>';
		    $this->content .= '
			<link rel="stylesheet" type="text/css" href="../res/ajaxgroupsearch.css">
		    ';
		    $this->content .= '<ul id="tabmenu">';
		    $this->content .= '<li id="tabitem1" class="'.($this->tpm['active_tab'] > 1 ? 'redbutton' : 'greenbutton').'"><a href="#" onclick="triggerTab(this,1);tpmIframeHide();return false;">'.$GLOBALS['LANG']->getLL('TabLabel1').'</a></li>';
		    $this->content .= '<li id="tabitem2" class="'.($this->tpm['active_tab'] == 2 ? 'greenbutton' : 'redbutton').'"><a href="#" onclick="triggerTab(this,2);tpmIframeHide();return false;">'.$GLOBALS['LANG']->getLL('TabLabel2').'</a></li>';
		    $this->content .= '<li id="tabitem3" class="'.($this->tpm['active_tab'] == 3 ? 'greenbutton' : 'redbutton').'"><a href="#" onclick="triggerTab(this,3);tpmIframeHide();return false;">'.$GLOBALS['LANG']->getLL('TabLabel3').'</a></li>';
		    $this->content .= '<li id="tabitem3" class="'.($this->tpm['active_tab'] == 4 ? 'greenbutton' : 'redbutton').'"><a href="#" onclick="triggerTab(this,4);tpmIframeHide();return false;">'.$GLOBALS['LANG']->getLL('TabLabel4').'</a></li>';
		    $this->content .= '</ul>
		    <input id="tpm_active_tab" type="hidden" name="tpm[active_tab]" value="'.($this->tpm['active_tab'] ? $this->tpm['active_tab'] : 1).'" />
		    <div id="tabcontent1" class="'.($this->tpm['active_tab'] > 1 ? 'tabcontent_off' : 'tabcontent_on').'">
			'.$this->moduleContentTab1().'
		    </div>
		    <div id="tabcontent2" class="'.($this->tpm['active_tab'] == 2 ? 'tabcontent_on' : 'tabcontent_off').'">
			'.$this->moduleContentTab2().'
		    </div>
		    <div id="tabcontent3" class="'.($this->tpm['active_tab'] == 3 ? 'tabcontent_on' : 'tabcontent_off').'">
			'.$this->moduleContentTab3().'
		    </div>
		    <div id="tabcontent4" class="'.($this->tpm['active_tab'] == 4 ? 'tabcontent_on' : 'tabcontent_off').'">
			'.$this->moduleContentTab4().'
		    </div>
		    <div id="iframe_container" style="display:none;">
			<iframe id="inner_frame" name="inner_frame" src="/typo3/alt_doc.php" onblur="tpmIframeHide();return false;"><!--//IFRAME FOR TCE-FORM//--></iframe>
		    </div>';
		    
		}
		 
		/**
		* Prints out the module HTML
		*
		* @return void
		*/
		function printContent() {
			 
			$this->content .= $this->doc->endPage();
			echo $this->content;
		}
		 
		/**
		* Generates the content for tab 1
		*
		* @return void
		*/
		function moduleContentTab1() {
			$tab1Content .= '<div class="tabscreenback1"><!--BACKGROUND--></div><div class="tabcontent tabscreen_left">'.$this->doc->header($GLOBALS['LANG']->getLL('Tab1_Left'));
			$tab1Content .= $this->makeDefaultFormFields(1);
			$blockedChecked = $this->tpm['approve']['blocked'] || (!$this->tpm['approve']['blocked'] && !$this->tpm['approve']['approved']) ? ' checked="checked"' : '';
			$approvedChecked = $this->tpm['approve']['approved'] || (!$this->tpm['approve']['blocked'] && !$this->tpm['approve']['approved']) ? ' checked="checked"' : '';
			$tab1Content .= '
			    <div id="approvedfilter">
				'.$GLOBALS['LANG']->getLL('find').' <input type="checkbox" class="tpm_checkbox" id="tpm_approve_blocked" name="tpm[approve][blocked]" value="1"'.$blockedChecked.' />
				<label for="tpm_approve_blocked"> '.$GLOBALS['LANG']->getLL('blocked').' </label>
				<input type="checkbox" class="tpm_checkbox" id="tpm_approve_approved" name="tpm[approve][approved]" value="1"'.$approvedChecked.' />
				<label for="tpm_approve_approved"> '.$GLOBALS['LANG']->getLL('approved').' </label>
				'.$GLOBALS['LANG']->getLL('tags').'
			    </div>
			';
			$tab1Content .= $this->makeLimitField(1);
			$tab1Content .= '<div class="submitbox"><input type="submit" class="submit" value="'.$GLOBALS['LANG']->getLL('find').'" /></div>';
			$tab1Content .= '</div>';
			$tab1Content .= '<div class="tabscreenback2"><!--BACKGROUND--></div><div class="tabcontent tabscreen_right">'.$this->doc->header($GLOBALS['LANG']->getLL('Tab1_Right'));
			$tab1Content .= $this->makeResultList(1,TRUE);
			$tab1Content .= '</div>';
			return $tab1Content;
		}
		 
		 
		/**
		* Generates the content for tab 2
		*
		* @return void
		*/
		function moduleContentTab2() {
			$tab2Content .= '<div class="tabscreenback1"><!--BACKGROUND--></div><div class="tabcontent tabscreen_left">'.$this->doc->header($GLOBALS['LANG']->getLL('Tab2_Left'));
			$tab2Content .= $this->makeDefaultFormFields(2);
			$tab2Content .= $this->makeLimitField(2);
			$tab2Content .= '<div class="submitbox"><input type="submit" class="submit" value="'.$GLOBALS['LANG']->getLL('find').'" /></div>';
			$tab2Content .= '</div>';
			$tab2Content .= '<div class="tabscreenback2"><!--BACKGROUND--></div><div class="tabcontent tabscreen_right">'.$this->doc->header($GLOBALS['LANG']->getLL('Tab2_Right'));
			$tab2Content .= $this->makeResultList(2);
			$tab2Content .= '</div>';
			return $tab2Content;
		}
		 
		 
		/**
		* Generates the content for tab 3
		*
		* @return void
		*/
		function moduleContentTab3() {
			$tab3Content .= '<div class="tabscreenback1"><!--BACKGROUND--></div><div class="tabcontent tabscreen_left">'.$this->doc->header($GLOBALS['LANG']->getLL('Tab3_Left'));
			$tab3Content .= $this->makeDefaultFormFields(3,FALSE);
			$tab3Content .= $this->makeContextSearchFields(3);
			$tab3Content .= $this->makeLimitField(3);
			$tab3Content .= '<div class="submitbox"><input type="submit" class="submit" value="'.$GLOBALS['LANG']->getLL('find').'" /></div>';
			$tab3Content .= '</div>';
			$tab3Content .= '<div class="tabscreenback2"><!--BACKGROUND--></div><div class="tabcontent tabscreen_right">'.$this->doc->header($GLOBALS['LANG']->getLL('Tab3_Right'));
			$tab3Content .= $this->mergeNow;
			$tab3Content .= $this->makeMergeForm(3);
			$tab3Content .= $this->makeResultList(3);
			$tab3Content .= $this->makeRelatedList(3,$this->firstLevelResults[3],$this->tpm['container_page'][3][0],$this->tpm['taglimit'][3]);
			$tab3Content .= '</div>';
			return $tab3Content;
		}
		 
		 
		/**
		* Generates the content for tab 4
		*
		* @return void
		*/
		function moduleContentTab4() {
			$tab4Content .= '<div class="tabscreenback1"><!--BACKGROUND--></div><div class="tabcontent tabscreen_left">'.$this->doc->header('Coming soon! '.$GLOBALS['LANG']->getLL('Tab4_Left'));
			/*$tab4Content .= $this->makeDefaultFormFields(4);
			$tab4Content .= $this->makeLimitField(4);*/
			$tab4Content .= '<div class="submitbox"><input type="submit" class="submit" value="'.$GLOBALS['LANG']->getLL('find').'" /></div>';
			$tab4Content .= '</div>';
			$tab4Content .= '<div class="tabscreenback2"><!--BACKGROUND--></div><div class="tabcontent tabscreen_right">'.$this->doc->header('Coming soon! '.$GLOBALS['LANG']->getLL('Tab4_Right'));
			/*$tab4Content .= $this->makeResultList(4);*/
			$tab4Content .= '</div>';
			return $tab4Content;
		}
		 
		function makeDefaultFormFields($tab,$multiple=TRUE) {
			$content .= $this->makeContainerSelector($tab,$multiple);
			if(count($this->tpm['container_page'][$tab])) {
				$content .= '<p>'.$GLOBALS['LANG']->getLL('within_containers').':</p>';
				$content .= $this->makeSearchbox($tab);
			}
			return $content;
		}
		 
		function makeContainerSelector($tab,$multiple=TRUE) {
			if(count($this->tpm['container_page'][$tab])) {
				foreach($this->tpm['container_page'][$tab] as $value) {
					$selectedOptions[$value]=1;
				}
			}
			if(count($this->tagContainer)) {
				$i=0;
				foreach($this->tagContainer as $pageData) {
					$this->availableContainers[$pageData['uid']]=$pageData;
					$selected = $selectedOptions[$pageData['uid']] ? ' selected="selected"' : '';
					$i++;
					$optionList .= '<option value="'.$pageData['uid'].'"'.$selected.'>['.$pageData['uid'].'] '.substr($pageData['title'],0,16).(strlen($pageData['title'])>16 ? '...' : '').'</option>';
				}
				$multiple = $multiple ? ' multiple="multiple" size="5"' : '';
				$selectBox = '<label for="tpm_container_page_'.$tab.'">'.$GLOBALS['LANG']->getLL('Tab'.$tab.'_Label1').'</label>
				<select'.$multiple.' id="tpm_container_page_'.$tab.'" class="container_page" name="tpm[container_page]['.$tab.'][]" ondblclick="submit();">'.$optionList.'</select>';
			}
			return $selectBox;
		} 


		function makeSearchbox($tab) {
			$searchBox = '<label for="tpm_tagname_'.$tab.'">'.$GLOBALS['LANG']->getLL('Tab'.$tab.'_Label2').'</label>
				<input class="search_tagname" id="tpm_tagname_'.$tab.'" type="text" name="tpm[tagname]['.$tab.']" value="'.$this->tpm['tagname'][$tab].'"/>';
			$searchBox .= '<div class="floatbox"><label for="tpm_tagdate_from_'.$tab.'">'.$GLOBALS['LANG']->getLL('Between').' </label>
				<input onblur="checkDate(this);return false;" class="search_tagfrom" id="tpm_tagdate_from_'.$tab.'" type="text" name="tpm[tagdatefrom]['.$tab.']" value="'.($this->tpm['tagdatefrom'][$tab] ? $this->tpm['tagdatefrom'][$tab] : date('Y-m-d',0)).'"/></div>';
			$searchBox .= '<div class="floatbox"><label for="tpm_tagdate_to_'.$tab.'"> '.$GLOBALS['LANG']->getLL('and').' </label>
				<input onblur="checkDate(this);return false;" class="search_tagto" id="tpm_tagdate_to_'.$tab.'" type="text" name="tpm[tagdateto]['.$tab.']" value="'.($this->tpm['tagdateto'][$tab] ? $this->tpm['tagdateto'][$tab] : date('Y-m-d',time())).'"/></div><br class="clearer" />';
			return $searchBox;
		} 
	
		function makeLimitField($tab) {
			$searchBox .= '<div class="floatbox"><p>'.$GLOBALS['LANG']->getLL('limit1').' <input class="search_taglimit" type="text" name="tpm[taglimit]['.$tab.']" value="'.($this->tpm['taglimit'][$tab] ? $this->tpm['taglimit'][$tab] : 50).'"/> ';
			$searchBox .= ($tab==3 ? $GLOBALS['LANG']->getLL('limit3') : $GLOBALS['LANG']->getLL('limit2')).'</p></div><br class="clearer" />';
			return $searchBox;
		} 
	
		function makeContextSearchFields($tab) {
			if($this->tpm['tagname'][$tab]) {
			    $checked = $this->tpm['context_enabled'] ? ' checked="checked"' : '';
			    $searchBox .= '<div class="floatbox"><p>
				<input type="checkbox" class="tpm_checkbox" name="tpm[context_enabled]['.$tab.']" value="1"'.$checked.' /> '.$GLOBALS['LANG']->getLL('context_enabled1').' <select id="context_level" name="tpm[context_level]['.$tab.']">
				';
				for($i=1;$i<=4;$i++) {				
				    $selected = $this->tpm['context_level'][$tab]==$i ? ' selected="selected"' : '';
				    $searchBox .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
				}
			    $searchBox .= '</select> '.$GLOBALS['LANG']->getLL('context_enabled2').'
				</p>
			    </div><br class="clearer" />';
			    return $searchBox;
			}
		} 
		
		function makeMergeForm($tab) {
		    $mergeForm = '<div id="merge_form">';
		    $mergeForm .= '<label for="tags_to_merge">'.$GLOBALS['LANG']->getLL('as_replacement').'</label><select id="tags_to_merge" name="tpm[tags_to_merge]" size="'.(($size = count($this->tpm['to_be_merged'])) > 3 ? $size+1 : 4).'" onclick="changeSelectedState(this);return false;" ondblclick="document.getElementById(\'tpm_merge_submit\').value = \'submit\'; submit(); return true;">
			<option value="" style="background:#CCC;">'.$GLOBALS['LANG']->getLL('select_master').'</option>';
			if($size) {
			    $selectedTags = $this->tpm['to_be_merged'];
			    foreach($selectedTags as $value => $name) {
				$mergeForm .= '<option value="'.$value.'">'.$name.'</option>';
			    }
			}
		    $mergeForm .= '</select>
		    <div class="typoSuggest">
		    <input id="tpm_new_name_ajaxsearch" name="tpm[merge_now][new_name]" type="text" onfocus="window.tx_tagpack_ajaxsearch_lazyCreator.get(this,{\'startLength\':2}).onfocus();" size="20" autocomplete="off" class="search" value="" title="Tags'.$this->tpm['container_page'][$tab][0].'" onkeypress="document.getElementById(\'tpm_merge_submit\').value = \'submit\'; return true;"/>
		    <input class="hidden" id="tpm_new_id" name="tpm[merge_now][new_id]" type="hidden" />
		    <input class="hidden" id="tpm_merge_submit" name="tpm[merge_now][submit]" type="hidden" />
		    <div class="submitbox"><input type="submit" class="submit" name="tpm[merge_now][submit]" value="'.$GLOBALS['LANG']->getLL('merge_now').'" /></div>
		    <ul class="results" style="" id="tpm_new_name_ajaxsearch_results"></ul>
		    </div>
		    ';
		    $mergeForm .= '<div class="clearer"><!--//CLEARER//--></div></div>';
		    return $mergeForm;
		}
		
		function mergeTags($tagsToMerge,$newId,$newName,$pid) {
		    if(count($tagsToMerge)) {
			if(!$newId) {
			    $newId = tx_tagpack_api::addTag($newName,intval($pid),TRUE);
			}
			if($newId) {
			    foreach($tagsToMerge as $tagToMergeId => $tagToMergeName) {
				if(tx_tagpack_api::tagExists($tagToMergeId) && $tagToMergeId != $newId) {
				    tx_tagpack_api::removeTag($tagToMergeId,FALSE,$newId);
				}
			    }			    
			    return '<div class="ok"><strong>'.count($tagsToMerge).' '.((count($tagsToMerge) == 1) ? $GLOBALS['LANG']->getLL('merge_success1') : $GLOBALS['LANG']->getLL('merge_success')).'</strong></div>';
			}
		    }
		}
	

		function makeResultlist($tab,$hidden=FALSE) {
			if(count($this->tpm['container_page'][$tab])) {
			    $tagName = trim($this->tpm['tagname'][$tab]) ? '%'.trim($this->tpm['tagname'][$tab]).'%' : '%';
			    if(count($resultData = tx_tagpack_api::getTagDataByTagName($tagName,implode(',',$this->tpm['container_page'][$tab]),($this->tpm['taglimit'][$tab] ? $this->tpm['taglimit'][$tab] : 50),$hidden,$this->tpm['tagdatefrom'][$tab],$this->tpm['tagdateto'][$tab]))) {
				foreach ($resultData as $tagData) {
				    if($tagData['hidden']) {
				        if($this->tpm['approve']['blocked'] || (!$this->tpm['approve']['blocked'] && !$this->tpm['approve']['approved'])) {
						$sortedData[$tagData['pid']][ucwords($tagData['name'])]=$tagData;
					}
				    } else if ($this->tpm['approve']['approved'] || (!$this->tpm['approve']['blocked'] && !$this->tpm['approve']['approved'])) {
				        $sortedData[$tagData['pid']][ucwords($tagData['name'])]=$tagData;
					$this->firstLevelResults[$tab] .= $this->firstLevelResults[$tab] ? ','.$tagData['uid'] : $tagData['uid'];
				    }
				}
			    }
			    foreach($this->tpm['container_page'][$tab] as $selectedId) {			    
				$resultList .= $this->makeList($sortedData,$tab,$selectedId);
			    }
			    return $resultList;
			}
		}
		
		function makeRelatedList($tab,$levelResults,$containerId,$limit,$level=0) {
		    $level++;
		    if($levelResults && $this->tpm['context_enabled'][$tab] && $this->tpm['tagname'][$tab]) {
			$this->relatedTags[$level] = tx_tagpack_api::getRelatedTagsForTags($levelResults,$containerId,$limit);
			if($level < $this->tpm['context_level'][$tab] && count($this->relatedTags[$level])) {
			    $nextLevelResults = $levelResults;
			    foreach($this->relatedTags[$level] as $relatedTag) {
				$nextLevelResults .= ','.$relatedTag['uid'];				
			    }
			    return $this->makeRelatedList($tab,$nextLevelResults,$containerId,$limit,$level);
			} else if(count($this->relatedTags)) {
			    foreach($this->relatedTags as $levelId => $levelTags) {
			        foreach($levelTags as $tagData) {
				    $sortedData[$levelId][ucwords($tagData['name'])]=$tagData;
				}
				$levelList .= $this->makeList($sortedData,$tab,$levelId,TRUE);
			    }
			    return $levelList;
			}
		    }
		}
	
		function makeList($sortedData,$tab,$selectedId,$levelTitle = FALSE) {
			if(count($sortedData)) {
				if($counter = count($sortedData[$selectedId])) {
				    ksort($sortedData[$selectedId]);
				    if($levelTitle===FALSE) {
					$resultList .= '<h3>'.$this->availableContainers[$selectedId]['title'].' ['.$selectedId.']</h3>';
				    } else {
					$resultList .= '<h3>'.$counter.' '.($counter==1 ? $GLOBALS['LANG']->getLL('leveltitle') : $GLOBALS['LANG']->getLL('leveltitles')).' '.$selectedId.'</h3>';				    
				    } 
				    $resultList .= '<table cellspacing="1" cellpadding="0" border="0" class="resultlist" width="100%">
				    <colgroup>
					<col width="50px" />
					<col />
					<col width="15px" />
					<col width="15px" />
				    </colgroup>
				    <tr>
					<th>
					    ID
					</th>
					<th>
					    Name
					</th>
					';
				    if($tab == 1) {
					$resultList .= '
					    <th>
						<img src="icons/button_unhide.gif" alt="'.$GLOBALS['LANG']->getLL('blocked').'" title="'.$GLOBALS['LANG']->getLL('blocked').'" />
					    </th>
					    <th>
						<img src="icons/garbage.gif" alt="'.$GLOBALS['LANG']->getLL('remove').'" title="'.$GLOBALS['LANG']->getLL('remove').'" />
					    </th>
					';
				    } else if($tab == 2) {
					$resultList .= '
					    <th colspan="2">
						'.$GLOBALS['LANG']->getLL('Edit').'
					    </th>
					';
				    } else {
					$resultList .= '
					    <th colspan="2">
						'.$GLOBALS['LANG']->getLL('Merge').'
					    </th>
					';
				    }
				    $resultList .= '
				    </tr>
				    ';
				    $counter = 0;
				    foreach($sortedData[$selectedId] as $tagData) {
					$counter++;
					$trClass = fmod($counter,2) ? 'odd' : 'even';
					$resultList .= '<tr class="'.$trClass.'" id="tag'.$tagData['uid'].'"><td align="right">'.$tagData['uid'].'</td><td>'.$tagData['name'].'</td>';
					if($tab == 1) {
					    $hiddenClass = $tagData['hidden'] ? ' class="caution"' : ' class="ok"';
					    $resultList .= '<td'.$hiddenClass.' align="center">
						<input title="'.($tagData['hidden'] ? $GLOBALS['LANG']->getLL('blocked') : $GLOBALS['LANG']->getLL('approved')).'" class="tpm_checkbox" type="checkbox" name="data[tx_tagpack_tags]['.$tagData['uid'].'][hidden]" value="1"'.($tagData['hidden'] ? '' : ' checked="checked"').' onclick="switchStatus(this);return false;" />
						</td>
						<td class="alert" align="center">
						<input title="'.$GLOBALS['LANG']->getLL('remove').'" class="tpm_checkbox" type="checkbox" name="cmd[tx_tagpack_tags]['.$tagData['uid'].'][delete]" value="1" onclick="switchStatus(this);return false;" />
						</td>';
					} else if($tab == 2) {
					    $resultList .= '<td colspan="2" align="center"><a href="#" onclick="tpmEditItem('.$tagData['uid'].');return false;"><img src="icons/edit2.gif" /></a></td>';
					} else if($tab == 3) {
					    $resultList .= '<td align="center" colspan="2">
						<input class="tpm_checkbox" type="checkbox" id="tpm_'.$tagData['uid'].'" name="tpm[to_be_merged]['.$tagData['uid'].']" value="'.$tagData['name'].'"'.($this->tpm['to_be_merged'][$tagData['uid']] ? ' checked="checked"' : '').'" onclick="mergeForm(this);return true;" />
						</td>';
					} else {
					    $resultList .= '<td>4</td><td></td>';
					}
					$resultList .= '</tr>
					';
				    }
				    $resultList .= '
				    </table>';
				}
			    }
			    return $resultList;			    
		}
		
	}
	 
	 
	 
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/mod1/index.php']) {
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpack/mod1/index.php']);
	}
	 
	 
	 
	 
	// Make instance:
	$SOBE = t3lib_div::makeInstance('tx_tagpack_module1');
	$SOBE->init();
	 
	// Include files?
	foreach($SOBE->include_once as $INC_FILE) include_once($INC_FILE);
	 
	$SOBE->main();
	$SOBE->printContent();
	 
?>
