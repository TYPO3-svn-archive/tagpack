<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$lConf = unserialize($_EXTCONF);
t3lib_extMgm::addPageTSConfig('
	tx_tagpack_tags.taggedTables = '.$lConf['taggedTables'].'
	tx_tagpack_tags.getTagsFromPid = '.$lConf['getTagsFromPid'].'
');

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_tagpack_tags=1
');

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_tagpack_pi1.php', '_pi1', 'list_type', 1);
t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_tagpack_pi2.php','_pi2','',1);
t3lib_extMgm::addPItoST43($_EXTKEY,'pi3/class.tx_tagpack_pi3.php','_pi3','list_type',1);

$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][] = 'EXT:tagpack/class.tx_tagpack_tceforms_addtags.php:tx_tagpack_tceforms_addtags';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'EXT:tagpack/class.tx_tagpack_tceforms_addtags.php:tx_tagpack_tceforms_addtags';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:tagpack/class.tx_tagpack_tceforms_addtags.php:tx_tagpack_tceforms_addtags';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:tagpack/class.tx_tagpack_tceforms_addtags.php:tx_tagpack_tceforms_addtags';

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/dummy.php']= t3lib_extMgm::extPath($_EXTKEY) . 'class.ux_SC_dummy.php';
?>