<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages('tx_tagpack_tags');
t3lib_extMgm::addToInsertRecords('tx_tagpack_tags');

$TCA['tx_tagpack_tags'] = array(
	'ctrl' => array(
		'title'     => 'LLL:EXT:tagpack/locallang_db.xml:tx_tagpack_tags',		
		'label'     => 'name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'default_sortby' => 'ORDER BY name',	
		'delete'         => 'deleted',	
		'enablecolumns' => array (		
			'disabled'  => 'hidden',	
			'starttime' => 'starttime',	
			'endtime'   => 'endtime',	
			'fe_group'  => 'fe_group',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_tagpack_tags.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, name, relations',
	)
);

t3lib_div::loadTCA('tt_content');

// set up plugin pi1 (tagcloud)
$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem'] = 'CType;;4;button;1-1-1, header;;3;;2-2-2';
t3lib_extMgm::addPlugin(array('LLL:EXT:tagpack/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY.'_pi1'), 'CType');

// set up plugin pi2 (tagnominations)
$TCA['tt_content']['types'][$_EXTKEY.'_pi2']['showitem'] = 'CType;;4;button;1-1-1, header;;3;;2-2-2';
t3lib_extMgm::addPlugin(array('LLL:EXT:tagpack/locallang_db.xml:tt_content.CType_pi2', $_EXTKEY.'_pi2'), 'CType');

// set up plugin pi3
$TCA['tt_content']['types'][$_EXTKEY.'_pi3']['showitem'] = 'CType;;4;button;1-1-1, header;;3;;2-2-2';
t3lib_extMgm::addPlugin(array('LLL:EXT:tagpack/locallang_db.xml:tt_content.list_type_pi3', $_EXTKEY.'_pi3'), 'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/tagcloud/',       'Tagpack Plugin: Tagcloud');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/tagnominations/', 'Tagpack Plugin: Tagnominations');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/taggeditems/',    'Tagpack Plugin: Tagged Items');


if (TYPO3_MODE == 'BE')	{
    include_once(t3lib_extMgm::extPath('tagpack') . 'class.tx_tagpack_ajaxsearch_client.php');
    t3lib_extMgm::addModule('user', 'txtagpackM1', '', t3lib_extMgm::extPath($_EXTKEY).'mod1/');
}

?>
