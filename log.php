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
// log.php
//
// Show the logs for the given path

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';
require_once 'include/bugtraq.php';

$page = (int)@$_REQUEST['page'];
$all = @$_REQUEST['all'] == 1;
$isDir = @$_REQUEST['isdir'] == 1 || $path == '' || $path == '/';
if (isset($_REQUEST['showchanges']))
	$showchanges = @$_REQUEST['showchanges'] == 1;
else
	$showchanges = $rep->logsShowChanges();
$search = trim(@$_REQUEST['search']);
$dosearch = strlen($search) > 0;

$words = preg_split('#\s+#', $search);
$fromRev = (int)@$_REQUEST['fr'];
$startrev = strtoupper(trim(@$_REQUEST['sr']));
$endrev = strtoupper(trim(@$_REQUEST['er']));
$max = isset($_REQUEST['max']) ? (int)$_REQUEST['max'] : false;

// Max number of results to find at a time
$numSearchResults = 20;

if ($search == '') {
	$dosearch = false;
}

// removeAccents
//
// Remove all the accents from a string. This function doesn't seem
// ideal, but expecting everyone to install 'unac' seems a little
// excessive as well...

function removeAccents($string) {
	$string = htmlentities($string, ENT_QUOTES, 'ISO-8859-1');
	$string = preg_replace('/&([A-Za-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron);/', '$1', $string);
	return $string;
}

// Normalise the search words
foreach ($words as $index => $word) {
	$words[$index] = strtolower(removeAccents($word));

	// Remove empty string introduced by multiple spaces
	if (empty($words[$index]))
		unset($words[$index]);
}

if (empty($page))
	$page = 1;

// If searching, display all the results
if ($dosearch)
	$all = true;

$maxperpage = 20;

// Make sure that we have a repository
if ($rep) {
	$svnrep = new SVNRepository($rep);

	$history = $svnrep->getLog($path, 'HEAD', '', false, 1, ($path == '/') ? '' : $peg);
	if (!$history) {
		unset($vars['error']);
		$history = $svnrep->getLog($path, '', '', false, 1, ($path == '/') ? '' : $peg);
		if (!$history) {
			header('HTTP/1.x 404 Not Found', true, 404);
			$vars['error'] = $lang['NOPATH'];
		}
	}

	$youngest = ($history && isset($history->entries[0])) ? $history->entries[0]->rev : 0;

	if (empty($startrev)) {
	  //$startrev = ($rev) ? $rev : 'HEAD';
		$startrev = $rev;
	} else if ($startrev != 'HEAD' && $startrev != 'BASE' && $startrev != 'PREV' && $startrev != 'COMMITTED') {
		$startrev = (int)$startrev;
	}
	if (empty($endrev)) {
		$endrev = 1;
	} else if ($endrev != 'HEAD' && $endrev != 'BASE' && $endrev != 'PREV' && $endrev != 'COMMITTED') {
		$endrev = (int)$endrev;
	}

	if (empty($rev)) {
		$rev = $youngest;
	}

	if (empty($startrev)) {
		$startrev = $rev;
	}

	// make sure path is prefixed by a /
	$ppath = $path;
	if ($path == '' || $path{0} != '/') {
		$ppath = '/'.$path;
	}

	$vars['action'] = $lang['LOG'];
	$vars['rev'] = $rev;
	$vars['peg'] = $peg;
	$vars['path'] = escape($ppath);

	if ($history && isset($history->entries[0])) {
		$vars['log'] = xml_entities($history->entries[0]->msg);
		$vars['date'] = $history->entries[0]->date;
		$vars['age'] = datetimeFormatDuration(time() - strtotime($history->entries[0]->date));
		$vars['author'] = $history->entries[0]->author;
	}

	if ($max === false) {
		$max = ($dosearch) ? 0 : 40;
	} else if ($max < 0) {
		$max = 40;
	}

	// TODO: If the rev is less than the head, get the path (may have been renamed!)
	// Will probably need to call `svn info`, parse XML output, and substring a path

	createPathLinks($rep, $ppath, $passrev, $peg);
	$passRevString = createRevAndPegString($rev, $peg);
	$isDirString = ($isDir) ? 'isdir=1&amp;' : '';

	unset($queryParams['repname']);
	unset($queryParams['path']);
	// Toggle 'showchanges' param for link to switch from the current behavior
	if ($showchanges == $rep->logsShowChanges())
		$queryParams['showchanges'] = (int)!$showchanges;
	else
		unset($queryParams['showchanges']);
	$vars['changesurl'] = $config->getURL($rep, $path, 'log').buildQuery($queryParams);
	$vars['changeslink'] = '<a href="'.$vars['changesurl'].'">'.$lang[($showchanges ? 'HIDECHANGED' : 'SHOWCHANGED')].'</a>';
	$vars['showchanges'] = $showchanges;
	// Revert 'showchanges' param to propagate the current behavior
	if ($showchanges == $rep->logsShowChanges())
		unset($queryParams['showchanges']);
	else
		$queryParams['showchanges'] = (int)$showchanges;

	$vars['revurl'] = $config->getURL($rep, $path, 'revision').$isDirString.$passRevString;
	if ($isDir) {
		$vars['directoryurl'] = $config->getURL($rep, $path, 'dir').$passRevString.'#'.anchorForPath($path);
		$vars['directorylink'] = '<a href="'.$vars['directoryurl'].'">'.$lang['LISTING'].'</a>';
	} else {
		$vars['filedetailurl'] = $config->getURL($rep, $path, 'file').$passRevString;
		$vars['filedetaillink'] = '<a href="'.$vars['filedetailurl'].'">'.$lang['FILEDETAIL'].'</a>';

		$vars['blameurl'] = $config->getURL($rep, $path, 'blame').$passRevString;
		$vars['blamelink'] = '<a href="'.$vars['blameurl'].'">'.$lang['BLAME'].'</a>';

		$vars['diffurl'] = $config->getURL($rep, $path, 'diff').$passRevString;
		$vars['difflink'] = '<a href="'.$vars['diffurl'].'">'.$lang['DIFFPREV'].'</a>';
	}

	if ($rep->isRssEnabled()) {
		$vars['rssurl'] = $config->getURL($rep, $path, 'rss').$isDirString.createRevAndPegString('', $peg);
		$vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
	}

	if ($rev != $youngest) {
		if ($path == '/') {
			$vars['goyoungesturl'] = $config->getURL($rep, '', 'log').$isDirString;
		} else {
			$vars['goyoungesturl'] = $config->getURL($rep, $path, 'log').$isDirString.'peg='.($peg ? $peg : $rev);
		}
		$vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'"'.($youngest ? ' title="'.$lang['REV'].' '.$youngest.'"' : '').'>'.$lang['GOYOUNGEST'].'</a>';
	}

	// We get the bugtraq variable just once based on the HEAD
	$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

	$vars['logsearch_moreresultslink'] = '';
	$vars['pagelinks'] = '';
	$vars['showalllink'] = '';

	if ($history) {
		$history = $svnrep->getLog($path, $startrev, $endrev, true, $max, $peg);
		if (empty($history)) {
			unset($vars['error']);
			$vars['warning'] = 'Revision '.$startrev.' of this resource does not exist.';
		}
	}
	if (!empty($history)) {
		// Get the number of separate revisions
		$revisions = count($history->entries);

		if ($all) {
			$firstrevindex = 0;
			$lastrevindex = $revisions - 1;
			$pages = 1;
		} else {
			// Calculate the number of pages
			$pages = floor($revisions / $maxperpage);
			if (($revisions % $maxperpage) > 0) $pages++;

			if ($page > $pages) $page = $pages;

			// Work out where to start and stop
			$firstrevindex = ($page - 1) * $maxperpage;
			$lastrevindex = min($firstrevindex + $maxperpage - 1, $revisions - 1);
		}

		$frev = isset($history->entries[0]) ? $history->entries[0]->rev : false;
		$brev = isset($history->entries[$firstrevindex]) ? $history->entries[$firstrevindex]->rev : false;
		$erev = isset($history->entries[$lastrevindex]) ? $history->entries[$lastrevindex]->rev : false;

		$entries = array();
		if ($brev && $erev) {
			$history = $svnrep->getLog($path, $brev, $erev, false, 0, $peg);
			if ($history)
				$entries = $history->entries;
		}

		$row = 0;
		$index = 0;
		$found = false;

		foreach ($entries as $revision) {
			// Assume a good match
			$match = true;
			$thisrev = $revision->rev;

			// Check the log for the search words, if searching
			if ($dosearch) {
				if ((empty($fromRev) || $fromRev > $thisrev)) {
					// Turn all the HTML entities into real characters.

					// Make sure that each word in the search in also in the log
					foreach ($words as $word) {
						if (strpos(strtolower(removeAccents($revision->msg)), $word) === false && strpos(strtolower(removeAccents($revision->author)), $word) === false) {
							$match = false;
							break;
						}
					}

					if ($match) {
						$numSearchResults--;
						$found = true;
					}
				} else {
					$match = false;
				}
			}

			$thisRevString = createRevAndPegString($thisrev, ($peg ? $peg : $thisrev));

			if ($match) {
				// Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
				$rpath = $revision->path;
				if (empty($rpath)) {
					$rpath = '/';
				} else if ($isDir && $rpath{strlen($rpath) - 1} != '/') {
					$rpath .= '/';
				}

				$precisePath = $revision->precisePath;
				if (empty($precisePath)) {
					$precisePath = '/';
				} else if ($isDir && $precisePath{strlen($precisePath) - 1} != '/') {
					$precisePath .= '/';
				}

				// Find the parent path (or the whole path if it's already a directory)
				$pos = strrpos($rpath, '/');
				$parent = substr($rpath, 0, $pos + 1);

				$compareValue = (($isDir) ? $parent : $rpath).'@'.$thisrev;

				$listing[$index]['compare_box'] = '<input type="checkbox" name="compare[]" value="'.$compareValue.'" onclick="checkCB(this)" />';
				$url = $config->getURL($rep, $rpath, 'revision').$thisRevString;
				$listing[$index]['revlink'] = '<a href="'.$url.'">'.$thisrev.'</a>';

				$url = $config->getURL($rep, $precisePath, ($isDir ? 'dir' : 'file')).$thisRevString;
				$listing[$index]['revpathlink'] = '<a href="'.$url.'">'.$precisePath.'</a>';
				$listing[$index]['revpath'] = $precisePath;
				$listing[$index]['revauthor'] = $revision->author;
				$listing[$index]['revdate'] = $revision->date;
				$listing[$index]['revage'] = $revision->age;
				$listing[$index]['revlog'] = nl2br($bugtraq->replaceIDs(create_anchors(xml_entities($revision->msg))));
				$listing[$index]['rowparity'] = $row;

				$listing[$index]['compareurl'] = $config->getURL($rep, '', 'comp').'compare[]='.$rpath.'@'.($thisrev - 1).'&amp;compare[]='.$rpath.'@'.$thisrev;

				if ($showchanges) {
					// Aggregate added/deleted/modified paths for display in table
					$modpaths = array();
					foreach ($revision->mods as $mod) {
						$modpaths[$mod->action][] = $mod->path;
					}
					ksort($modpaths);
					foreach ($modpaths as $action => $paths) {
						sort($paths);
						$modpaths[$action] = $paths;
					}

					$listing[$index]['revadded'] = (isset($modpaths['A'])) ? implode('<br/>', $modpaths['A']) : '';
					$listing[$index]['revdeleted'] = (isset($modpaths['D'])) ? implode('<br/>', $modpaths['D']) : '';
					$listing[$index]['revmodified'] = (isset($modpaths['M'])) ? implode('<br/>', $modpaths['M']) : '';
				}

				$row = 1 - $row;
				$index++;
			}

			// If we've reached the search limit, stop here...
			if (!$numSearchResults) {
				$url = $config->getURL($rep, $path, 'log').$isDirString.$thisRevString;
				$vars['logsearch_moreresultslink'] = '<a href="'.$url.'&amp;search='.$search.'&amp;fr='.$thisrev.'">'.$lang['MORERESULTS'].'</a>';
				break;
			}
		}

		$vars['logsearch_resultsfound'] = true;

		if ($dosearch && !$found) {
			if ($fromRev == 0) {
				$vars['logsearch_nomatches'] = true;
				$vars['logsearch_resultsfound'] = false;
			} else {
				$vars['logsearch_nomorematches'] = true;
			}
		} else if ($dosearch && $numSearchResults > 0) {
			$vars['logsearch_nomorematches'] = true;
		}

		// Work out the paging options, create links to pages of results
		if ($pages > 1) {
			$prev = $page - 1;
			$next = $page + 1;

			unset($queryParams['page']);
			$logurl = $config->getURL($rep, $path, 'log').buildQuery($queryParams);

			if ($page > 1) {
				$vars['pagelinks'] .= '<a href="'.$logurl.(!$peg && $frev && $prev != 1 ? '&amp;peg='.$frev : '').'&amp;page='.$prev.'">&larr;'.$lang['PREV'].'</a>';
			} else {
				$vars['pagelinks'] .= '<span>&larr;'.$lang['PREV'].'</span>';
			}

			for ($p = 1; $p <= $pages; $p++) {
				if ($p != $page) {
					$vars['pagelinks'] .= '<a href="'.$logurl.(!$peg && $frev && $p != 1 ? '&amp;peg='.$frev : '').'&amp;page='.$p.'">'.$p.'</a>';
				} else {
					$vars['pagelinks'] .= '<span id="curpage">'.$p.'</span>';
				}
			}

			if ($page < $pages) {
				$vars['pagelinks'] .= '<a href="'.$logurl.(!$peg && $frev ? '&amp;peg='.$frev : '').'&amp;page='.$next.'">'.$lang['NEXT'].'&rarr;</a>';
			} else {
				$vars['pagelinks'] .= '<span>'.$lang['NEXT'].'&rarr;</span>';
			}

			$vars['showalllink'] = '<a href="'.$logurl.'&amp;all=1">'.$lang['SHOWALL'].'</a>';
		}
	}

	// Create form elements for filtering and searching log messages
	if ($config->multiViews) {
		$hidden = '<input type="hidden" name="op" value="log" />';
	} else {
		$hidden = '<input type="hidden" name="repname" value="'.$repname.'" />';
		$hidden .= '<input type="hidden" name="path" value="'.$path.'" />';
	}
	if ($isDir)
		$hidden .= '<input type="hidden" name="isdir" value="'.$isDir.'" />';
	if ($peg)
		$hidden .= '<input type="hidden" name="peg" value="'.$peg.'" />';
	if ($showchanges != $rep->logsShowChanges())
		$hidden .= '<input type="hidden" name="showchanges" value="'.$showchanges.'" />';

	$vars['logsearch_form'] = '<form method="get" action="'.$config->getURL($rep, $path, 'log').'" id="search">'.$hidden;
	$vars['logsearch_startbox'] = '<input name="sr" size="5" value="'.$startrev.'" />';
	$vars['logsearch_endbox'] = '<input name="er" size="5" value="'.$endrev.'" />';
	$vars['logsearch_maxbox'] = '<input name="max" size="5" value="'.($max == 0 ? 40 : $max).'" />';
	$vars['logsearch_inputbox'] = '<input name="search" value="'.escape($search).'" />';
	$vars['logsearch_showall'] = '<input type="checkbox" name="all" value="1"'.($all ? ' checked="checked"' : '').' />';
	$vars['logsearch_submit'] = '<input type="submit" value="'.$lang['GO'].'" />';
	$vars['logsearch_endform'] = '</form>';

	// If a filter is in place, produce a link to clear all filter parameters
	if ($page !== 1 || $all || $dosearch || $fromRev || $startrev !== $rev || $endrev !== 1 || $max !== 40) {
		$url = $config->getURL($rep, $path, 'log').$isDirString.$passRevString;
		$vars['logsearch_clearloglink'] = '<a href="'.$url.'">'.$lang['CLEARLOG'].'</a>';
	}

	// Create form elements for comparing selected revisions
	$vars['compare_form'] = '<form method="get" action="'.$config->getURL($rep, '', 'comp').'" id="compare">';
	if ($config->multiViews) {
		$vars['compare_form'] .= '<input type="hidden" name="op" value="comp" />';
	} else {
		$vars['compare_form'] .= '<input type="hidden" name="repname" value="'.$repname.'" />';
	}
	$vars['compare_submit'] = '<input type="submit" value="'.$lang['COMPAREREVS'].'" />';
	$vars['compare_endform'] = '</form>';

	if (!$rep->hasReadAccess($path, false)) {
		$vars['error'] = $lang['NOACCESS'];
		checkSendingAuthHeader($rep);
	}

} else {
	header('HTTP/1.x 404 Not Found', true, 404);
}

renderTemplate('log');
