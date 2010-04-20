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
// svn-look.php
//
// Svn bindings
//
// These binding currently use the svn command line to achieve their goal.	Once a proper
// SWIG binding has been produced for PHP, there'll be an option to use that instead.

require_once 'include/utils.php';

// {{{ Classes for retaining log information ---

$debugxml = false;

class SVNMod {
	var $action = '';
	var $copyfrom = '';
	var $copyrev = '';
	var $path = '';
}

class SVNListEntry {
	var $rev = 1;
	var $author = '';
	var $date = '';
	var $committime;
	var $age = '';
	var $file = '';
	var $isdir = false;
}

class SVNList {
	var $entries; // Array of entries
	var $curEntry; // Current entry

	var $path = ''; // The path of the list
}

class SVNLogEntry {
	var $rev = 1;
	var $author = '';
	var $date = '';
	var $committime;
	var $age = '';
	var $msg = '';
	var $path = '';

	var $mods;
	var $curMod;
}

function SVNLogEntry_compare($a, $b) {
	return strnatcasecmp($a->path, $b->path);
}

class SVNLog {
	var $entries; // Array of entries
	var $curEntry; // Current entry

	var $path = ''; // Temporary variable used to trace path history

	// findEntry
	//
	// Return the entry for a given revision

	function findEntry($rev) {
		foreach ($this->entries as $index => $entry) {
			if ($entry->rev == $rev) {
				return $index;
			}
		}
	}
}

// }}}

// {{{ XML parsing functions---

$curTag = '';

$curList = 0;

// {{{ listStartElement

function listStartElement($parser, $name, $attrs) {
	global $curList, $curTag, $debugxml;

	switch ($name) {
		case 'LIST':
			if ($debugxml) print 'Starting list'."\n";

			if (count($attrs)) {
				while (list($k, $v) = each($attrs)) {
					switch ($k) {
						case 'PATH':
							if ($debugxml) print 'Path '.$v."\n";
							$curList->path = $v;
							break;
					}
				}
			}
			break;

		case 'ENTRY':
			if ($debugxml) print 'Creating new entry'."\n";
			$curList->curEntry = new SVNListEntry;

			if (count($attrs)) {
				while (list($k, $v) = each($attrs)) {
					switch ($k) {
						case 'KIND':
							if ($debugxml) print 'Kind '.$v."\n";
							$curList->curEntry->isdir = ($v == 'dir');
							break;
					}
				}
			}
			break;

		case 'COMMIT':
			if ($debugxml) print 'Commit'."\n";

			if (count($attrs)) {
				while (list($k, $v) = each($attrs)) {
					switch ($k) {
						case 'REVISION':
							if ($debugxml) print 'Revision '.$v."\n";
							$curList->curEntry->rev = $v;
							break;
					}
				}
			}
			break;

		default:
			$curTag = $name;
			break;
	}
}

// }}}

// {{{ listEndElement

function listEndElement($parser, $name) {
	global $curList, $debugxml, $curTag;

	switch ($name) {
		case 'ENTRY':
			if ($debugxml) print 'Ending new list entry'."\n";
			if ($curList->curEntry->isdir) {
				$curList->curEntry->file .= '/';
			}
			$curList->entries[] = $curList->curEntry;
			$curList->curEntry = null;
			break;
	}

	$curTag = '';
}

// }}}

// {{{ listCharacterData

function listCharacterData($parser, $data) {
	global $curList, $curTag, $debugxml;

	switch ($curTag) {
		case 'NAME':
			if ($debugxml) print 'Name: '.$data."\n";
			if ($data === false || $data === '') return;
			$curList->curEntry->file .= $data;
			break;

		case 'AUTHOR':
			if ($debugxml) print 'Author: '.$data."\n";
			if ($data === false || $data === '') return;
			if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding'))
				$data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data));
			$curList->curEntry->author .= $data;
			break;

		case 'DATE':
			if ($debugxml) print 'Date: '.$data."\n";
			$data = trim($data);
			if ($data === false || $data === '') return;

			$committime = parseSvnTimestamp($data);
			$curList->curEntry->committime = $committime;
			$curList->curEntry->date = strftime('%Y-%m-%d %H:%M:%S', $committime);
			$curList->curEntry->age = datetimeFormatDuration(max(time() - $committime, 0), true, true);
			break;
	}
}

// }}}

$curLog = 0;

// {{{ logStartElement

function logStartElement($parser, $name, $attrs) {
	global $curLog, $curTag, $debugxml;

	switch ($name) {
		case 'LOGENTRY':
			if ($debugxml) print 'Creating new log entry'."\n";
			$curLog->curEntry = new SVNLogEntry;
			$curLog->curEntry->mods = array();

			$curLog->curEntry->path = $curLog->path;

			if (count($attrs)) {
				while (list($k, $v) = each($attrs)) {
					switch ($k) {
						case 'REVISION':
							if ($debugxml) print 'Revision '.$v."\n";
							$curLog->curEntry->rev = $v;
							break;
					}
				}
			}
			break;

		case 'PATH':
			if ($debugxml) print 'Creating new path'."\n";
			$curLog->curEntry->curMod = new SVNMod;

			if (count($attrs)) {
				while (list($k, $v) = each($attrs)) {
					switch ($k) {
						case 'ACTION':
							if ($debugxml) print 'Action '.$v."\n";
							$curLog->curEntry->curMod->action = $v;
							break;

						case 'COPYFROM-PATH':
							if ($debugxml) print 'Copy from: '.$v."\n";
							$curLog->curEntry->curMod->copyfrom = $v;
							break;

						case 'COPYFROM-REV':
							$curLog->curEntry->curMod->copyrev = $v;
							break;
					}
				}
			}

			$curTag = $name;
			break;

		default:
			$curTag = $name;
			break;
	}
}

// }}}

// {{{ logEndElement

function logEndElement($parser, $name) {
	global $curLog, $debugxml, $curTag;

	switch ($name) {
		case 'LOGENTRY':
			if ($debugxml) print 'Ending new log entry'."\n";
			$curLog->entries[] = $curLog->curEntry;
			break;

		case 'PATH':
			if ($debugxml) print 'Ending path'."\n";
			$curLog->curEntry->mods[] = $curLog->curEntry->curMod;
			break;

		case 'MSG':
			$curLog->curEntry->msg = trim($curLog->curEntry->msg);
			if ($debugxml) print 'Completed msg = "'.$curLog->curEntry->msg.'"'."\n";
			break;
	}

	$curTag = '';
}

// }}}

// {{{ logCharacterData

function logCharacterData($parser, $data) {
	global $curLog, $curTag, $debugxml;

	switch ($curTag) {
		case 'AUTHOR':
			if ($debugxml) print 'Author: '.$data."\n";
			if ($data === false || $data === '') return;
			if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding'))
				$data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data));
			$curLog->curEntry->author .= $data;
			break;

		case 'DATE':
			if ($debugxml) print 'Date: '.$data."\n";
			$data = trim($data);
			if ($data === false || $data === '') return;

			$committime = parseSvnTimestamp($data);
			$curLog->curEntry->committime = $committime;
			$curLog->curEntry->date = strftime('%Y-%m-%d %H:%M:%S', $committime);
			$curLog->curEntry->age = datetimeFormatDuration(max(time() - $committime, 0), true, true);
			break;

		case 'MSG':
			if ($debugxml) print 'Msg: '.$data."\n";
			if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding'))
				$data = mb_convert_encoding($data, 'UTF-8', mb_detect_encoding($data));
			$curLog->curEntry->msg .= $data;
			break;

		case 'PATH':
			if ($debugxml) print 'Path name: '.$data."\n";
			$data = trim($data);
			if ($data === false || $data === '') return;

			$curLog->curEntry->curMod->path .= $data;

			// The XML returned when a file is renamed/branched in inconsistent.
			// In the case of a branch, the path doesn't include the leafname.
			// In the case of a rename, it does.	Ludicrous.

			if (!empty($curLog->path)) {
				$pos = strrpos($curLog->path, '/');
				$curpath = substr($curLog->path, 0, $pos);
				$leafname = substr($curLog->path, $pos + 1);
			} else {
				$curpath = '';
				$leafname = '';
			}

			$curMod = $curLog->curEntry->curMod;
			if ($curMod->action == 'A') {
				if ($debugxml) print 'Examining added path "'.$curMod->copyfrom.'" - Current path = "'.$curpath.'", leafname = "'.$leafname.'"'."\n";
				if ($data == $curLog->path) {
					// For directories and renames
					$curLog->path = $curMod->copyfrom;
				} else if ($data == $curpath || $data == $curpath.'/') {
					// Logs of files that have moved due to branching
					$curLog->path = $curMod->copyfrom.'/'.$leafname;
				} else {
					$curLog->path = str_replace($curMod->path, $curMod->copyfrom, $curLog->path);
				}
				if ($debugxml) print 'New path for comparison: "'.$curLog->path.'"'."\n";
			}
			break;
	}
}

// }}}

// }}}

// {{{ internal functions (_topLevel and _listSort)

// Function returns true if the give entry in a directory tree is at the top level

function _topLevel($entry) {
	// To be at top level, there must be one space before the entry
	return (strlen($entry) > 1 && $entry{0} == ' ' && $entry{1} != ' ');
}

// Function to sort two given directory entries.
// Directories go at the top if config option alphabetic is not set

function _listSort($e1, $e2) {
	global $config;

	$file1 = $e1->file;
	$file2 = $e2->file;
	$isDir1 = ($file1{strlen($file1) - 1} == '/');
	$isDir2 = ($file2{strlen($file2) - 1} == '/');

	if (!$config->isAlphabeticOrder()) {
		if ($isDir1 && !$isDir2) return -1;
		if ($isDir2 && !$isDir1) return 1;
	}

	if ($isDir1) $file1 = substr($file1, 0, -1);
	if ($isDir2) $file2 = substr($file2, 0, -1);

	return strnatcasecmp($file1, $file2);
}

// }}}

// {{{ encodePath

// Function to encode a URL without encoding the /'s

function encodePath($uri) {
	global $config;

	$uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);
	if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
		$uri = mb_convert_encoding($uri, 'UTF-8', mb_detect_encoding($uri));
	}

	$parts = explode('/', $uri);
	$partscount = count($parts);
	for ($i = 0; $i < $partscount; $i++) {
		// do not urlencode the 'svn+ssh://' part!
		if ($i != 0 || $parts[$i] != 'svn+ssh:') {
			$parts[$i] = rawurlencode($parts[$i]);
		}
	}

	$uri = implode('/', $parts);

	// Quick hack. Subversion seems to have a bug surrounding the use of %3A instead of :

	$uri = str_replace('%3A', ':', $uri);

	// Correct for Window share names
	if ($config->serverIsWindows) {
		if (substr($uri, 0, 2) == '//') {
			$uri = '\\'.substr($uri, 2, strlen($uri));
		}

		if (substr($uri, 0, 10) == 'file://///' ) {
			$uri = 'file:///\\'.substr($uri, 10, strlen($uri));
		}
	}

	return $uri;
}

// }}}

// The SVNRepository class

class SVNRepository {
	var $repConfig;
	var $geshi = null;

	function SVNRepository($repConfig) {
		$this->repConfig = $repConfig;
	}

	// {{{ highlightLine
	//
	// Distill line-spanning syntax highlighting so that each line can stand alone
	// (when invoking on the first line, $attributes should be an empty array)
	// Invoked to make sure all open syntax highlighting tags (<font>, <i>, <b>, etc.)
	// are closed at the end of each line and re-opened on the next line

	function highlightLine($line, &$attributes) {
		$hline = '';

		// Apply any highlighting in effect from the previous line
		foreach ($attributes as $attr) {
			$hline .= $attr['text'];
		}

		// append the new line
		$hline .= $line;

		// update attributes
		for ($line = strstr($line, '<'); $line; $line = strstr(substr($line, 1), '<')) {
			if (substr($line, 1, 1) == '/') {
				// if this closes a tag, remove most recent corresponding opener
				$tagNamLen = strcspn($line, '> '."\t", 2);
				$tagNam = substr($line, 2, $tagNamLen);
				foreach (array_reverse(array_keys($attributes)) as $k) {
					if ($attributes[$k]['tag'] == $tagNam) {
						unset($attributes[$k]);
						break;
					}
				}
			} else {
				// if this opens a tag, add it to the list
				$tagNamLen = strcspn($line, '> '."\t", 1);
				$tagNam = substr($line, 1, $tagNamLen);
				$tagLen = strcspn($line, '>') + 1;
				$attributes[] = array('tag' => $tagNam, 'text' => substr($line, 0, $tagLen));
			}
		}

		// close any still-open tags
		foreach (array_reverse($attributes) as $attr) {
			$hline .= '</'.$attr['tag'].'>';
		}

		// XXX: this just simply replaces [ and ] with their entities to prevent
		//		it from being parsed by the template parser; maybe something more
		//		elegant is in order?
		$hline = str_replace('[', '&#91;', str_replace(']', '&#93;', $hline) );
		return $hline;
	}

	// }}}

	// Private function to simplify creation of common SVN command string text.
	function svnCommandString($command, $path, $rev, $peg) {
		global $config;
		return $config->getSvnCommand().' '.$command.' '.$this->repConfig->svnParams().($rev ? '-r '.$rev.' ' : '').quote(encodePath($this->getSvnPath($path)).'@'.($peg ? $peg : ''));
	}

	// Private function to simplify creation of enscript command string text.
	function enscriptCommandString($l) {
		global $config;
		return $config->enscript.' --language=html '.($l ? '--color --'.(!$config->getUseEnscriptBefore_1_6_3() ? 'highlight' : 'pretty-print').'='.$l : '').' -o -';
	}

	// {{{ getFileContents
	//
	// Dump the content of a file to the given filename

	function getFileContents($path, $filename, $rev = 0, $peg = '', $pipe = '', $highlight = 'file') {
		global $config, $extEnscript;
		assert ($highlight == 'file' || $highlight == 'no' || $highlight == 'line');

		$highlighted = false;

		// If there's no filename, just deliver the contents as-is to the user
		if ($filename == '') {
			$cmd = $this->svnCommandString('cat', $path, $rev, $peg);
			passthruCommand($cmd.' '.$pipe);
			return $highlighted;
		}

		// Get the file contents info

		$ext = strrchr($path, '.');

		$tempname = $filename;
		if ($highlight == 'line') {
			$tempname = tempnam($config->getTempDir(), '');
		}
		$highlighted = true;
		if ($highlight != 'no' && $config->useGeshi && $geshiLang = $this->highlightLanguageUsingGeshi($ext)) {
			$this->applyGeshi($path, $tempname, $geshiLang, $rev, $peg);
		} else if ($highlight != 'no' && $config->useEnscript) {
			// Get the files, feed it through enscript, then remove the enscript headers using sed
			// Note that the sed command returns only the part of the file between <PRE> and </PRE>.
			// It's complicated because it's designed not to return those lines themselves.
			$l = @$extEnscript[$ext];
			$cmd = $this->svnCommandString('cat', $path, $rev, $peg);
			$cmd = quoteCommand($cmd.' | '.$this->enscriptCommandString($l).' | '.
				$config->sed.' -n '.$config->quote.'1,/^<PRE.$/!{/^<\\/PRE.$/,/^<PRE.$/!p;}'.$config->quote.' > '.$tempname);
		} else {
			$highlighted = false;
			$cmd = $this->svnCommandString('cat', $path, $rev, $peg);
			$cmd = quoteCommand($cmd.' > '.quote($filename));
		}
		if (isset($cmd)) {
			$descriptorspec = array(2 => array('pipe', 'w')); // stderr
			$resource = proc_open($cmd, $descriptorspec, $pipes);
			$error = '';
			while (!feof($pipes[2])) {
				$error .= fgets($pipes[2]);
			}
			$error = trim($error);
			fclose($pipes[2]);
			proc_close($resource);

			if (!empty($error)) {
				global $lang;
				error_log($lang['BADCMD'].': '.escape($cmd));
				error_log($error);
				global $vars;
				$vars['warning'] = nl2br(escape(toOutputEncoding($error)));
			}
		}

		if ($highlighted && $highlight == 'line') {
			// If we need each line independently highlighted (e.g. for diff or blame)
			// then we'll need to filter the output of the highlighter
			// to make sure tags like <font>, <i> or <b> don't span lines

			$dst = fopen($filename, 'w');
			if ($dst) {
				$content = file_get_contents($tempname);
				$content = explode('<br />', $content);

				// $attributes is used to remember what highlighting attributes
				// are in effect from one line to the next
				$attributes = array(); // start with no attributes in effect

				foreach ($content as $line) {
					fputs($dst, $this->highlightLine(trim($line), $attributes)."\n");
				}
				fclose($dst);
			}
		}
		if ($tempname != $filename) {
			@unlink($tempname);
		}
		return $highlighted;
	}

	// }}}

	// {{{ highlightLanguageUsingGeshi
	//
	// check if geshi can highlight the given extension and return the language

	function highlightLanguageUsingGeshi($ext) {
		global $extGeshi;
		if (substr($ext, 0, 1) == '.') $ext = substr($ext, 1);

		foreach ($extGeshi as $language => $extensions) {
			if (in_array($ext, $extensions)) {
				if ($this->geshi === null) {
					require_once 'lib/geshi.php';
					$this->geshi = new GeSHi();
				} else {
					$this->geshi->error = false;
				}
				$this->geshi->set_language($language);
				if ($this->geshi->error() === false) {
					return $language;
				}
			}
		}
		return '';
	}

	// }}}

	// {{{ applyGeshi
	//
	// perform syntax highlighting using geshi

	function applyGeshi($path, $filename, $language, $rev, $peg = '', $return = false) {
		// Output the file to the filename
		$cmd = quoteCommand($this->svnCommandString('cat', $path, $rev, $peg).' > '.quote($filename));
		$descriptorspec = array(2 => array('pipe', 'w')); // stderr
		$resource = proc_open($cmd, $descriptorspec, $pipes);
		$error = '';
		while (!feof($pipes[2])) {
			$error .= fgets($pipes[2]);
		}
		$error = trim($error);
		fclose($pipes[2]);
		proc_close($resource);

		if (!empty($error)) {
			global $lang;
			error_log($lang['BADCMD'].': '.escape($cmd));
			error_log($error);
			global $vars;
			$vars['warning'] = 'Unable to cat file: '.nl2br(escape(toOutputEncoding($error)));
			return;
		}

		$source = file_get_contents($filename);
		if ($this->geshi === null) {
			require_once 'lib/geshi.php';
			$this->geshi = new GeSHi();
		}
		$this->geshi->set_source($source);
		$this->geshi->set_language($language);
		$this->geshi->set_header_type(GESHI_HEADER_NONE);
		$this->geshi->set_overall_class('geshi');
		$this->geshi->set_tab_width($this->repConfig->getExpandTabsBy());

		if ($return) {
			return $this->geshi->parse_code();
		} else {
			$f = @fopen($filename, 'w');
			fwrite($f, $this->geshi->parse_code());
			fclose($f);
		}
	}

	// }}}

	// {{{ listFileContents
	//
	// Print the contents of a file without filling up Apache's memory

	function listFileContents($path, $rev = 0, $peg = '') {
		global $config, $extEnscript;

		$ext = strrchr($path, '.');

		if ($config->useGeshi && $geshiLang = $this->highlightLanguageUsingGeshi($ext)) {
			$tempname = tempnam($config->getTempDir(), 'wsvn');
			print toOutputEncoding($this->applyGeshi($path, $tempname, $geshiLang, $rev, $peg, true));
			@unlink($tempname);
		} else {
			$pre = false;
			$cmd = $this->svnCommandString('cat', $path, $rev, $peg);
			if ($config->useEnscript) {
				$l = @$extEnscript[$ext];
				$cmd .= ' | '.$this->enscriptCommandString($l).' | '.
					$config->sed.' -n '.$config->quote.'/^<PRE.$/,/^<\\/PRE.$/p'.$config->quote;
			} else {
				$pre = true;
			}

			if ($result = popenCommand($cmd, 'r')) {
				if ($pre)
					echo '<pre>';
				while (!feof($result)) {
					$line = fgets($result, 1024);
					$line = toOutputEncoding($line);
					if ($pre) {
						$line = escape($line);
					}
					print hardspace($line);
				}
				if ($pre)
					echo '</pre>';
				pclose($result);
			}
		}
	}

	// }}}

	// {{{ getBlameDetails
	//
	// Dump the blame content of a file to the given filename

	function getBlameDetails($path, $filename, $rev = 0, $peg = '') {
		$cmd = quoteCommand($this->svnCommandString('blame', $path, $rev, $peg).' > '.quote($filename));
		$descriptorspec = array(2 => array('pipe', 'w')); // stderr
		$resource = proc_open($cmd, $descriptorspec, $pipes);
		$error = '';
		while (!feof($pipes[2])) {
			$error .= fgets($pipes[2]);
		}
		$error = trim($error);
		fclose($pipes[2]);
		proc_close($resource);

		if (!empty($error)) {
			global $lang;
			error_log($lang['BADCMD'].': '.escape($cmd));
			error_log($error);
			global $vars;
			$vars['warning'] = 'No blame info: '.nl2br(escape(toOutputEncoding($error)));
		}
	}

	// }}}

	function getProperties($path, $rev = 0, $peg = '') {
		$cmd = $this->svnCommandString('proplist', $path, $rev, $peg);
		$ret = runCommand($cmd, true);
		$properties = array();
		if (is_array($ret)) {
			foreach ($ret as $line) {
				if (substr($line, 0, 1) == ' ') {
					$properties[] = ltrim($line);
				}
			}
		}
		return $properties;
	}

	// {{{ getProperty

	function getProperty($path, $property, $rev = 0, $peg = '') {
		$cmd = $this->svnCommandString('propget '.$property, $path, $rev, $peg);
		$ret = runCommand($cmd, true);
		// Remove the surplus newline
		if (count($ret)) {
			unset($ret[count($ret) - 1]);
		}
		return implode("\n", $ret);
	}

	// }}}

	// {{{ exportDirectory
	//
	// Exports the directory to the given location

	function exportRepositoryPath($path, $filename, $rev = 0, $peg = '') {
		$cmd = $this->svnCommandString('export', $path, $rev, $peg).' '.quote($filename);
		$retcode = 0;
		execCommand($cmd, $retcode);
		if ($retcode != 0) {
			global $lang;
			error_log($lang['BADCMD'].': '.escape($cmd));
		}
		return $retcode;
	}

	// }}}

	// {{{ getList

	function getList($path, $rev = 0, $peg = '') {
		global $config, $curList;

		$xml_parser = xml_parser_create('UTF-8');
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($xml_parser, 'listStartElement', 'listEndElement');
		xml_set_character_data_handler($xml_parser, 'listCharacterData');

		// Since directories returned by svn log don't have trailing slashes (:-(), we need to remove
		// the trailing slash from the path for comparison purposes

		if ($path{strlen($path) - 1} == '/' && $path != '/') {
			$path = substr($path, 0, -1);
		}

		$curList = new SVNList;
		$curList->entries = array();
		$curList->path = $path;

		// Get the list info

		if ($rev == 0) {
			$headlog = $this->getLog('/', '', '', true, 1);
			if ($headlog && isset($headlog->entries[0]))
				$rev = $headlog->entries[0]->rev;
		}

		$cmd = quoteCommand($this->svnCommandString('list --xml', $path, $rev, $peg));

		$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));

		$resource = proc_open($cmd, $descriptorspec, $pipes);

		if (!is_resource($resource)) {
			global $lang;
			echo $lang['BADCMD'].': <code>'.escape(stripCredentialsFromCommand($cmd)).'</code>';
			exit;
		}

		$handle = $pipes[1];
		$firstline = true;
		while (!feof($handle)) {
			$line = fgets($handle);
			if (!xml_parse($xml_parser, $line, feof($handle))) {
				$errorMsg = sprintf('XML error: %s (%d) at line %d column %d byte %d'."\n".'cmd: %s',
									xml_error_string(xml_get_error_code($xml_parser)),
									xml_get_error_code($xml_parser),
									xml_get_current_line_number($xml_parser),
									xml_get_current_column_number($xml_parser),
									xml_get_current_byte_index($xml_parser),
									$cmd);
				if (xml_get_error_code($xml_parser) != 5) {
					// errors can contain sensitive info! don't echo this ~J
					error_log($errorMsg);
					exit;
				} else {
					break;
				}
			}
		}

		$error = '';
		while (!feof($pipes[2])) {
			$error .= fgets($pipes[2]);
		}
		$error = toOutputEncoding(trim($error));

		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		proc_close($resource);
		xml_parser_free($xml_parser);

		if (!empty($error)) {
			$error = toOutputEncoding(nl2br(str_replace('svn: ', '', $error)));
			global $lang;
			error_log($lang['BADCMD'].': '.escape($cmd));
			error_log($error);
			global $vars;
			if (strstr($error, 'found format')) {
				$vars['error'] = 'Repository uses a newer format than Subversion '.$config->getSubversionVersion().' can read. ("'.nl2br(escape(toOutputEncoding(substr($error, strrpos($error, 'Expected'))))).'.")';
			} else if (strstr($error, 'No such revision')) {
				$vars['warning'] = 'Revision '.escape($rev).' of this resource does not exist.';
			} else {
				$vars['error'] = $lang['BADCMD'].': <code>'.escape(stripCredentialsFromCommand($cmd)).'</code><br />'.nl2br(escape(toOutputEncoding($error)));
			}
			return null;
		}

		// Sort the entries into alphabetical order
		usort($curList->entries, '_listSort');
		return $curList;
	}

	// }}}

	// {{{ getLog

	function getLog($path, $brev = '', $erev = 1, $quiet = false, $limit = 2, $peg = '') {
		global $config, $curLog;

		$xml_parser = xml_parser_create('UTF-8');
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($xml_parser, 'logStartElement', 'logEndElement');
		xml_set_character_data_handler($xml_parser, 'logCharacterData');

		// Since directories returned by svn log don't have trailing slashes (:-(),
		// we must remove the trailing slash from the path for comparison purposes.
		if ($path != '/' && $path{strlen($path) - 1} == '/') {
			$path = substr($path, 0, -1);
		}

		$curLog = new SVNLog;
		$curLog->entries = array();
		$curLog->path = $path;

		// Get the log info
		$effectiveRev = ($brev && $erev ? $brev.':'.$erev : ($brev ? $brev.':1' : ''));
		$effectivePeg = ($peg ? $peg : ($brev ? $brev : ''));
		$cmd = quoteCommand($this->svnCommandString('log --xml '.($quiet ? '--quiet' : '--verbose'), $path, $effectiveRev, $effectivePeg));

		if (($config->subversionMajorVersion > 1 || $config->subversionMinorVersion >= 2) && $limit != 0) {
			$cmd .= ' --limit '.$limit;
		}

		$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));

		$resource = proc_open($cmd, $descriptorspec, $pipes);

		if (!is_resource($resource)) {
			global $lang;
			echo $lang['BADCMD'].': <code>'.escape(stripCredentialsFromCommand($cmd)).'</code>';
			exit;
		}

		$handle = $pipes[1];
		$firstline = true;
		while (!feof($handle)) {
			$line = fgets($handle);
			if (!xml_parse($xml_parser, $line, feof($handle))) {
				$errorMsg = sprintf('XML error: %s (%d) at line %d column %d byte %d'."\n".'cmd: %s',
									xml_error_string(xml_get_error_code($xml_parser)),
									xml_get_error_code($xml_parser),
									xml_get_current_line_number($xml_parser),
									xml_get_current_column_number($xml_parser),
									xml_get_current_byte_index($xml_parser),
									$cmd);
				if (xml_get_error_code($xml_parser) != 5) {
					// errors can contain sensitive info! don't echo this ~J
					error_log($errorMsg);
					exit;
				} else {
					break;
				}
			}
		}

		$error = '';
		while (!feof($pipes[2])) {
			$error .= fgets($pipes[2]);
		}
		$error = trim($error);

		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		proc_close($resource);

		if (!empty($error)) {
			global $lang;
			error_log($lang['BADCMD'].': '.escape($cmd));
			error_log($error);
			global $vars;
			if (strstr($error, 'found format')) {
				$vars['error'] = 'Repository uses a newer format than Subversion '.$config->getSubversionVersion().' can read. ("'.nl2br(escape(toOutputEncoding(substr($error, strrpos($error, 'Expected'))))).'.")';
			} else if (strstr($error, 'No such revision')) {
				$vars['warning'] = 'Revision '.escape($brev).' of this resource does not exist.';
			} else {
				$vars['error'] = $lang['BADCMD'].': <code>'.escape(stripCredentialsFromCommand($cmd)).'</code><br />'.nl2br(escape(toOutputEncoding($error)));
			}
			return null;
		}

		xml_parser_free($xml_parser);

		foreach ($curLog->entries as $entryKey => $entry) {
			$fullModAccess = true;
			$anyModAccess = (count($entry->mods) == 0);
			foreach ($entry->mods as $modKey => $mod) {
				$access = $this->repConfig->hasReadAccess($mod->path);
				if ($access) {
					$anyModAccess = true;
				} else {
					// hide modified entry when access is prohibited
					unset($curLog->entries[$entryKey]->mods[$modKey]);
					$fullModAccess = false;
				}
			}
			if (!$fullModAccess) {
				// hide commit message when access to any of the entries is prohibited
				$curLog->entries[$entryKey]->msg = '';
			}
			if (!$anyModAccess) {
				// hide author and date when access to all of the entries is prohibited
				$curLog->entries[$entryKey]->author = '';
				$curLog->entries[$entryKey]->date = '';
				$curLog->entries[$entryKey]->committime = '';
				$curLog->entries[$entryKey]->age = '';
			}
		}
		return $curLog;
	}

	// }}}

	function isFile($path, $rev = 0, $peg = '') {
		$cmd = $this->svnCommandString('info --xml', $path, $rev, $peg);
		return strpos(implode(' ', runCommand($cmd, true)), 'kind="file"') !== false;
	}

	// {{{ getSvnPath

	function getSvnPath($path) {
		if ($this->repConfig->subpath === null) {
			return $this->repConfig->path.$path;
		} else {
			$path = preg_replace('|^/?'.$this->repConfig->subpath.'|', '', $path);
			return $this->repConfig->path.'/'.$this->repConfig->subpath.$path;
		}
	}

	// }}}

}

// Initialize SVN version information by parsing from command-line output.
$ret = runCommand(str_replace('--non-interactive', '--version', $config->getSvnCommand()), false);
if (preg_match('~([0-9]?)\.([0-9]?)\.([0-9]?)~', $ret[0], $matches)) {
	$config->setSubversionVersion($matches[0]);
	$config->setSubversionMajorVersion($matches[1]);
	$config->setSubversionMinorVersion($matches[2]);
}
