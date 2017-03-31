//WX SYSTEM CLASSES

var wx = {
	config : {
		base_host : '/'
	},

	init : function(options) {
		wx.config = $.extend( true, wx.config, options );
	},

	ajax : function(action, vars, options) {
		var defaults = {
			// variables
			async : false,
			cache : false,
			method : 'POST',
			dataType : 'JSON',
			isImageAjax : false,
			params : {},

			// callbacks
			beforeSend : function() { loadStart('Loading...'); },
			afterSuccess : function(response) {}
		}

		var settings = $.extend(true, defaults, options);

		// create image settings for jquery $.ajax
		var ajaxSettings = {};
		if (settings.isImageAjax === true) {
			ajaxSettings.contentType = false;
			ajaxSettings.processData = false;
			if (settings.params !== null) {
				var action = action + '/?';
				$.each(settings.params, function(k,v){
					action += k + '=' + v + '&';
				})
			}
		} else {
			ajaxSettings.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
			ajaxSettings.processData = true;
		}
		
		$.ajax({
			url : action,
			type : 'POST',
			async :	settings.async,
			data : vars,
			dataType: settings.dataType,
			cache: settings.cache,
			processData: ajaxSettings.processData,
			contentType: ajaxSettings.contentType,
			beforeSend : function () {
				settings.beforeSend();
			},
			success : function (response) {
				loadEnd();
				if (response !== undefined && response.unexpectedError !== undefined) {
					wx.dialog.error(response.data.message);
					switch (parseInt(response.errorCode)) {
						case 901:
							window.location.href = '/';
							break;
						default:
							wx.dialog.error(response.message);
					}
				} else {
					if (typeof options === 'function') {
						options(response);
					} else {
						settings.afterSuccess(response);
					}
				}
			},
			statusCode : {
				404: function () {
					loadEnd();
					wx.dialog.error(t('JS_ERROR_UNEXPECTED'));
				},
				500: function () {
					loadEnd();
					wx.dialog.error(t('JS_ERROR_UNEXPECTED'));
				}
			},
			onError: function () {
				loadEnd();
				wx.dialog.error(t('JS_ERROR_UNEXPECTED'));
			}
		});
	},
	
	outerHtml : function(selector) {
		return $(selector).clone().wrap('<div></div>').parent().html();
	}
}

wx.feedback = {
	generateId : function() {
		var number1 = 1 + Math.floor(Math.random()*1000),
			number2 = 1 + Math.floor(Math.random()*1000),
			now = $.now();

		return 'feedback-'+number1.toString()+now.toString()+number2.toString();
	},
	init : function(type, message, group, fadeOutTimeOverride) {
		var id = this.generateId();

		// create the html code by schema
		if (group) {
			var groupHtml = 'data-group='+group;
		} else {
			var groupHtml = '';
		}

		/* Create from any "TYPE" string a "Type" style string*/
		var formattedType = type.toLowerCase();
		var formattedType = formattedType.charAt(0).toUpperCase()+formattedType.slice(1);
	
		/* Get schema */
		var schemaFunction = 'wx.feedback.schema'+formattedType+'("'+id+'", "'+message+'", "'+groupHtml+'")';
		var html = eval(schemaFunction);

		// add to DOM
		$('#wx-feedback').append(html);

		// bind close
		this.bindClose(id);

		// set timeout
		$('#'+id).fadeIn(500, function(){
			if (fadeOutTimeOverride) {
				wx.feedback.setTimer(id, fadeOutTimeOverride);
			} else {
				switch (type.toLowerCase()) {
					case 'warning' :
					case 'info' :
						wx.feedback.setTimer(id, 15000);
						break;
					case 'success' :
						wx.feedback.setTimer(id, 5000);
						break;
				}
			}
		});

		return id;
	},
	schemaLoading : function (id, message, group) {
		var html = '<div ' + group + ' id="'+id+'" class="feedback loading"><div class="message"><i class="fa fa-refresh fa-spin"></i>'+message+'</div><div class="close">x</div></div><div class="clear"></div>';
		return html;
	},
	schemaInfo : function (id, message, group) {
		var html = '<div ' + group + ' id="'+id+'" class="feedback info"><div class="message"><i class="fa fa-info-circle"></i></i>'+message+'</div><div class="close">x</div></div><div class="clear"></div>';
		return html;
	},
	schemaSuccess : function (id, message, group) {
		var html = '<div ' + group + ' id="'+id+'" class="feedback success"><div class="message"><i class="fa fa-check-circle"></i>'+message+'</div><div class="close"><i class="fa fa-times"></i></div></div><div class="clear"></div>';
		return html;
	},
	schemaWarning : function (id, message, group) {
		var html = '<div ' + group + ' id="'+id+'" class="feedback warning"><div class="message"><i class="fa fa-exclamation-circle"></i>'+message+'</div><div class="close">x</div></div><div class="clear"></div>';
		return html;
	},
	schemaError : function (id, message, group) {
		var html = '<div ' + group + ' id="'+id+'" class="feedback error"><div class="message"><i class="fa fa-minus-circle"></i>'+message+'</div><div class="close"><i class="fa fa-times"></i></div></div><div class="clear"></div>';
		return html;
	},
	setTimer : function (id, milliSec) {
		setTimeout(function() {
			wx.feedback.close(id);
		}, milliSec);
	},
	close : function(id) {
		$('#'+id).fadeOut(1000, function() {
			wx.feedback.remove(id);
		});		
	},
	remove : function(id) {
		$('#'+id).remove();
	},
	removeGroup : function(groupId) {
		var selector = 'div[data-group="'+groupId+'"]';
		$.each($(selector), function(index, value) {
			var id = $(this).attr('id');
			$('#'+id).remove();
		});
		
	},
	bindClose : function (id) {
		$('#'+id+' .close').click(function(e){
			e.preventDefault();
			$('#'+id).fadeOut(500, function() {
				$('#'+id).remove();
			});
		});
	}
}

wx.tools = {
	now : function() {
		var d = new Date();

		var month = d.getMonth()+1;
		var day = d.getDate();
		var hour = d.getHours();
		var minute = d.getMinutes();
		var second = d.getSeconds();

		var output = d.getFullYear() + '-' +
			((''+month).length<2 ? '0' : '') + month + '-' +
			((''+day).length<2 ? '0' : '') + day + ' ' +
			((''+hour).length<2 ? '0' :'') + hour + ':' +
			((''+minute).length<2 ? '0' :'') + minute + ':' +
			((''+second).length<2 ? '0' :'') + second;

		return output;
	},
	
	sleep : function(milliseconds) {
		var start = new Date().getTime();
		for (var i = 0; i < 1e7; i++) {
			if ((new Date().getTime() - start) > milliseconds){
				break;
			}
		}
	}
}

function D(value, mode) {
	if (mode === undefined) {
		console.log(value);
	} else {
		switch (mode) {
			case 'i' :
				console.info(value);
				break;ar 
			case 'd' :
				console.debug(value);
				break;
			case 'e' :
				console.error(value);
				break;
			default :
				console.error('Undefined debug mode: ' + value);
				break;
		}
	}
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();

    if (exdays !== undefined) {
	    d.setTime(d.getTime() + (exdays*24*60*60*1000));
	    var expires = "expires="+d.toUTCString();
	} else {
	    var expires = "";
	} 

    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
    }
    return "";
}

function t (str) {
	return str;
	/*
	if (TRANSLATOR.hasOwnProperty(str)) {
		return TRANSLATOR[str];
	} else {
		return str;
	}
	*/
	
}

function loadStart () {
	$('body').css({'cursor':'progress'});
}

function loadEnd () {
	$('body').css({'cursor':'default'});
}

function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}

$.extend({
    keyCount : function(o) {
        if(typeof o == "object") {
            var i, count = 0;
            for(i in o) {
                if(o.hasOwnProperty(i)) {
                    count++;
                }
            }
            return count;
        } else {
            return false;
        }
    }
});