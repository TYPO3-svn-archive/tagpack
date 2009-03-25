function triggerTab(triggerItem,triggeredContent) {
    allTriggers = triggerItem.parentNode.parentNode.getElementsByTagName("li");
    countTriggers = allTriggers.length;
    for(i=1;i<=countTriggers;i++) {
	allTriggers[i-1].className = "redbutton";
	if(i==triggeredContent) {
	    document.getElementById("tabcontent" + i).className = "tabcontent_on";
	} else {
	    document.getElementById("tabcontent" + i).className = "tabcontent_off";
	}
    }
    document.getElementById("tpm_active_tab").value = triggeredContent;
    triggerItem.parentNode.className = "greenbutton";
    triggerItem.blur();
}