/*
	star-light - version 1.0.2 (2005/06/06)
	Copyright 2005, Dean Edwards
	License: http://creativecommons.org/licenses/LGPL/2.1/
*/

/*    ---   (include) /my/src?fix-ie5.js       ---    */
/*    ---   (require) /my/src?ICommon.js       ---    */
/*    ---   (require) /my/src?ParseMaster.js   ---    */

// constants
function get_IGNORE(){return "$1"};
function get_LINE_COMMENT(){return /\/\/[^\n]*\n/};
function get_BLOCK_COMMENT(){return /\/\*[^*]*\*+([^\/][^*]*\*+)*\//};
function get_SGML_COMMENT(){return /<!\s*(--([^-]|[\r\n]|-[^-])*--\s*)>/};
function get_STRING1(){return /'[^']*'/};
function get_STRING2(){return /"[^"]*"/};
function get_NUMBER(){return /\b[+-]?(\d*\.?\d+|\d+\.?\d*)([eE][+-]?\d+)?\b/};

// read-only
function get_parser(){return _parser};

function refresh() {
	parse(true);
};

// initialise
tabStop = -1; // tabs to spaces
urls = false;
email = false;
userRefresh = false;
function parse(refresh) {
try {
	if (refresh || !userRefresh) {
		var $parsed = parser.exec();
		if ($parsed) innerHTML = $parsed;
	}
} catch ($ignore) {
}};

// constants
var $BASE_TAG = "<span>$1</span>";
var $ELEMENT_NODE = 1;  // DOM constant
var $TEXT_NODE = 3;
// internal references
var _parser = new ParseMaster;

// create and add a new pattern to the patterns collection
_parser.specialize({
	add: function($expression, $style, $replacement) {
		// if "expression" is a string, assume it's a keyword list
		// e.g. expression = "...each|else|end|for|goto..."
		if (typeof $expression == "string") $expression = new RegExp("\\b(" + $expression + ")\\b");
		// allow some patterns to be ignored during parsing
		//  the IGNORE flag is for readability only
		if ($style == IGNORE) $style = $replacement = "";
		else if ($style) $replacement = _stylise($replacement || $BASE_TAG, $style);
		// call the ancestor method to create the pattern
		this.inherit(new RegExp(_decode(String($expression).slice(1, -1))), $replacement || "$1");
	},
	// global init function (called from the HTC)
	exec: function() {
		// retrieve this element's text content
		var $text = _decode(_getText(element));
		// parse the text
		$text = this.inherit($text);
		// parse urls / emails
		$text = _parseUrls($text);
		// update this element's html content
		return _parseWhiteSpace(_encode($text));
	}
});

// add a style attribute to a replacement string (usually <span>text</span>)
function _stylise($replacement, $style) {
	return $replacement.replace(/>/, " style='" + $style + "'>");
};

// retrieve the element's text
function _getText($node) {
	var $text = "";
	// loop through text nodes
	var $childNodes = $node.childNodes;
	for (var i = 0; ($node = $childNodes[i]); i++) {
		switch ($node.nodeType) {
			case $ELEMENT_NODE:
				$text += ($node.tagName == "BR") ? "\r" : _getText($node);
				break;
			case $TEXT_NODE:
				$text += $node.nodeValue;
				break;
		}
	}
	// ensure that we have the same text for both platforms (mozilla/explorer)
	return $text.replace(/\r/g, "\n") + "\n";
};

// fix tabs and spaces
function _parseWhiteSpace($text) {
	var $leadingSpace = /(\n[&nbsp;]*) /g;
	while ($leadingSpace.test($text)) {
		$text = $text.replace($leadingSpace, "$1&nbsp;");
	}
	// fix tabs
	var $stop = "";
	if (tabStop > 0) {
		var $count = tabStop;
		while ($count--) $stop += "&nbsp;";
		$text = $text.replace(/\t/g, $stop);
	}
	// yuk. me no like :-(
	if (/MSIE/.test(navigator.appVersion)) {
		// get line-spacing working properly for IE
		$text = $text.replace(/\n(<\/\w+>)?/g, "$1<br>").replace(/<br><br>/g, "<p><br></p>");
	}
	return $text;
};

function _parseUrls($text) {
	if (urls) $text = $text.replace(/(http:\/\/+[\w\/\-%&#=.,?+$]+)/g, "<a href='$1'>$1</a>");
	if (email) $text = $text.replace(/([\w.-]+@[\w.-]+\.\w+)/g, "<a href='mailto:$1'>$1</a>");
	return $text;
};

// encode special characters
function _encode($text) {
	return $text.split("\x02").join("&lt;").split("\x03").join("&amp;");
};

// decode special characters
function _decode($text) {
	// patterns need "<" encoded
	return $text.split("<").join("\x02").split("&").join("\x03");
};