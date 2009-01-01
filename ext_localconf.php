<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// adding a default userTSconfig
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_tagpack_tags = 1
');

// adding the plugins to the pagetemplate setup
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_tagpack_pi1.php', '_pi1', '', 1);
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi2/class.tx_tagpack_pi2.php', '_pi2', '', 1);
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi3/class.tx_tagpack_pi3.php', '_pi3', 'list_type', 1);

// adding the tagpack Hooks to TCEforms and TCEmain
$hookClass = 'EXT:tagpack/class.tx_tagpack_tceforms_addtags.php:tx_tagpack_tceforms_addtags';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'][]  = $hookClass;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = $hookClass;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]  = $hookClass;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]   = $hookClass;

?>
