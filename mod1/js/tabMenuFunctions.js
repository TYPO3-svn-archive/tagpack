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
    triggerItem.parentNode.className = "greenbutton";
    triggerItem.blur();
}