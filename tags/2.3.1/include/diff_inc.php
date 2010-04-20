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
// diff_inc.php
//
// Diff to files

ini_set('include_path', $locwebsvnreal.'/lib/pear'.$config->pathSeparator.ini_get('include_path'));
@include_once 'Text/Diff.php';
@include_once 'Text/Diff/Renderer.php';
@include_once 'Text/Diff/Renderer/unified.php';
include_once 'include/diff_util.php';

$arrayBased = false;
$fileBased = false;

class ListingHelper {
	var $_listing = array();
	var $_index = 0;
	var $_blockStart = false;

	function _add($text1, $lineno1, $class1, $text2, $lineno2, $class2) {
		$listing = &$this->_listing;
		$index = &$this->_index;

		$listing[$index]['rev1diffclass'] = $class1;
		$listing[$index]['rev2diffclass'] = $class2;

		$listing[$index]['rev1line'] = $text1;
		$listing[$index]['rev2line'] = $text2;

		$listing[$index]['rev1lineno'] = $lineno1;
		$listing[$index]['rev2lineno'] = $lineno2;
		$listing[$index]['startblock'] = $this->_blockStart;
		$this->_blockStart = false;
		$index++;
	}

	function addDeletedLine($text, $lineno) {
		$this->_add($text, $lineno, 'diffdeleted', '&nbsp;', '-', 'diffempty');
	}

	function addAddedLine($text, $lineno) {
		$this->_add('&nbsp;', '-', 'diffempty', $text, $lineno, 'diffadded');
	}

	function addChangedLine($text1, $lineno1, $text2, $lineno2) {
		$this->_add($text1, $lineno1, 'diffchanged', $text2, $lineno2, 'diffchanged');
	}

	// note that $text1 do not need to be equal $text2 if $ignoreWhitespace is true
	function addLine($text1, $lineno1, $text2, $lineno2) {
		$this->_add($text1, $lineno1, 'diff', $text2, $lineno2, 'diff');
	}

	function startNewBlock() {
		$this->_blockStart = true;
	}

	function getListing() {
		return $this->_listing;
	}
}

function nextLine(&$obj) {
	global $arrayBased, $fileBased;
	if ($arrayBased) return array_shift($obj);
	if ($fileBased) return fgets($obj);
	return '';
}

function endOfFile(&$obj) {
	global $arrayBased, $fileBased;
	if ($arrayBased) return count($obj) == 0;
	if ($fileBased) return feof($obj);
	return true;
}


function getWrappedLineFromFile($file, $is_highlighted) {
	$line = fgets($file);
	if ($line === false) return false;
	$line = rtrim($line);
	$line = toOutputEncoding($line);
	if (!$is_highlighted) {
		$line = escape($line);
	}
	if (strip_tags($line) === '') $line = '&nbsp;';
	return wrapInCodeTagIfNecessary($line);
}

function diff_result($all, $highlighted, $newtname, $oldtname, $obj, $ignoreWhitespace) {
	$ofile = fopen($oldtname, 'r');
	$nfile = fopen($newtname, 'r');

	// Get the first real line
	$line = nextLine($obj);

	$index = 0;
	$listingHelper = new ListingHelper();

	$curoline = 1;
	$curnline = 1;

	$sensibleLineChanges = new SensibleLineChanges(new LineDiff($ignoreWhitespace));

	while (!endOfFile($obj)) {
		// Get the first line of this range
		$oline = 0;
		sscanf($line, '@@ -%d', $oline);
		$line = substr($line, strpos($line, '+'));
		$nline = 0;
		sscanf($line, '+%d', $nline);

		while ($curoline < $oline || $curnline < $nline) {
			if ($curoline < $oline) {
				$text1 = getWrappedLineFromFile($ofile, $highlighted);
				$tmpoline = $curoline;
				$curoline++;
			} else {
				$tmpoline = '?';
				$text1 = '&nbsp';
			}

			if ($curnline < $nline) {
				$text2 = getWrappedLineFromFile($nfile, $highlighted);
				$tmpnline = $curnline;
				$curnline++;
			} else {
				$tmpnline = '?';
				$text2 = '&nbsp;';
			}

			if ($all) {
				$listingHelper->addLine($text1, $tmpoline, $text2, $tmpnline);
			}
		}

		if (!$all) {
			$listingHelper->startNewBlock();
		}

		$fin = false;
		while (!endOfFile($obj) && !$fin) {
			$line = nextLine($obj);
			if ($line === false || $line === '' || strncmp($line, '@@', 2) == 0) {
				$sensibleLineChanges->addChangesToListing($listingHelper, $highlighted);
				$fin = true;
			} else {
				$mod = $line{0};
				$line = rtrim(substr($line, 1));

				switch ($mod) {
					case '-':
						$text = getWrappedLineFromFile($ofile, $highlighted);
						$sensibleLineChanges->addDeletedLine($line, $text, $curoline);
						$curoline++;
						break;

					case '+':
						$text = getWrappedLineFromFile($nfile, $highlighted);
						$sensibleLineChanges->addAddedLine($line, $text, $curnline);
						$curnline++;
						break;

					default:
						$sensibleLineChanges->addChangesToListing($listingHelper, $highlighted);

						$text1 = getWrappedLineFromFile($ofile, $highlighted);
						$text2 = getWrappedLineFromFile($nfile, $highlighted);

						$listingHelper->addLine($text1, $curoline, $text2, $curnline);

						$curoline++;
						$curnline++;

						break;
				}
			}

			if (!$fin) {
				$index++;
			}
		}
	}
	$sensibleLineChanges->addChangesToListing($listingHelper, $highlighted);

	// Output the rest of the files
	if ($all) {
		while (!feof($ofile) || !feof($nfile)) {
			$noneof = false;

			$text1 = getWrappedLineFromFile($ofile, $highlighted);
			if ($text1 !== false) {
				$tmpoline = $curoline;
				$curoline++;
				$noneof = true;
			} else {
				$tmpoline = '-';
				$text1 = '&nbsp;';
			}


			$text2 = getWrappedLineFromFile($nfile, $highlighted);
			if ($text2 !== false) {
				$tmpnline = $curnline;
				$curnline++;
				$noneof = true;
			} else {
				$tmpnline = '-';
				$text2 = '&nbsp;';
			}

			if ($noneof) {
				$listingHelper->addLine($text1, $tmpoline, $text2, $tmpnline);
			}

		}
	}

	fclose($ofile);
	fclose($nfile);

	return $listingHelper->getListing();
}

function command_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname) {
	global $config, $lang, $arrayBased, $fileBased;

	$context = 5;

	if ($all) {
		// Setting the context to 0 makes diff generate the wrong line numbers!
		$context = 1;
	}

	if ($ignoreWhitespace) {
		$whitespaceFlag = ' -w';
	} else {
		$whitespaceFlag = '';
	}

	// Open a pipe to the diff command with $context lines of context

	$cmd = quoteCommand($config->diff.$whitespaceFlag.' -U '.$context.' "'.$oldtname.'" "'.$newtname.'"');

	$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));

	$resource = proc_open($cmd, $descriptorspec, $pipes);
	$error = '';

	if (is_resource($resource)) {
		// We don't need to write
		fclose($pipes[0]);

		$diff = $pipes[1];

		// Ignore the 3 header lines
		$line = fgets($diff);
		$line = fgets($diff);

		$arrayBased = false;
		$fileBased = true;

		if ($highlighted) {
			$listing = diff_result($all, $highlighted, $newhlname, $oldhlname, $diff, $ignoreWhitespace);
		} else {
			$listing = diff_result($all, $highlighted, $newtname, $oldtname, $diff, $ignoreWhitespace);
		}

		fclose($pipes[1]);

		while (!feof($pipes[2])) {
			$error .= fgets($pipes[2]);
		}

		$error = toOutputEncoding(trim($error));

		if (!empty($error)) $error = '<p>'.$lang['BADCMD'].': <code>'.$cmd.'</code></p><p>'.nl2br($error).'</p>';

		fclose($pipes[2]);

		proc_close($resource);

	} else {
		$error = '<p>'.$lang['BADCMD'].': <code>'.$cmd.'</code></p>';
	}

	if (!empty($error)) {
		echo $error;

		if (is_resource($resource)) {
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			proc_close($resource);
		}
		exit;
	}

	return $listing;
}

function inline_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname) {
	global $arrayBased, $fileBased;

	$context = 5;
	if ($all) {
		// Setting the context to 0 makes diff generate the wrong line numbers!
		$context = 1;
	}

	// modify error reporting level to suppress deprecated/strict warning "Assigning the return value of new by reference"
	$bckLevel = error_reporting();
	$removeLevel = 0;
	if (version_compare(PHP_VERSION, '5.3.0alpha') !== -1) {
		$removeLevel = E_DEPRECATED;
	} else if (version_compare(PHP_VERSION, '5.0.0') !== -1) {
		$removeLevel = E_STRICT;
	}
	$modLevel = $bckLevel & (~$removeLevel);
	error_reporting($modLevel);

	// Create the diff class
	$fromLines = explode("\n", file_get_contents($oldtname));
	$toLines = explode("\n", file_get_contents($newtname));
	if (!$ignoreWhitespace) {
		$diff = @new Text_Diff('auto', array($fromLines, $toLines));
	} else {
		$whitespaces = array(' ', "\t", "\n", "\r");
		$mappedFromLines = array();
		foreach ($fromLines as $line) {
			$mappedFromLines[] = str_replace($whitespaces, array(), $line);
		}
		$mappedToLines = array();
		foreach ($toLines as $line) {
			$mappedToLines[] = str_replace($whitespaces, array(), $line);
		}
		$diff = @new Text_MappedDiff($fromLines, $toLines, $mappedFromLines, $mappedToLines);
	}
	$renderer = new Text_Diff_Renderer_unified(array('leading_context_lines' => $context, 'trailing_context_lines' => $context));
	$rendered = explode("\n", $renderer->render($diff));

	// restore previous error reporting level
	error_reporting($bckLevel);

	$arrayBased = true;
	$fileBased = false;
	if ($highlighted) {
		$listing = diff_result($all, $highlighted, $newhlname, $oldhlname, $rendered, $ignoreWhitespace);
	} else {
		$listing = diff_result($all, $highlighted, $newtname, $oldtname, $rendered, $ignoreWhitespace);
	}

	return $listing;
}

function do_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname) {
	if (class_exists('Text_Diff')) {
		return inline_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname);
	} else {
		return command_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname);
	}
}
