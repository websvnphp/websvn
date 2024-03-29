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

if (!defined('USE_AUTOLOADER')) {
	@include_once 'Horde/String.php';
	@include_once 'Horde/Text/Diff.php';
	@include_once 'Horde/Text/Diff/Mapped.php';
	@include_once 'Horde/Text/Diff/Engine/Native.php';
	@include_once 'Horde/Text/Diff/Op/Base.php';
	@include_once 'Horde/Text/Diff/Op/Copy.php';
	@include_once 'Horde/Text/Diff/Op/Delete.php';
	@include_once 'Horde/Text/Diff/Op/Add.php';
	@include_once 'Horde/Text/Diff/Op/Change.php';
	@include_once 'Horde/Text/Diff/Renderer.php';
	@include_once 'Horde/Text/Diff/Renderer/Unified.php';
}
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

		$listvar = &$listing[$index];
		$listvar['rev1diffclass'] = $class1;
		$listvar['rev2diffclass'] = $class2;

		$listvar['rev1line'] = $text1;
		$listvar['rev2line'] = $text2;

		$listvar['rev1lineno'] = $lineno1;
		$listvar['rev2lineno'] = $lineno2;
		$listvar['startblock'] = $this->_blockStart;
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
	$line = toOutputEncoding($line);
	if (!$is_highlighted) {
		$line = escape($line);
	}
	$line = rtrim($line, "\n\r");
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

		if (!$all && $line !== false) {
			$listingHelper->startNewBlock();
		}

		$fin = false;
		while (!endOfFile($obj) && !$fin) {
			$line = nextLine($obj);
			if ($line === false || $line === '' || strncmp($line, '@@', 2) == 0) {
				$sensibleLineChanges->addChangesToListing($listingHelper, $highlighted);
				$fin = true;
			} else {
				$mod = $line[0];
				$line = substr($line, 1);

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

	// Open a pipe to the diff command with $context lines of context:
	$cmd	= $config->diff.$whitespaceFlag.' -U '.$context.' "'.$oldtname.'" "'.$newtname.'"';
	$diff	= runCommand($cmd, true);

	// Ignore the 3 header lines:
	$line = array_shift($diff);
	$line = array_shift($diff);

	$arrayBased	= true;
	$fileBased	= false;

	if ($highlighted) {
		$listing = diff_result($all, $highlighted, $newhlname, $oldhlname, $diff, $ignoreWhitespace);
	} else {
		$listing = diff_result($all, $highlighted, $newtname, $oldtname, $diff, $ignoreWhitespace);
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
	$removeLevel = E_DEPRECATED;
	$modLevel = $bckLevel & (~$removeLevel);
	error_reporting($modLevel);

	// Create the diff class
	$fromLines = file($oldtname);
	$toLines = file($newtname);
	if (!$ignoreWhitespace) {
		$diff = new Horde_Text_Diff('Native', array($fromLines, $toLines));
	} else {
		$whitespaces = array(' ', "\t", "\n", "\r");
		$mappedFromLines = array();
		foreach ($fromLines as $k => $line) {
			$line = rtrim($line, "\n\r");
			$fromLines[$k] = $line;
			$mappedFromLines[] = str_replace($whitespaces, array(), $line);
		}
		$mappedToLines = array();
		foreach ($toLines as $k => $line) {
			$line = rtrim($line, "\n\r");
			$toLines[$k] = $line;
			$mappedToLines[] = str_replace($whitespaces, array(), $line);
		}
		$diff = new Horde_Text_Diff_Mapped('Native', array($fromLines, $toLines, $mappedFromLines, $mappedToLines));
	}
	$renderer = new Horde_Text_Diff_Renderer_Unified(array('leading_context_lines' => $context, 'trailing_context_lines' => $context));
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
	if ((!$ignoreWhitespace ? class_exists('Horde_Text_Diff') : class_exists('Horde_Text_Diff_Mapped'))
           && class_exists('Horde_Text_Diff_Renderer_Unified')) {
		   return inline_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname);
	} else {
		return command_diff($all, $ignoreWhitespace, $highlighted, $newtname, $oldtname, $newhlname, $oldhlname);
	}
}
