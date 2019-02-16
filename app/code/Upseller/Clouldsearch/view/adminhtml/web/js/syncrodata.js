//var cloudsearchSynchronization = Class.create();
define([
	'jquery',
	'underscore',
    'prototype'
], function ($,_,prototype) {

	window.cloudsearchSynchronization = Class.create();
	console.log(cloudsearchSynchronization);
	cloudsearchSynchronization.prototype = {

		initialize: function(options) {
		 	this.ajax(options,options.initurl);
		  	
		},
		ajax : function(options,url){
			//alert(this.options.ajaxurl);
			//$('loadingmigration').show();
			new Ajax.Request(url, {
			  method: 'post',
			  parameters: $(options.fromdata).serialize(),
			  onSuccess: function(transport){
				var json = transport.responseText.evalJSON();
				console.log(json);
				cloudsearchSynchronization.prototype.resposnceFunction(json,options);
				console.log('pratik');
			  },
			  onFailure : function(transport){
			  	
				var json = transport.responseText.evalJSON();
				//console.log(json);
				
			  }
			});	
			  
		},
		resposnceFunction : function(json,options){
			document.getElementById("syncronizationdata").innerHTML=json.loading_html;
			console.log(json.finish);
			if(json.error==false){
				if(json.finish==false){
					console.log(options.continueurl);
					cloudsearchSynchronization.prototype.ajax(options,options.continueurl);  
				}else{
					//alert("finish");
				}
			}else{
				alert(json.error_message);
			}
		  
		  	//$('loadingmigration').hide();
		  
		}
	};

});
