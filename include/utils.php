<?php
// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004-2006 Tim Armes
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
//
// --
//
// utils.php
//
// General utility commands

// {{{ createDirLinks
//
// Create a list of links to the current path that'll be available from the template

function createPathLinks($rep, $path, $rev, $peg = '') {
	global $vars, $config;

	$pathComponents = explode('/', htmlentities($path, ENT_QUOTES, 'UTF-8'));
	$count = count($pathComponents);

	// The number of links depends on the last item.	It's empty if we're looking
	// at a directory, and non-empty if we're looking at a file.
	if (empty($pathComponents[$count - 1])) {
		$limit = $count - 2;
		$dir = true;
	} else {
		$limit = $count - 1;
		$dir = false;
	}

	$passRevString = createRevAndPegString($rev, $peg);

	$pathSoFar = '/';
	$pathSoFarURL = $config->getURL($rep, $pathSoFar, 'dir').$passRevString;
	$vars['pathlinks'] = '<a href="'.$pathSoFarURL.'" class="root"><span>(root)</span></a>/';

	for ($n = 1; $n < $limit; $n++) {
		$pathSoFar .= html_entity_decode($pathComponents[$n]).'/';
		$pathSoFarURL = $config->getURL($rep, $pathSoFar, 'dir').$passRevString;
		$vars['pathlinks'] .= '<a href="'.$pathSoFarURL.'#'.anchorForPath($pathSoFar).'">'.$pathComponents[$n].'</a>/';
	}

	if (!empty($pathComponents[$n])) {
		$pegrev = ($peg) ? ' <a class="peg" href="'.'?'.htmlspecialchars(str_replace('&peg='.$peg, '', $_SERVER['QUERY_STRING']), ENT_NOQUOTES).'">@ '.$peg.'</a>' : '';
		if ($dir) {
			$vars['pathlinks'] .= '<span class="dir">'.$pathComponents[$n].'/'.$pegrev.'</span>';
		} else {
			$vars['pathlinks'] .= '<span class="file">'.$pathComponents[$n].$pegrev.'</span>';
		}
	}
}

// }}}

function createRevAndPegString($rev, $peg) {
	$params = array();
	if ($rev) $params[] = 'rev='.$rev;
	if ($peg) $params[] = 'peg='.$peg;
	return implode('&amp;', $params);
}

function anchorForPath($path) {
	global $config;

	// (X)HMTL id/name attribute must be this format: [A-Za-z][A-Za-z0-9-_.:]*
	// MD5 hashes are 32 characters, deterministic, quite collision-resistant,
	// and work for any string, regardless of encoding or special characters.
	if ($config->treeView)
		return 'a'.md5($path);
	else
		return '';
}

// {{{ create_anchors
//
// Create links out of http:// and mailto: tags

// TODO: the target="_blank" nonsense should be optional (or specified by the template)
function create_anchors($text) {
	$ret = $text;

	// Match correctly formed URLs that aren't already links
	$ret = preg_replace('#\b(?<!href=")([a-z]+?)://(\S*)([\w/]+)#i',
								'<a href="\\1://\\2\\3" target="_blank">\\1://\\2\\3</a>',
								$ret);

	// Now match anything beginning with www, as long as it's not //www since they were matched above
	$ret = preg_replace('#\b(?<!//)www\.(\S*)([\w/]+)#i',
								'<a href="http://www.\\1\\2" target="_blank">www.\\1\\2</a>',
								$ret);

	// Match email addresses
	$ret = preg_replace('#\b([\w\-_.]+)@([\w\-.]+)\b#i',
								'<a href="mailto:\\1@\\2">\\1@\\2</a>',
								$ret);

	return $ret;
}

// }}}

// {{{ getFullURL

function getFullURL($loc) {
	$protocol = 'http';

	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		$protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
	} else if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
		$protocol = 'https';
	}

	$port = ':'.$_SERVER['SERVER_PORT'];
	if ((':80' == $port && 'http' == $protocol) || (':443' == $port && 'https' == $protocol)) {
		$port = '';
	}

	if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
		$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else if (isset($_SERVER['HTTP_HOST'])) {
		$host = $_SERVER['HTTP_HOST'];
	} else if (isset($_SERVER['SERVER_NAME'])) {
		$host = $_SERVER['SERVER_NAME'].$port;
	} else if (isset($_SERVER['SERVER_ADDR'])) {
		$host = $_SERVER['SERVER_ADDR'].$port;
	} else {
		print 'Unable to redirect';
		exit;
	}

	// make sure we have a directory to go to
	if (empty($loc)) {
		$loc = '/';
	} else if ($loc{0} != '/') {
		$loc = '/'.$loc;
	}

	$url = $protocol . '://' . $host . $loc;

	return $url;
}

// }}}

function xml_entities($str) {
	$entities = array();
	$entities['&'] = '&amp;';
	$entities['<'] = '&lt;';
	$entities['>'] = '&gt;';
	$entities['"'] = '&quot;';
	$entities['\''] = '&apos;';
	return str_replace(array_keys($entities), array_values($entities), $str);
}

// {{{ hardspace
//
// Replace the spaces at the front of a line with hard spaces

// XXX: this is an unnecessary function; you can prevent whitespace from being
//		trimmed via CSS (use the "white-space: pre;" properties). ~J
// in the meantime, here's an improved function (does nothing)

function hardspace($s) {
	return '<code>' . expandTabs($s) . '</code>';
}

// }}}

function wrapInCodeTagIfNecessary($string) {
	global $config;
	return ($config->getUseGeshi()) ? $string : '<code>'.$string.'</code>';
}

// {{{ expandTabs

/**
 * Expands the tabs in a line that may or may not include HTML.
 *
 * Enscript generates code with HTML, so we need to take that into account.
 *
 * @param string $s Line of possibly HTML-encoded text to expand
 * @param int $tabwidth Tab width, -1 to use repository's default, 0 to collapse
 *							 all tabs.
 * @return string The expanded line.
 * @since 2.1
 */

function expandTabs($s, $tabwidth = - 1) {
	global $rep;

	if ($tabwidth == -1) {
		$tabwidth = $rep->getExpandTabsBy();
	}
	$pos = 0;

	// Parse the string into chunks that are either 1 of: HTML tag, tab char, run of any other stuff
	$chunks = preg_split('/((?:<.+?>)|(?:&.+?;)|(?:\t))/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);

	// Count the sizes of the chunks and replace tabs as we go
	$chunkscount = count($chunks);
	for ($i = 0; $i < $chunkscount; $i++) {
		// make sure we're not dealing with an empty string
		if (empty($chunks[$i])) continue;
		switch ($chunks[$i]{0}) {
			case '<': // HTML tag: ignore its width by doing nothing
				break;

			case '&': // HTML entity: count its width as 1 char
				$pos++;
				break;

			case "\t": // Tab char: replace it with a run of spaces between length tabwidth and 1
				$tabsize = $tabwidth - ($pos % $tabwidth);
				$chunks[$i] = str_repeat(' ', $tabsize);
				$pos += $tabsize;
				break;

			default: // Anything else: just keep track of its width
				$pos +=	strlen($chunks[$i]);
				break;
		}
	}

	// Put the chunks back together and we've got the original line, detabbed.
	return join('', $chunks);
}

// }}}

// {{{ datetimeFormatDuration
//
// Formats a duration of seconds for display.
//
// $seconds the number of seconds until something
// $nbsp true if spaces should be replaced by nbsp
// $skipSeconds true if seconds should be omitted
//
// return the formatted duration (e.g. @c "8h	6m	1s")

function datetimeFormatDuration($seconds, $nbsp = false, $skipSeconds = false) {
	global $lang;

	$neg = false;
	if ($seconds < 0) {
		$seconds = 0 - $seconds;
		$neg = true;
	}

	$qty = array();
	$names = array($lang['DAYLETTER'], $lang['HOURLETTER'], $lang['MINUTELETTER']);

	$qty[] = (int)($seconds / (60 * 60 * 24));
	$seconds %= 60 * 60 * 24;

	$qty[] = (int)($seconds / (60 * 60));
	$seconds %= 60 * 60;

	$qty[] = (int)($seconds / 60);

	if (!$skipSeconds) {
		$qty[] = (int)($seconds % 60);
		$names[] = $lang['SECONDLETTER'];
	}

	$text = $neg ? '-' : '';
	$any = false;
	$count = count($names);
	$parts = 0;
	for ($i = 0; $i < $count; $i++) {
		// If a "higher valued" time slot had a value or this time slot
		// has a value or this is the very last entry (i.e. all values
		// are 0 and we still want to print seconds)
		if ($any || $qty[$i] > 0 || $i == $count - 1) {
			if ($any) $text .= $nbsp ? '&nbsp;' : ' ';
			if ($any && $qty[$i] < 10) $text .= '0';
			$text .= $qty[$i].$names[$i];
			$any = true;
			$parts++;
			if ($parts >= 2) break;
		}
	}
	return $text;
}

// }}}

function parseSvnTimestamp($dateString) {
	// Try the simple approach of a built-in PHP function first.
	$date = strtotime($dateString);
	// If the resulting timestamp isn't sane, try parsing manually.
	if ($date <= 0) {
		$y = 0;
		$mo = 0;
		$d = 0;
		$h = 0;
		$m = 0;
		$s = 0;
		sscanf($dateString, '%d-%d-%dT%d:%d:%d.', $y, $mo, $d, $h, $m, $s);
		
		$mo = substr('00'.$mo, -2);
		$d = substr('00'.$d, -2);
		$h = substr('00'.$h, -2);
		$m = substr('00'.$m, -2);
		$s = substr('00'.$s, -2);
		$date = strtotime($y.'-'.$mo.'-'.$d.' '.$h.':'.$m.':'.$s.' GMT');
	}
	return $date;
}

// {{{ buildQuery
//
// Build parameters for url query part

function buildQuery($data, $separator = '&amp;', $key = '') {
	if (is_object($data))
		$data = get_object_vars($data);
	$p = array();
	foreach ($data as $k => $v) {
		$k = urlencode($k);
		if (!empty($key))
			$k = $key.'['.$k.']';
		if (is_array($v) || is_object($v)) {
			$p[] = buildQuery($v, $separator, $k);
		} else {
			$p[] = $k.'='.urlencode($v);
		}
	}
	return implode($separator, $p);
}

// }}}

// {{{ getUserLanguage

function getUserLanguage($languages, $default, $userchoice) {
	global $config;
	if (!$config->useAcceptedLanguages()) return $default;

	$acceptlangs = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : false;
	if (!$acceptlangs)
		return $default;

	$langs = array();
	$sublangs = array();

	foreach (explode(',', $acceptlangs) as $str) {
		$a = explode(';', $str, 2);
		$lang = trim($a[0]);
		$pos = strpos($lang, '-');
		if ($pos !== false)
			$sublangs[] = substr($lang, 0, $pos);
		$q = 1.0;
		if (count($a) == 2) {
			$v = trim($a[1]);
			if (substr($v, 0, 2) == 'q=')
				$q = doubleval(substr($v, 2));
		}
		if ($userchoice)
			$q *= 0.9;
		$langs[$lang] = $q;
	}

	foreach ($sublangs as $l)
		if (!isset($langs[$l]))
			$langs[$l] = 0.1;

	if ($userchoice)
		$langs[$userchoice] = 1.0;

	arsort($langs);
	foreach ($langs as $code => $q) {
		if (isset($languages[$code])) {
			return $code;
		}
	}

	return $default;
}

// }}}
