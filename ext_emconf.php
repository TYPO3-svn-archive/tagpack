<?php

########################################################################
# Extension Manager/Repository config file for ext: "tagpack"
#
# Auto generated 14-04-2009 13:21
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tag Pack',
	'description' => 'All purpose tagging suite. Use tags for almost any allowed table without having to create new DB fields for each of them. Create multifunctional tag clouds using surf or filter mode together with time based settings, a tag breadcrumb menu and a searchbox with autocompletion.',
	'category' => '',
	'shy' => 0,
	'version' => '0.8.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'JoH asenau',
	'author_email' => 'jh@eqony.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:97:{s:9:"ChangeLog";s:4:"e2a5";s:10:"README.txt";s:4:"ee2d";s:38:"class.tx_tagpack_ajaxsearch_client.php";s:4:"6520";s:38:"class.tx_tagpack_ajaxsearch_server.php";s:4:"aa26";s:28:"class.tx_tagpack_realurl.php";s:4:"11da";s:37:"class.tx_tagpack_tceforms_addtags.php";s:4:"8eec";s:21:"class.ux_SC_dummy.php";s:4:"9dd3";s:21:"ext_conf_template.txt";s:4:"8af7";s:12:"ext_icon.gif";s:4:"f4d1";s:17:"ext_localconf.php";s:4:"3db0";s:14:"ext_tables.php";s:4:"cb33";s:14:"ext_tables.sql";s:4:"922c";s:24:"icon_tx_tagpack_tags.gif";s:4:"f4d1";s:13:"locallang.xml";s:4:"5c1d";s:16:"locallang_db.xml";s:4:"83e2";s:7:"tca.php";s:4:"e49c";s:14:"doc/manual.sxw";s:4:"f7ea";s:19:"doc/wizard_form.dat";s:4:"c7aa";s:20:"doc/wizard_form.html";s:4:"74dc";s:28:"lib/class.tx_tagpack_api.php";s:4:"e781";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"fc0a";s:14:"mod1/index.php";s:4:"3967";s:18:"mod1/locallang.xml";s:4:"9f51";s:22:"mod1/locallang_mod.xml";s:4:"f72d";s:19:"mod1/moduleicon.gif";s:4:"f4d1";s:24:"mod1/css/lightwindow.css";s:4:"ea70";s:23:"mod1/css/tagmanager.css";s:4:"bb96";s:18:"mod1/icons/add.gif";s:4:"408a";s:26:"mod1/icons/button_down.gif";s:4:"fa54";s:26:"mod1/icons/button_hide.gif";s:4:"fba8";s:26:"mod1/icons/button_left.gif";s:4:"cdec";s:27:"mod1/icons/button_right.gif";s:4:"5573";s:28:"mod1/icons/button_unhide.gif";s:4:"fde9";s:24:"mod1/icons/button_up.gif";s:4:"0cc7";s:20:"mod1/icons/clear.gif";s:4:"cc11";s:19:"mod1/icons/down.gif";s:4:"b8a8";s:19:"mod1/icons/edit.gif";s:4:"55bd";s:20:"mod1/icons/edit2.gif";s:4:"3248";s:22:"mod1/icons/edit2_d.gif";s:4:"2717";s:22:"mod1/icons/edit2_h.gif";s:4:"ddc9";s:22:"mod1/icons/edit_fe.gif";s:4:"336a";s:24:"mod1/icons/edit_file.gif";s:4:"a68b";s:24:"mod1/icons/edit_page.gif";s:4:"a055";s:26:"mod1/icons/edit_rtewiz.gif";s:4:"d8b4";s:25:"mod1/icons/editaccess.gif";s:4:"7bdb";s:22:"mod1/icons/garbage.gif";s:4:"7fbe";s:21:"mod1/icons/level1.gif";s:4:"443d";s:21:"mod1/icons/level2.gif";s:4:"d7a2";s:21:"mod1/icons/level3.gif";s:4:"47d3";s:21:"mod1/icons/level4.gif";s:4:"2271";s:20:"mod1/icons/minus.gif";s:4:"de77";s:26:"mod1/icons/minusbottom.gif";s:4:"5f7a";s:24:"mod1/icons/minusonly.gif";s:4:"362b";s:23:"mod1/icons/minustop.gif";s:4:"f47e";s:21:"mod1/icons/new_el.gif";s:4:"591c";s:24:"mod1/icons/new_level.gif";s:4:"7fcf";s:19:"mod1/icons/plus.gif";s:4:"d67c";s:25:"mod1/icons/plusbottom.gif";s:4:"9791";s:23:"mod1/icons/plusonly.gif";s:4:"f127";s:22:"mod1/icons/plustop.gif";s:4:"6d51";s:23:"mod1/icons/shortcut.gif";s:4:"7546";s:17:"mod1/icons/up.gif";s:4:"822e";s:28:"mod1/images/ajax-loading.gif";s:4:"b6f3";s:26:"mod1/images/arrow-down.gif";s:4:"2694";s:24:"mod1/images/arrow-up.gif";s:4:"020a";s:27:"mod1/images/background1.png";s:4:"6ff3";s:24:"mod1/images/black-70.png";s:4:"703c";s:21:"mod1/images/black.png";s:4:"1cf6";s:31:"mod1/images/buttongreen_off.png";s:4:"89af";s:30:"mod1/images/buttongreen_on.png";s:4:"edeb";s:29:"mod1/images/buttonred_off.png";s:4:"1918";s:28:"mod1/images/buttonred_on.png";s:4:"b24b";s:25:"mod1/images/nextlabel.gif";s:4:"485d";s:30:"mod1/images/pattern_148-70.png";s:4:"e04d";s:27:"mod1/images/pattern_148.gif";s:4:"4d56";s:25:"mod1/images/prevlabel.gif";s:4:"d935";s:26:"mod1/images/screenback.png";s:4:"4ba3";s:28:"mod1/images/submitbutton.png";s:4:"51a7";s:18:"mod1/js/effects.js";s:4:"7808";s:22:"mod1/js/lightwindow.js";s:4:"2f3d";s:27:"mod1/js/tabMenuFunctions.js";s:4:"c4b9";s:28:"pi1/class.tx_tagpack_pi1.php";s:4:"5f61";s:28:"pi2/class.tx_tagpack_pi2.php";s:4:"338e";s:28:"pi3/class.tx_tagpack_pi3.php";s:4:"eec4";s:17:"pi3/locallang.xml";s:4:"9028";s:23:"res/ajaxgroupsearch.css";s:4:"8cd2";s:22:"res/ajaxgroupsearch.js";s:4:"0cc4";s:15:"res/loading.gif";s:4:"e059";s:24:"res/magnifying-glass.gif";s:4:"5b28";s:24:"res/magnifying-glass.xcf";s:4:"ffd5";s:34:"static/tagnomination/constants.txt";s:4:"d41d";s:30:"static/tagnomination/setup.txt";s:4:"7d41";s:32:"static/taggeditems/constants.txt";s:4:"9e61";s:28:"static/taggeditems/setup.txt";s:4:"dee7";s:29:"static/tagcloud/constants.txt";s:4:"c924";s:25:"static/tagcloud/setup.txt";s:4:"165b";}',
	'suggests' => array(
	),
);

?>