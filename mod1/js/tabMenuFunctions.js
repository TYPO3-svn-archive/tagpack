top.iframeOn = false;

function triggerTab(triggerItem,triggeredContent) {
    allTriggers = triggerItem.parentNode.parentNode.getElementsByTagName("li");
    countTriggers = allTriggers.length;
    for(i=1;i<=countTriggers;i++) {
	allTriggers[i-1].className = "redbutton";
	if(i==triggeredContent) {
	    document.getElementById("tabcontent" + i).className = "tabcontent_on";
	    allTriggers[i-1].className = "greenbutton";
	} else {
	    document.getElementById("tabcontent" + i).className = "tabcontent_off";
	}
    }
    document.getElementById("tpm_active_tab").value = triggeredContent;
}

function switchStatus(triggerItem) {
    if(triggerItem.checked) {
	self.location.href = '/typo3/tce_db.php?' + triggerItem.name + '=0&redirect=' + self.location.pathname + '#' + triggerItem.parentNode.parentNode.id;
	return false;
    } else {
	self.location.href = '/typo3/tce_db.php?' + triggerItem.name + '=1&redirect=' + self.location.pathname + '#' + triggerItem.parentNode.parentNode.id;
	return false;
    }
}

function tpmEditItem(triggerItem) {
    if(triggerItem) {
	window.inner_frame.location.href = '/typo3/alt_doc.php?edit[tx_tagpack_tags][' + triggerItem + ']=edit';
	Effect.Grow('iframe_container');
	top.iframeOn = true;
	window.inner_frame.focus();
    }
}

function tpmIframeHide() {
    if(top.iframeOn === true) {
	Effect.SwitchOff('iframe_container');
	top.iframeOn = false;
    }
}

function checkDate(triggerItem) {
    alert(toLocaleString(triggerItem.value));        
}

function mergeForm(triggerItem) {
    selectBox = document.getElementById('tags_to_merge');
    triggerItemId = triggerItem.id.substring(4);
    newOption = new Option(triggerItem.value,triggerItemId,false,false);
    exists = false;	
    if(selectBox.options.length) {
        for(var i=0;i<selectBox.options.length;i++) {
	    if(selectBox.options[i].value == triggerItemId) {
	        exists == true;
		if(!triggerItem.checked) {
		    selectBox.options[i] = null;    
		}		
	    }
	}
    }
    if(exists==false && triggerItem.checked) {
        selectBox.options[document.getElementById('tags_to_merge').options.length] = newOption;
    }
    selectBox.size = selectBox.options.length;
}