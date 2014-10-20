/*
* This file is a part of: AGATE WEB framework
* http://agateweb.org/
*
* Copyright 2012, Vasile Giorgi
* Dual licensed under the LGPL licenses.
* http://agateweb.org/license
*
* Date: Tue May 1 16:18:21 2012 -0400
*
* Include: Remedial JavaScript by Douglas Crockford http://www.crockford.com/
* 	source and details here: http://javascript.crockford.com/remedial.html
*
* Required jQuery at least version 1.7 http://jquery.com/
*/



//Remedial Javascript (http://javascript.crockford.com/remedial.html):
function typeOf(b){var a=typeof b;if(a==="object"){if(b){if(Object.prototype.toString.call(b)=="[object Array]"){a="array"}}else{a="null"}}return a}function isEmpty(c){var b,a;if(typeOf(c)==="object"){for(b in c){a=c[b];if(a!==undefined&&typeOf(a)!=="function"){return false}}}return true}if(!String.prototype.entityify){String.prototype.entityify=function(){return this.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}}if(!String.prototype.quote){String.prototype.quote=function(){var e,b,a=this.length,d='"';for(b=0;b<a;b+=1){e=this.charAt(b);if(e>=" "){if(e==="\\"||e==='"'){d+="\\"}d+=e}else{switch(e){case"\b":d+="\\b";break;case"\f":d+="\\f";break;case"\n":d+="\\n";break;case"\r":d+="\\r";break;case"\t":d+="\\t";break;default:e=e.charCodeAt();d+="\\u00"+Math.floor(e/16).toString(16)+(e%16).toString(16)}}}return d+'"'}}if(!String.prototype.supplant){String.prototype.supplant=function(a){return this.replace(/{([^{}]*)}/g,function(d,c){var e=a[c];return typeof e==="string"||typeof e==="number"?e:d})}}if(!String.prototype.trim){String.prototype.trim=function(){return this.replace(/^\s*(\S*(?:\s+\S+)*)\s*$/,"$1")}};


// Extending javascript String objects:
////ucFirst (inspired by this thread: http://stackoverflow.com/questions/1026069/capitalize-the-first-letter-of-string-in-javascript
if (!String.prototype.ucFirst) {
	String.prototype.ucFirst = function () {
		return this.charAt(0).toUpperCase() + this.slice(1).toLowerCase();
	};
}

////padLeft (add zero at the begining of the string, usefull for numbers)
if(!String.prototype.padLeft) {
	String.prototype.padLeft = function (n, c) {
		if (isNaN(n)) {
			return null;
		}
		c = c || "0";
		return (new Array(n).join(c).substring(0, this.length-n)) + this;
	};
}

////suplant
/*
if (!String.prototype.supplant) {
	String.prototype.supplant = function (o) {
		return this.replace(/{([^{}]*)}/g,
			function (a, b) {
				var r = o[b];
				return typeof r === 'string' || typeof r === 'number' ? r : a;
			}
		);
	};
}
*/

////atos = convert an array to string using a pattern
function atos(a, p) {
	var i, r = '', iCount = a.length;
	if (typeof(a) === 'object' && iCount === undefined) {
		r = p.supplant(a);
	}
	else {
		for (i = 0; i < iCount; i++) {
			r += p.supplant(a[i]);
		}
	}
	return r;
}

//Array
////Extending javascript Array object with indexOf (not defined in MSIE)
if (!Array.indexOf) {
	Array.prototype.indexOf = function (obj, start) {
		for (var i = (start || 0); i < this.length; i++) {
			if (this[i] === obj) {
				return i;
			}
		}
	};
}


//jQuery
/**
 * extend jquery to add mapping 2 arrays into one object
 */
$.fn.arrayMap = function (arNames, arValues) {
	var oReturn = {},
		i,
		iMax = Math.min(arNames.length, arValues.length);
	for (i = 0; i < iMax; i++) {
		oReturn[arNames[i]] = arValues[i];
	}
	return (oReturn);
};


/**
 * extend jquery to add multidimensional array into one array of objects
 */
$.fn.arrayMapArray = function (arNames, arValues) {
	var aReturn = [],
		i,
		iMax = arValues.length;
	for (i = 0; i < iMax; i++) {
		aReturn[i] = $.fn.arrayMap(arNames, arValues[i]);
	}
	return (aReturn);
};


/**
 * extend jquery to add get vars from url
 */
$.fn.getUrlVars = function (sUrl) {
	if (typeof(sUrl) === 'undefined') {
		sUrl = window.location.href;
	}
	var vars = [], hash;
	var hashes = sUrl.slice(sUrl.indexOf('?') + 1).split('&');
	for (var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
};


/**
 * extend jquery to add formToLocalStorage
 */
$.fn.formToLocalStorage = function (selector) {
	var oFormValues = $(selector).serializeArray();
	localStorage.setItem(selector, JSON.stringify(oFormValues));
};


/**
 * extend jquery to add localStorageToForm
 */
$.fn.localStorageToForm = function (selector) {
	var oFormValues = JSON.parse(localStorage.getItem(selector));
	$(oFormValues).each(function () {
		$(selector + ' [name="' + this.name + '"]').val(this.value);
	});
	//to do: specific logic for radios
};

