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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
//
// --
//
// comp.php
//
// Compare two paths using `svn diff`
//

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';

function checkRevision($rev) {
	if (is_numeric($rev) && ((int)$rev > 0)) {
		return $rev;
	}
	$rev = strtoupper($rev);
	if ($rev == 'HEAD' || $rev == 'PREV' || $rev == 'COMMITTED')
		return $rev;
	else
		return 'HEAD';
}

// Make sure that we have a repository
if ($rep) {
	$svnrep = new SVNRepository($rep);

	// Retrieve the request information
	$path1 = @$_REQUEST['compare'][0];
	$path2 = @$_REQUEST['compare'][1];
	$rev1 = (int)@$_REQUEST['compare_rev'][0];
	$rev2 = (int)@$_REQUEST['compare_rev'][1];
	$manualorder = (@$_REQUEST['manualorder'] == 1);
	$ignoreWhitespace = (@$_REQUEST['ignorews'] == 1);

	// Some page links put the revision with the path...
	if (strpos($path1, '@')) {
		list($path1, $rev1) = explode('@', $path1);
	} else if (strpos($path1, '@') === 0) {
		// Something went wrong. The path is missing.
		$rev1 = substr($path1, 1);
		$path1 = '/';
	}
	if (strpos($path2, '@')) {
		list($path2, $rev2) = explode('@', $path2);
	} else if (strpos($path2, '@') === 0) {
		$rev2 = substr($path2, 1);
		$path2 = '/';
	}

	$rev1 = checkRevision($rev1);
	$rev2 = checkRevision($rev2);

	// Choose a sensible comparison order unless told not to

	if (!$manualorder && is_numeric($rev1) && is_numeric($rev2) && $rev1 > $rev2) {
		$temppath = $path1;
		$path1 = $path2;
		$path2 = $temppath;

		$temprev = $rev1;
		$rev1 = $rev2;
		$rev2 = $temprev;
	}

	$vars['rev1url'] = $config->getURL($rep, $path1, 'dir').createRevAndPegString($rev1, $rev1);
	$vars['rev2url'] = $config->getURL($rep, $path2, 'dir').createRevAndPegString($rev2, $rev2);

	$url = $config->getURL($rep, '', 'comp');
	$vars['reverselink'] = '<a href="'.$url.'compare%5B%5D='.urlencode($path2).'@'.$rev2.'&amp;compare%5B%5D='.urlencode($path1).'@'.$rev1.'&amp;manualorder=1'.($ignoreWhitespace ? '&amp;ignorews=1' : '').'">'.$lang['REVCOMP'].'</a>';
	if (!$ignoreWhitespace) {
		$vars['ignorewhitespacelink'] = '<a href="'.$url.'compare%5B%5D='.urlencode($path1).'@'.$rev1.'&amp;compare%5B%5D='.urlencode($path2).'@'.$rev2.($manualorder ? '&amp;manualorder=1' : '').'&amp;ignorews=1">'.$lang['IGNOREWHITESPACE'].'</a>';
	} else {
		$vars['regardwhitespacelink'] = '<a href="'.$url.'compare%5B%5D='.urlencode($path1).'@'.$rev1.'&amp;compare%5B%5D='.urlencode($path2).'@'.$rev2.($manualorder ? '&amp;manualorder=1' : '').'">'.$lang['REGARDWHITESPACE'].'</a>';
	}

	if ($rev1 == 0) $rev1 = 'HEAD';
	if ($rev2 == 0) $rev2 = 'HEAD';

	$vars['repname'] = escape($rep->getDisplayName());
	$vars['action'] = $lang['PATHCOMPARISON'];

	$hidden = '<input type="hidden" name="manualorder" value="1" />';
	if ($config->multiViews) {
		$hidden .= '<input type="hidden" name="op" value="comp"/>';
	} else {
		$hidden .= '<input type="hidden" name="repname" value="'.$repname.'" />';
	}
	$vars['compare_form'] = '<form method="get" action="'.$url.'" id="compare">'.$hidden;
	$vars['compare_path1input'] = '<input type="text" size="40" name="compare[0]" value="'.escape($path1).'" />';
	$vars['compare_path2input'] = '<input type="text" size="40" name="compare[1]" value="'.escape($path2).'" />';
	$vars['compare_rev1input'] = '<input type="text" size="5" name="compare_rev[0]" value="'.$rev1.'" />';
	$vars['compare_rev2input'] = '<input type="text" size="5" name="compare_rev[1]" value="'.$rev2.'" />';
	$vars['compare_submit'] = '<input name="comparesubmit" type="submit" value="'.$lang['COMPAREPATHS'].'" />';
	$vars['compare_endform'] = '</form>';

	// safe paths are a hack for fixing XSS exploit
	$vars['path1'] = escape($path1);
	$vars['safepath1'] = escape($path1);
	$vars['path2'] = escape($path2);
	$vars['safepath2'] = escape($path2);

	$vars['rev1'] = $rev1;
	$vars['rev2'] = $rev2;

	$history1 = $svnrep->getLog($path1, $rev1, $rev1, false, 1);
	if (!$history1) {
		header('HTTP/1.x 404 Not Found', true, 404);
		$vars['error'] = $lang['NOPATH'];
	} else {
		$history2 = $svnrep->getLog($path2, $rev2, $rev2, false, 1);
		if (!$history2) {
			header('HTTP/1.x 404 Not Found', true, 404);
			$vars['error'] = $lang['NOPATH'];
		}
	}

	// Set variables used for the more recent of the two revisions
	$history = ($rev1 >= $rev2 ? $history1 : $history2);
	if ($history) {
		$logEntry = $history->curEntry;
		$vars['rev'] = $logEntry->rev;
		$vars['peg'] = $peg;
		$vars['date'] = $logEntry->date;
		$vars['age'] = datetimeFormatDuration(time() - strtotime($logEntry->date));
		$vars['author'] = $logEntry->author;
		$vars['log'] = xml_entities($logEntry->msg);
	} else {
		$vars['warning'] = 'Problem with comparison.';
	}

	$noinput = empty($path1) || empty($path2);

	// Generate the diff listing

	$relativePath1 = $path1;
	$relativePath2 = $path2;

	$svnpath1 = encodepath($svnrep->getSvnPath(str_replace(DIRECTORY_SEPARATOR, '/', $path1)));
	$svnpath2 = encodepath($svnrep->getSvnPath(str_replace(DIRECTORY_SEPARATOR, '/', $path2)));

	$debug = false;

	if (!$noinput) {
		$cmd = $config->getSvnCommand().$rep->svnCredentials().' diff '.($ignoreWhitespace ? '-x "-w --ignore-eol-style" ' : '').quote($svnpath1.'@'.$rev1).' '.quote($svnpath2.'@'.$rev2);
	}

	function clearVars() {
		global $ignoreWhitespace, $listing, $index;

		if ($ignoreWhitespace && $index > 1) {
			$endBlock = false;
			$previous = $index - 1;
			if ($listing[$previous]['endpath']) $endBlock = 'newpath';
			else if ($listing[$previous]['enddifflines']) $endBlock = 'difflines';
			if ($endBlock !== false) {
				// check if block ending at previous contains real diff data
				$i = $previous;
				$containsOnlyEqualDiff = true;
				$addedLines = array();
				$removedLines = array();
				while ($i >= 0 && !$listing[$i - 1][$endBlock]) {
					$diffclass = $listing[$i - 1]['diffclass'];

					if ($diffclass !== 'diffadded' && $diffclass !== 'diffdeleted') {
						if ($addedLines !== $removedLines) {
							$containsOnlyEqualDiff = false;
							break;
						}
					}
					if (count($addedLines) > 0 && $addedLines === $removedLines) {
						$addedLines = array();
						$removedLines = array();
					}

					if ($diffclass === 'diff') {
						$i--;
						continue;
					}
					if ($diffclass === null) {
						$containsOnlyEqualDiff = false;
						break;;
					}

					if ($diffclass === 'diffdeleted') {
						if (count($addedLines) <= count($removedLines)) {
							$containsOnlyEqualDiff = false;
							break;;
						}
						array_unshift($removedLines, $listing[$i - 1]['line']);
						$i--;
						continue;
					}

					if ($diffclass === 'diffadded') {
						if (count($removedLines) > 0) {
							$containsOnlyEqualDiff = false;
							break;;
						}
						array_unshift($addedLines, $listing[$i - 1]['line']);
						$i--;
						continue;
					}

					assert(false);
				}
				if ($containsOnlyEqualDiff) {
					$containsOnlyEqualDiff = $addedLines === $removedLines;
				}

				// remove blocks which only contain diffclass=diff and equal removes and adds
				if ($containsOnlyEqualDiff) {
					for ($j = $i - 1; $j < $index; $j++) {
						unset($listing[$j]);
					}
					$index = $i - 1;
				}
			}
		}

		$listing[$index]['newpath'] = null;
		$listing[$index]['endpath'] = null;
		$listing[$index]['info'] = null;
		$listing[$index]['diffclass'] = null;
		$listing[$index]['difflines'] = null;
		$listing[$index]['enddifflines'] = null;
		$listing[$index]['properties'] = null;
	}

	$vars['success'] = false;

	if (!$noinput) {
		// TODO: Report warning/error if comparison encounters any problems
		if ($diff = popenCommand($cmd, 'r')) {
			$listing = array();
			$index = 0;
			$indiff = false;
			$indiffproper = false;
			$getLine = true;
			$node = null;
			$bufferedLine = false;

			$vars['success'] = true;

			while (!feof($diff)) {
				if ($getLine) {
					if ($bufferedLine === false) {
						$bufferedLine = rtrim(fgets($diff), "\r\n");
					}
					$newlineR = strpos($bufferedLine, "\r");
					$newlineN = strpos($bufferedLine, "\n");
					if ($newlineR === false && $newlineN === false) {
						$line = $bufferedLine;
						$bufferedLine = false;
					} else {
						$newline = ($newlineR < $newlineN ? $newlineR : $newlineN);
						$line = substr($bufferedLine, 0, $newline);
						$bufferedLine = substr($bufferedLine, $newline + 1);
					}
				}

				clearVars();
				$getLine = true;
				if ($debug) print 'Line = "'.$line.'"<br />';
				if ($indiff) {
					// If we're in a diff proper, just set up the line
					if ($indiffproper) {
						if (strlen($line) > 0 && ($line[0] == ' ' || $line[0] == '+' || $line[0] == '-')) {
							$subline = escape(toOutputEncoding(substr($line, 1)));
							$subline = rtrim($subline, "\n\r");
							$subline = ($subline) ? expandTabs($subline) : '&nbsp;';
							$listing[$index]['line'] = $subline;

							switch ($line[0]) {
								case ' ':
									$listing[$index]['diffclass'] = 'diff';
									if ($debug) print 'Including as diff: '.$subline.'<br />';
									break;

								case '+':
									$listing[$index]['diffclass'] = 'diffadded';
									if ($debug) print 'Including as added: '.$subline.'<br />';
									break;

								case '-':
									$listing[$index]['diffclass'] = 'diffdeleted';
									if ($debug) print 'Including as removed: '.$subline.'<br />';
									break;
							}
							$index++;
						} else if ($line != '\ No newline at end of file') {
							$indiffproper = false;
							$listing[$index++]['enddifflines'] = true;
							$getLine = false;
							if ($debug) print 'Ending lines<br />';
						}
						continue;
					}

					// Check for the start of a new diff area
					if (!strncmp($line, '@@', 2)) {
						$pos = strpos($line, '+');
						$posline = substr($line, $pos);
						$sline = 0;
						$eline = 0;
						sscanf($posline, '+%d,%d', $sline, $eline);
						if ($debug) print 'sline = "'.$sline.'", eline = "'.$eline.'"<br />';
						// Check that this isn't a file deletion
						if ($sline == 0 && $eline == 0) {
							$line = fgets($diff);
							if ($debug) print 'Ignoring: "'.$line.'"<br />';
							while ($line[0] == ' ' || $line[0] == '+' || $line[0] == '-') {
								$line = fgets($diff);
								if ($debug) print 'Ignoring: "'.$line.'"<br />';
							}

							$getLine = false;
							if ($debug) print 'Unignoring previous - marking as deleted<br />';
							$listing[$index++]['info'] = $lang['FILEDELETED'];

						} else {
							$listing[$index]['difflines'] = $line;
							$sline = 0;
							$slen = 0;
							$eline = 0;
							$elen = 0;
							sscanf($line, '@@ -%d,%d +%d,%d @@', $sline, $slen, $eline, $elen);
							$listing[$index]['rev1line'] = $sline;
							$listing[$index]['rev1len'] = $slen;
							$listing[$index]['rev2line'] = $eline;
							$listing[$index]['rev2len'] = $elen;

							$indiffproper = true;

							$index++;
						}

						continue;

					} else {
						$indiff = false;
						if ($debug) print 'Ending diff';
					}
				}

				// Check for a new node entry
				if (strncmp(trim($line), 'Index: ', 7) == 0) {
					// End the current node
					if ($node) {
						$listing[$index++]['endpath'] = true;
						clearVars();
					}

					$node = trim($line);
					$node = substr($node, 7);
					if ($node == '' || $node{0} != '/') $node = '/'.$node;

					if (substr($path2, -strlen($node)) === $node) {
						$absnode = $path2;
					} else {
						$absnode = $path2;
						if (substr($absnode, -1) == '/') $absnode = substr($absnode, 0, -1);
						$absnode .= $node;
					}

					$listing[$index]['newpath'] = $absnode;

					$listing[$index]['fileurl'] = $config->getURL($rep, $absnode, 'file').'rev='.$rev2;

					if ($debug) echo 'Creating node '.$node.'<br />';

					// Skip past the line of ='s
					$line = fgets($diff);
					if ($debug) print 'Skipping: '.$line.'<br />';

					// Check for a file addition
					$line = fgets($diff);
					if ($debug) print 'Examining: '.$line.'<br />';
					if (strpos($line, '(revision 0)')) {
						$listing[$index]['info'] = $lang['FILEADDED'];
					}

					if (strncmp(trim($line), 'Cannot display:', 15) == 0) {
						$index++;
						clearVars();
						$listing[$index++]['info'] = escape(toOutputEncoding($line));
						continue;
					}

					// Skip second file info
					$line = fgets($diff);
					if ($debug) print 'Skipping: '.$line.'<br />';

					$indiff = true;
					$index++;

					continue;
				}

				if (strncmp(trim($line), 'Property changes on: ', 21) == 0) {
					$propnode = trim($line);
					$propnode = substr($propnode, 21);
					if ($propnode == '' || $propnode{0} != '/') $propnode = '/'.$propnode;

					if ($debug) print 'Properties on '.$propnode.' (cur node $ '.$node.')';
					if ($propnode != $node) {
						if ($node) {
							$listing[$index++]['endpath'] = true;
							clearVars();
						}

						$node = $propnode;

						$listing[$index++]['newpath'] = $node;
						clearVars();
					}

					$listing[$index++]['properties'] = true;
					clearVars();
					if ($debug) echo 'Creating node '.$node.'<br />';

					// Skip the row of underscores
					$line = fgets($diff);
					if ($debug) print 'Skipping: '.$line.'<br />';

					while ($line = trim(fgets($diff))) {
						$listing[$index++]['info'] = escape(toOutputEncoding($line));
						clearVars();
					}

					continue;
				}

				// Check for error messages
				if (strncmp(trim($line), 'svn: ', 5) == 0) {
					$listing[$index++]['info'] = urldecode($line);
					$vars['success'] = false;
					continue;
				}

				$listing[$index++]['info'] = escape(toOutputEncoding($line));
			}

			if ($node) {
				clearVars();
				$listing[$index++]['endpath'] = true;
			}

			if ($debug) print_r($listing);

			if (!$rep->hasUnrestrictedReadAccess($relativePath1) || !$rep->hasUnrestrictedReadAccess($relativePath2, false)) {
				// check every item for access and remove it if read access is not allowed
				$restricted = array();
				$inrestricted = false;
				foreach ($listing as $i => $item) {
					if ($item['newpath'] !== null) {
						$newpath = $item['newpath'];
						$inrestricted = !$rep->hasReadAccess($newpath, false);
					}
					if ($inrestricted) {
						$restricted[] = $i;
					}
					if ($item['endpath'] !== null) {
						$inrestricted = false;
					}
				}
				foreach ($restricted as $i) {
					unset($listing[$i]);
				}
				if (count($restricted) && !count($listing)) {
					$vars['error'] = $lang['NOACCESS'];
					checkSendingAuthHeader($rep);
				}
			}

			pclose($diff);
		}
	}

} else {
	header('HTTP/1.x 404 Not Found', true, 404);
}

renderTemplate('compare');
