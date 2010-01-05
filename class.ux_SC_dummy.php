<?php
/**
 * Script Class, creating the content for the dummy script - which is just blank output. *
 *  
 * @author    Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author    JoH asenau <info@cybercraft.de>
 * @package TYPO3 
 * @subpackage core
 */
 
class ux_SC_dummy extends SC_dummy {
    var $content;
    function main()    {
	global $TBE_TEMPLATE;
 	// Start page
 	$TBE_TEMPLATE->docType = 'xhtml_trans';
	$this->content.=$TBE_TEMPLATE->startPage('Dummy document');
	$this->content .= '<script type="text/javascript">
	<!--// TRIGGER onblur
	if(parent.Effect && top.iframeOn) {
	    parent.Effect.SwitchOff("iframe_container");
	    top.iframeOn = false;
	}
	//-->
	</script>';
	// End page:
	$this->content.=$TBE_TEMPLATE->endPage();
    }
}
?>