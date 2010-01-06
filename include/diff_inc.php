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

$arrayBased = false;
$fileBased = false;

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
	if ($is_highlighted) {
		$line = toOutputEncoding($line);
	} else {
		$line = replaceEntities($line);
	}
	if (strip_tags($line) === '') $line = '&nbsp;';
	return wrapInCodeTagIfNecessary($line);
}

function diff_result($all, $highlighted, $newtname, $oldtname, $obj) {
	$ofile = fopen($oldtname, 'r');
	$nfile = fopen($newtname, 'r');

	// Get the first real line
	$line = nextLine($obj);

	$index = 0;
	$listing = array();

	$curoline = 1;
	$curnline = 1;

	while (!endOfFile($obj)) {
		// Get the first line of this range
		$oline = 0;
		sscanf($line, '@@ -%d', $oline);
		$line = substr($line, strpos($line, '+'));
		$nline = 0;
		sscanf($line, '+%d', $nline);

		while ($curoline < $oline || $curnline < $nline) {
			if ($all) {
				$listing[$index]['rev1diffclass'] = 'diff';
				$listing[$index]['rev2diffclass'] = 'diff';
			}

			if ($curoline < $oline) {
				$text = getWrappedLineFromFile($ofile, $highlighted);

				if ($all) {
					$listing[$index]['rev1line'] = $text;
				}

				$curoline++;
			} else if ($all) {
				$listing[$index]['rev1line'] = '&nbsp;';
			}

			if ($curnline < $nline) {
				$text = getWrappedLineFromFile($nfile, $highlighted);

				if ($all) {
					$listing[$index]['rev2line'] = $text;
				}
				$curnline++;

			} else if ($all) {
				$listing[$index]['rev2line'] = '&nbsp;';
			}

			if ($all) {
				$listing[$index]['rev1lineno'] = 0;
				$listing[$index]['rev2lineno'] = 0;

				$index++;
			}
		}

		if (!$all) {
			// Output the line numbers
			$listing[$index]['rev1lineno'] = $oline;
			$listing[$index]['rev2lineno'] = $nline;
			$index++;
		}

		$fin = false;
		while (!endOfFile($obj) && !$fin) {
			$line = nextLine($obj);
			if ($line === false || $line === '' || strncmp($line, '@@', 2) == 0) {
				$fin = true;
			} else {
				$listing[$index]['rev1lineno'] = 0;
				$listing[$index]['rev2lineno'] = 0;

				$mod = $line{0};

				switch ($mod) {
					case '-':
						$curoline++;
						$text = getWrappedLineFromFile($ofile, $highlighted);

						$listing[$index]['rev1diffclass'] = 'diffdeleted';
						$listing[$index]['rev2diffclass'] = 'diff';

						$listing[$index]['rev1line'] = $text;
						$listing[$index]['rev2line'] = '&nbsp;';

						break;

					case '+':
						// Try to mark "changed" line sensibly
						if (!empty($listing[$index - 1]) && empty($listing[$index - 1]['rev1lineno']) && @$listing[$index - 1]['rev1diffclass'] == 'diffdeleted' && @$listing[$index - 1]['rev2diffclass'] == 'diff') {
							$i = $index - 1;
							while (!empty($listing[$i - 1]) && empty($listing[$i - 1]['rev1lineno']) && $listing[$i - 1]['rev1diffclass'] == 'diffdeleted' && $listing[$i - 1]['rev2diffclass'] == 'diff') {
								$i--;
							}

							$curnline++;
							$text = getWrappedLineFromFile($nfile, $highlighted);

							$listing[$i]['rev1diffclass'] = 'diffchanged';
							$listing[$i]['rev2diffclass'] = 'diffchanged';
							$listing[$i]['rev2line'] = $text;

							// Don't increment the current index count
							$index--;

						} else {
							$curnline++;
							$text = getWrappedLineFromFile($nfile, $highlighted);

							$listing[$index]['rev1diffclass'] = 'diff';
							$listing[$index]['rev2diffclass'] = 'diffadded';

							$listing[$index]['rev1line'] = '&nbsp;';
							$listing[$index]['rev2line'] = $text;
						}
						break;

					default:
						$listing[$index]['rev1diffclass'] = 'diff';
						$listing[$index]['rev2diffclass'] = 'diff';

						$curoline++;
						$text = getWrappedLineFromFile($ofile, $highlighted);
						$listing[$index]['rev1line'] = $text;

						$curnline++;
						$text = getWrappedLineFromFile($nfile, $highlighted);
						$listing[$index]['rev2line'] = $text;

						break;
				}
			}

			if (!$fin) {
				$index++;
			}
		}
	}

	// Output the rest of the files
	if ($all) {
		while (!feof($ofile) || !feof($nfile)) {
			$listing[$index]['rev1diffclass'] = 'diff';
			$listing[$index]['rev2diffclass'] = 'diff';

			$text = getWrappedLineFromFile($ofile, $highlighted);
			if ($text !== false) {
				$curoline++;
				$listing[$index]['rev1line'] = $text;
			} else {
				$listing[$index]['rev1line'] = '&nbsp;';
			}


			$text = getWrappedLineFromFile($nfile, $highlighted);
			if ($text !== false) {
				$curnline++;
				$listing[$index]['rev2line'] = $text;
			} else {
				$listing[$index]['rev2line'] = '&nbsp;';
			}

			$listing[$index]['rev1lineno'] = 0;
			$listing[$index]['rev2lineno'] = 0;

			$index++;
		}
	}

	fclose($ofile);
	fclose($nfile);

	return $listing;
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
			$listing = diff_result($all, $highlighted, $newhlname, $oldhlname, $diff);
		} else {
			$listing = diff_result($all, $highlighted, $newtname, $oldtname, $diff);
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
		$diff = new Text_Diff('auto', array($fromLines, $toLines));
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
		$diff = new Text_MappedDiff($fromLines, $toLines, $mappedFromLines, $mappedToLines);
	}
	$renderer = new Text_Diff_Renderer_unified(array('leading_context_lines' => $context, 'trailing_context_lines' => $context));
	$rendered = explode("\n", $renderer->render($diff));

	// restore previous error reporting level
	error_reporting($bckLevel);

	$arrayBased = true;
	$fileBased = false;
	if ($highlighted) {
		$listing = diff_result($all, $highlighted, $newhlname, $oldhlname, $rendered);
	} else {
		$listing = diff_result($all, $highlighted, $newtname, $oldtname, $rendered);
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
