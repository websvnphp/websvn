/*
	ParseMaster, version 1.0 (pre-release) (2005/05/12) x6
	Copyright 2005, Dean Edwards
	Web: http://dean.edwards.name/

	This software is licensed under the CC-GNU LGPL
	Web: http://creativecommons.org/licenses/LGPL/2.1/
*/

/* a multi-pattern parser */

/*    ---   (include) http://dean.edwards.name/my/fix-ie5.js       ---    */
/*    ---   (require) http://dean.edwards.name/common/common.js       ---    */

function ParseMaster() {
	// constants
	var $EXPRESSION = 0, $REPLACEMENT = 1, $LENGTH = 2;
	// used to determine nesting levels
	var $GROUPS = /\(/g, $SUB_REPLACE = /\$\d/, $INDEXED = /^\$\d+$/,
	    $TRIM = /(['"])\1\+(.*)\+\1\1$/, $$ESCAPE = /\\./g, $QUOTE = /'/,
	    $$DELETED = /\001[^\001]*\001/g;
	function $DELETE($match, $offset){return "\001" + $match[$offset] + "\001"};
	// public
	this.add = function($expression, $replacement) {
		if (!$replacement) $replacement = $DELETE;
		// count the number of sub-expressions
		//  - add one because each pattern is itself a sub-expression
		var $length = (_internalEscape(String($expression)).match($GROUPS) || "").length + 1;
		// does the pattern deal with sub-expressions?
		if (typeof $replacement == "string" && $SUB_REPLACE.test($replacement)) {
			// a simple lookup? (e.g. "$2")
			if ($INDEXED.test($replacement)) {
				// store the index (used for fast retrieval of matched strings)
				$replacement = parseInt($replacement.slice(1)) - 1;
			} else { // a complicated lookup (e.g. "Hello $2 $1")
				// build a function to do the lookup
				var i = $length;
				var $quote = $QUOTE.test(_internalEscape($replacement)) ? '"' : "'";
				while (i) $replacement = $replacement.split("$" + i--).join($quote + "+a[o+" + i + "]+" + $quote);
				$replacement = new Function("a,o", "return" + $quote + $replacement.replace($TRIM, "$1") + $quote);
			}
		}
		// pass the modified arguments
		_add($expression || "/^$/", $replacement, $length);
	};
	// execute the global replacement
	this.exec = function($string) {
		return _unescape(_escape($string, this.escapeChar).replace(
			new RegExp(_patterns, this.ignoreCase ? "gi" : "g"), _replacement), this.escapeChar).replace($$DELETED, "");
	};
	// clear the patterns collections so that this object may be re-used
	this.reset = function() {
		_patterns.length = 0;
	};

	// private
	var _patterns = [];   // patterns stored by index
	var _toString = function(){return "(" + String(this[$EXPRESSION]).slice(1, -1) + ")"};
	_patterns.toString = function(){return this.join("|")};
	// create and add a new pattern to the patterns collection
	function _add() {
		arguments.toString = _toString;
		// store the pattern - as an arguments object (i think this is quicker..?)
		_patterns[_patterns.length] = arguments;
	}
	// this is the global replace function (it's quite complicated)
	function _replacement() {
		if (!arguments[0]) return "";
		var i = 1, j = 0, $pattern;
		// loop through the patterns
		while ($pattern = _patterns[j++]) {
			// do we have a result?
			if (arguments[i]) {
				var $replacement = $pattern[$REPLACEMENT];
				switch (typeof $replacement) {
					case "function": return $replacement(arguments, i);
					case "number": return arguments[$replacement + i];
					default: return $replacement;
				}
			// skip over references to sub-expressions
			} else i += $pattern[$LENGTH];
		}
	};
	// encode escaped characters
	var _escaped = [];
	function _escape($string, $escapeChar) {
		return $escapeChar ? $string.replace(new RegExp("\\" + $escapeChar + "(.)", "g"), function($match, $char) {
			_escaped[_escaped.length] = $char;
			return $escapeChar;
		}) : $string;
	};
	// decode escaped characters
	function _unescape($string, $escapeChar) {
		var i = 0;
		return $escapeChar ? $string.replace(new RegExp("\\" + $escapeChar, "g"), function() {
			return $escapeChar + (_escaped[i++] || "");
		}) : $string;
	};
	function _internalEscape($string) {
		return $string.replace($$ESCAPE, "");
	};
};
Common.specialize({
	constructor: ParseMaster,
	ignoreCase: false,
	escapeChar: ""
});
