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
// command.php
//
// External command handling

function detectCharacterEncoding($str) {
	$list = array(/*'ASCII',*/ 'UTF-8', 'ISO-8859-1');
	if (function_exists('mb_detect_encoding')) {
		// @see http://de3.php.net/manual/en/function.mb-detect-encoding.php#81936
		// why appending an 'a' and specifying an encoding list is necessary
		return mb_detect_encoding($str.'a', $list);

	} else if (function_exists('iconv')) {
		foreach ($list as $item) {
			$encstr = iconv($item, $item.'//TRANSLIT//IGNORE', $str);
			if (md5($encstr) == md5($str)) return $item;
		}
	}

	return null;
}

// {{{ toOutputEncoding

function toOutputEncoding($str) {
	$enc = detectCharacterEncoding($str);

	if ($enc !== null && function_exists('mb_convert_encoding')) {
		$str = mb_convert_encoding($str, 'UTF-8', $enc);

	} else if ($enc !== null && function_exists('iconv')) {
		$str = iconv($enc, 'UTF-8//TRANSLIT//IGNORE', $str);

	} else {
		// @see http://w3.org/International/questions/qa-forms-utf-8.html
		$isUtf8 = preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E]              # ASCII
			| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
			|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			)*$%xs', $str
		);
		if (!$isUtf8) $str = utf8_encode($str);
	}

	return $str;
}

// }}}

// {{{ escape
//
// Escape a string to output

function escape($str) {
	$entities = array();
	$entities['&'] = '&amp;';
	$entities['<'] = '&lt;';
	$entities['>'] = '&gt;';
	$entities['"'] = '&quot;';
	$entities['\''] = '&apos;';
	return str_replace(array_keys($entities), array_values($entities), $str);
}

// }}}

// {{{ quoteCommand

function quoteCommand($cmd) {
	global $config;

	// On Windows machines, the whole line needs quotes round it so that it's
	// passed to cmd.exe correctly

	if ($config->serverIsWindows) {
		$cmd = '"'.$cmd.'"';
	}

	return $cmd;
}

// }}}

// {{{ execCommand

function execCommand($cmd, &$retcode) {
	global $config;

	// On Windows machines, the whole line needs quotes round it so that it's
	// passed to cmd.exe correctly
	// Since php 5.3.0 the quoting seems to be done internally

	if ($config->serverIsWindows && version_compare(PHP_VERSION, '5.3.0alpha') === -1) {
		$cmd = '"'.$cmd.'"';
	}

	return @exec($cmd, $tmp, $retcode);
}

// }}}

// {{{ popenCommand

function popenCommand($cmd, $mode) {
	global $config;

	// On Windows machines, the whole line needs quotes round it so that it's
	// passed to cmd.exe correctly
	// Since php 5.3.0 the quoting seems to be done internally

	if ($config->serverIsWindows && version_compare(PHP_VERSION, '5.3.0alpha') === -1) {
		$cmd = '"'.$cmd.'"';
	}

	return popen($cmd, $mode);
}

// }}}

// {{{ passthruCommand

function passthruCommand($cmd) {
	global $config;

	// On Windows machines, the whole line needs quotes round it so that it's
	// passed to cmd.exe correctly
	// Since php 5.3.0 the quoting seems to be done internally

	if ($config->serverIsWindows && version_compare(PHP_VERSION, '5.3.0alpha') === -1) {
		$cmd = '"'.$cmd.'"';
	}

	return passthru($cmd);
}

// }}}

// {{{ runCommand

function runCommand($cmd, $mayReturnNothing = false) {
	global $lang;

	$output = array();
	$err = false;

	$c = quoteCommand($cmd);

	$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));

	$resource = proc_open($c, $descriptorspec, $pipes);
	$error = '';

	if (!is_resource($resource)) {
		echo '<p>'.$lang['BADCMD'].': <code>'.stripCredentialsFromCommand($cmd).'</code></p>';
		exit;
	}

	$handle = $pipes[1];
	$firstline = true;
	while (!feof($handle)) {
		$line = fgets($handle);
		if ($firstline && empty($line) && !$mayReturnNothing) {
			$err = true;
		}

		$firstline = false;
		$output[] = toOutputEncoding(rtrim($line));
	}

	while (!feof($pipes[2])) {
		$error .= fgets($pipes[2]);
	}

	$error = toOutputEncoding(trim($error));

	fclose($pipes[0]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	proc_close($resource);

	if (!$err) {
		return $output;
	} else {
		echo '<p>'.$lang['BADCMD'].': <code>'.stripCredentialsFromCommand($cmd).'</code></p><p>'.nl2br($error).'</p>';
	}
}

// }}}

function stripCredentialsFromCommand($cmd) {
	global $config;

	$quotingChar = ($config->serverIsWindows ? '"' : "'");
	$quotedString = $quotingChar.'([^'.$quotingChar.'\\\\]*(\\\\.[^'.$quotingChar.'\\\\]*)*)'.$quotingChar;
	$patterns = array('|--username '.$quotedString.' |U', '|--password '.$quotedString.' |U');
	$replacements = array('--username '.quote('***').' ', '--password '.quote('***').' ');
	$cmd = preg_replace($patterns, $replacements, $cmd, 1);

	return $cmd;
}

// {{{ quote
//
// Quote a string to send to the command line

function quote($str) {
	global $config;

	if ($config->serverIsWindows) {
		return '"'.$str.'"';
	} else {
		return escapeshellarg($str);
	}
}

// }}}
