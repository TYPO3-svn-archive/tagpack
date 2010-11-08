/**
 * manages one ajax-search field 
 **/
function tx_tagpack_ajaxsearch_observer(el,creator,params) {
	/**
	 * add keyboard event listener on focus
	 * onfocus is already an event handler the "oldschool" attribute way
	 **/
	this.onfocus = function() {
		if (!this.vcListener) {
			if (!this.results.empty()) {
				this.results.style.display = 'block';
			} else {
			}
			this.vcListener = this.valChanged.bindAsEventListener(this);
			this.el.observe('keydown',this.vcListener);
		}
			
	}

	/** 
	 * remove keyboard listener on blur
	 **/
	this.onblur = function(e) {
		if (this.vcListener) {
			this.el.stopObserving('keydown',this.vcListener);
			this.vcListener = null;			
		}
		this.hideResults();
	}

	/**
	 * helper method add a little timeout so the character arrives at the input
	 **/
	this.valChanged = function(e) {		
		window.setTimeout(this.valChangedDelayed.bind(this),20);
	}

	/**
	 * checks if there is enough input to perform the search
	 **/
	this.valChangedDelayed = function() {				
		var v = this.el.value;
		window.status = v;
		if (v.length >= this.params.startLength) {
			if(this.lastVal != v) {
				// yeah, fancy loading icons from www.ajaxload.info
				this.el.addClassName('loading');
				this.request(v);
				this.lastVal = v;
			}
		} else {
			this.results.innerHTML = '';
		}
	}

	/**
	 * submits the ajax search request 
	 **/
	this.request = function(query) {
		new Ajax.Updater(
			this.results,
			window.tx_tagpack_ajaxsearch_server,
			{
				method:'get',
				parameters:{'function':'groupsearch','id':this.el.id,'value':query,'pid':this.el.title.replace(/Tags/g,'')},
				onSuccess: this.showResults.bind(this),
				onFailure: function(){ alert('error in request!') }
			}
		);
	}


	this.showResults = function() {
		this.el.removeClassName('loading');
		this.results.style.display = 'block';
		if (!this.resultsListener) {
			this.resultsListener =  this.resultsKeypressed.bindAsEventListener(this);
			Event.observe(this.keyObserver, 'keydown', this.resultsListener, false);
		}
	}

	this.hideResults = function() {
		if (this.resultsListener) {
			Event.stopObserving(this.keyObserver, 'keydown', this.resultsListener);
			this.resultsListener = null;
		}
		// this timeout is super important, else the result browser will disappear before the clicks will be triggered
		window.setTimeout((function () {this.results.hide();this.results.innerHTML = '';}).bind(this),1000);
	}

	/**
	 * key handling for browsing the result list
	 **/
	this.resultsKeypressed = function (e) {
		var oldCurrent = this.current;
		switch (e.keyCode) {
			case Event.KEY_DOWN:
				// take the next or the first
				this.current = this.current ? this.current.next() : this.results.down('dt');
				this.current = this.current.className ? this.current : this.current.next();
				break;
			case Event.KEY_UP:
				// take the previous or the first
				this.current = this.current ? this.current.previous() : this.results.down('dt');
				this.current = this.current.className ? this.current : this.current.previous();
				break;
			
			case 10: // prototype doesn't care for *ix*
			case Event.KEY_RETURN:
				if (this.current) {
					if(this.el.id.substr(0,3)!='tpm') {
					    this.current.down('a').onclick();
					    this.el.value = '';
					    this.el.blur();
					    this.el.focus();
					    this.current = false;
					    this.lastVal = false;
					} else {
					    this.el.value = this.current.getElementsByTagName('span')[0].title;
					    if(document.getElementById('tpm_new_id')) {
						document.getElementById('tpm_new_id').value = this.current.title.replace(/tx_tagpack_tags_/,'');
					    }
					    this.el.blur();
					    this.current = false;
					}
				} else {
				    if(this.results.lastChild.className=='allowed') {
					setFormValueFromBrowseWin(this.el.id.replace(/\D\d\D_ajaxsearch/g,''),'new_'+this.el.value,this.el.value,'');
				    }
				    this.el.value='';
				    this.el.blur();
				    this.el.focus();
				    this.lastVal = false;
				}
				Event.stop(e);
				break;

			case Event.KEY_HOME:
				this.current = this.results.down('dt');
				Event.stop(e);
				break;
			case Event.KEY_END:
				this.current = this.results.down('dt');
				this.current = this.current.nextSiblings().last();
				Event.stop(e);
				break;
		}

		if (oldCurrent)
			oldCurrent.removeClassName('focus');

		if (this.current) 
			this.current.addClassName('focus');		
	}

	/**
	 * "constructor"
	 */
	this.init = function () {	
		this.el = $(el);	
		this.parent = $(el.parentNode);
		this.keyObserver = (document.all && !window.opera) ? window.document : window;
		this.results = $(this.el.id+'_results');
		this.creator = creator;
		this.params = params;
		this.params.startLength = this.params.startLength ? this.params.startLength : 3;
		this.vcListener = 0;
		this.el.observe('blur',this.onblur.bindAsEventListener(this));		
	}
	
	this.init();	
}

/**
 * creates and manages the tx_tagpack_ajaxsearch_observer objects
 */
function tx_tagpack_ajaxsearch_lazyCreate() {
	this.els = new Array();
	this.get = function (el,params) {		
		var id = el.id;
		if (!this.els[id]) {	
			this.els[id] = new tx_tagpack_ajaxsearch_observer(el,this,params);
		}
		return this.els[id];
	}
}
/**
 * THE one global intance of tx_tagpack_ajaxsearch_lazyCreate
 */
window.tx_tagpack_ajaxsearch_lazyCreator = new tx_tagpack_ajaxsearch_lazyCreate();
