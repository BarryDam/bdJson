var bdJson = (function ($, window, undefined) {
	/**
	 * @example
	 * var object = new bdJson('http://www.example.com/fruit.php');
	 * 
	 * object.post(
	 *    'getFruit', 
	 *    { startWith: 'a' },
	 *    function onSucces(getData) {
	 *    	console.log(getData.fruit);
	 *    },
	 *    function onError(getData) {
	 *    	console.error('something went wrong', getData);
	 *    }
	 *	);
	 *
	 * 
	 * @param  string	getJsonURL     URL to php script which returns JSON data
	 * @param  object   getDefaultData *not required.. default data that needs to be passed allong with the post request
	 * @return void
	 */
	var bdJson = function(getJsonURL, getDefaultData) {
		// in case you forget to use the word NEW
		// allways create a new instance
		if(!(this instanceof bdJson))
			return new bdAppClass(getJsonURL);
		// the url to exec the ajaxcall
		this.JSON_URL = null;
		// default data when posted
		this.defaultData = {};
		// hold the jQuery xhr object when $.post is called
		this.jqXhr = null;
		// setters
		this.setURL(getJsonURL);
		this.setDefaultData(getDefaultData); 
	};
	bdJson.prototype.setURL = function(getJsonURL) {
		if (typeof(getJsonURL) != 'undefined')
			this.JSON_URL = getJsonURL;
		else
			console.error('No URL passed in param');
	};
	bdJson.prototype.setDefaultData = function(getDefaultData) {
		if (typeof(getDefaultData) == 'object')
			this.defaultData = getDefaultData;
	};
	bdJson.prototype.getURL = function() {
		return this.JSON_URL;
	};
	bdJson.prototype.getDefaultData = function() {
		return this.defaultData;
	};
	bdJson.prototype.post = function(getAction, getData, getSuccessCallback, getErrorCallBack) {
		// if 2nd param a function.. then its the getSuccessCallback
		if (typeof(getData) == 'function') {
			// if 3rd param is a function then its the errorcallback
			if (typeof(getSuccessCallback) == 'function')
				getErrorCallBack = getSuccessCallback;
			getSuccessCallback	= getData;
			getData	= {};
		} 
		// 2nd param undefined
		else if (typeof(getData) != 'object') 
			getData = {};	
		// prepare data
		var data = $.extend({}, this.getDefaultData(), {bdJson_Action : getAction}, getData);
		// abort previous ajax request if not already finished or failed
		if (this.jqXhr)
			this.jqXhr.abort();
		var that = this;
		// do the post and exec callback on success
		that.jqXhr = $.post(
			that.getURL(),
			data,
			function(objReturnData) {
				that.jqXhr = null;
				// on error
				if ((! objReturnData.success || objReturnData.error) && typeof(getErrorCallBack)==='function')
					getErrorCallBack(objReturnData.error);
				// on success
				if (objReturnData.success && typeof(getSuccessCallback)==='function') {
					// delete success cuz we allready used it here
					delete objReturnData.success;
					getSuccessCallback(objReturnData);
					return;
				}
			},
			'json'
		).fail(function (jqXHR, txtStatus, errorThrown) {
			that.jqXhr = null;
			if (txtStatus !== 'abort') {
				if (typeof(getErrorCallBack)==='function')
					getErrorCallBack(txtStatus);
			}		
		});
	};
return bdJson;
})(jQuery, window);