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
// listing.php
//
// Show the listing for the given repository/path/revision

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';
require_once 'include/bugtraq.php';

function removeURLSeparator($url) {
	return preg_replace('#(\?|&(amp;)?)$#', '', $url);
}

function urlForPath($fullpath, $passRevString) {
	global $config, $rep;

	$isDir = $fullpath{strlen($fullpath) - 1} == '/';
	if ($isDir) {
		if ($config->treeView) {
			$url = $config->getURL($rep, $fullpath, 'dir').$passRevString;
			$id = anchorForPath($fullpath);
			$url .= '#'.$id.'" id="'.$id;
		} else {
			$url = $config->getURL($rep, $fullpath, 'dir').$passRevString;
		}
	} else {
		$url = $config->getURL($rep, $fullpath, 'file').$passRevString;
	}
	return removeURLSeparator($url);
}

function showDirFiles($svnrep, $subs, $level, $limit, $rev, $peg, $listing, $index, $treeview = true) {
	global $config, $lang, $rep, $passrev, $peg, $passRevString;

	$path = '';

	if (!$treeview) {
		$level = $limit;
	}

	// TODO: Fix node links to use the path and number of peg revision (if exists)
	// This applies to file detail, log, and RSS -- leave the download link as-is
	for ($n = 0; $n <= $level; $n++) {
		$path .= $subs[$n].'/';
	}

	// List each file in the current directory
	$loop = 0;
	$last_index = 0;
	$accessToThisDir = $rep->hasReadAccess($path, false);

	// If using flat view and not at the root, create a '..' entry at the top.
	if (!$treeview && count($subs) > 2) {
		$parentPath = $subs;
		unset($parentPath[count($parentPath) - 2]);
		$parentPath = implode('/', $parentPath);
		if ($rep->hasReadAccess($parentPath, false)) {
			$listing[$index]['rowparity'] = $index % 2;
			$listing[$index]['path'] = $parentPath;
			$listing[$index]['filetype'] = 'dir';
			$listing[$index]['filename'] = '..';
			$listing[$index]['fileurl'] = urlForPath($parentPath, $passRevString);
			$listing[$index]['filelink'] = '<a href="'.$listing[$index]['fileurl'].'">'.$listing[$index]['filename'].'</a>';
			$listing[$index]['level'] = 0;
			$listing[$index]['node'] = 0; // t-node
			$listing[$index]['revision'] = $rev;
			$listing[$index]['revurl'] = $config->getURL($rep, $parentPath, 'revision').'rev='.$rev.'&amp;isdir=1';
			global $vars;
			$listing[$index]['date'] = $vars['date'];
			$listing[$index]['age'] = datetimeFormatDuration(time() - strtotime($vars['date']), true, true);
			$index++;
		}
	}

	$openDir = false;
	$logList = $svnrep->getList($path, $rev, $peg);
	if ($logList) {
		$downloadRevAndPeg = createRevAndPegString($rev, $peg ? $peg : $rev);
		foreach ($logList->entries as $entry) {
			$isDir = $entry->isdir;
			if (!$isDir && $level != $limit) {
				continue; // Skip any files outside the current directory
			}
			$file = $entry->file;
			$isDirString = ($isDir) ? 'isdir=1&amp;' : '';

			// Only list files/directories that are not designated as off-limits
			$access = ($isDir) ? $rep->hasReadAccess($path.$file, true)
												 : $accessToThisDir;
			if ($access) {
				$listing[$index]['rowparity'] = $index % 2;

				if ($isDir) {
					$listing[$index]['filetype'] = ($openDir) ? 'diropen' : 'dir';
					$openDir = isset($subs[$level + 1]) && (!strcmp($subs[$level + 1].'/', $file) || !strcmp($subs[$level + 1], $file));
				} else {
					$listing[$index]['filetype'] = strtolower(strrchr($file, '.'));
					$openDir = false;
				}
				$listing[$index]['isDir'] = $isDir;
				$listing[$index]['openDir'] = $openDir;
				$listing[$index]['level'] = ($treeview) ? $level : 0;
				$listing[$index]['node'] = 0; // t-node
				$listing[$index]['path'] = $path.$file;
				$listing[$index]['filename'] = $file;
				if ($isDir) {
					$listing[$index]['fileurl'] = urlForPath($path.$file, $passRevString);
				} else {
					$listing[$index]['fileurl'] = urlForPath($path.$file, createDifferentRevAndPegString($passrev, $peg));
				}
				$listing[$index]['filelink'] = '<a href="'.$listing[$index]['fileurl'].'">'.$listing[$index]['filename'].'</a>';
				if ($isDir) {
					$listing[$index]['logurl'] = $config->getURL($rep, $path.$file, 'log').$isDirString.$passRevString;
				} else {
					$listing[$index]['logurl'] = $config->getURL($rep, $path.$file, 'log').$isDirString.createDifferentRevAndPegString($passrev, $peg);
				}

				if ($treeview) {
					$listing[$index]['compare_box'] = '<input type="checkbox" name="compare[]" value="'.$path.$file.'@'.$passrev.'" onclick="checkCB(this)" />';
				}
				if ($config->showLastModInListing()) {
					$listing[$index]['committime'] = $entry->committime;
					$listing[$index]['revision'] = $entry->rev;
					$listing[$index]['author'] = $entry->author;
					$listing[$index]['age'] = $entry->age;
					$listing[$index]['date'] = $entry->date;
					$listing[$index]['revurl'] = $config->getURL($rep, $path.$file, 'revision').$isDirString.createRevAndPegString($entry->rev, $peg ? $peg : $rev);
				}
				if ($rep->isDownloadAllowed($path.$file)) {
					$downloadurl = $config->getURL($rep, $path.$file, 'dl').$isDirString.$downloadRevAndPeg;
					if ($isDir) {
						$listing[$index]['downloadurl'] = $downloadurl;
						$listing[$index]['downloadplainurl'] = '';
					} else {
						$listing[$index]['downloadplainurl'] = $downloadurl;
						$listing[$index]['downloadurl'] = '';
					}
				} else {
					$listing[$index]['downloadplainurl'] = '';
					$listing[$index]['downloadurl'] = '';
				}
				if ($rep->isRssEnabled()) {
					// RSS should always point to the latest revision, so don't include rev
					$listing[$index]['rssurl'] = $config->getURL($rep, $path.$file, 'rss').$isDirString.createRevAndPegString('', $peg);
				}

				$loop++;
				$index++;
				$last_index = $index;

				if ($isDir && ($level != $limit)) {
					// @todo remove the alternate check with htmlentities when assured that there are not side effects
					if (isset($subs[$level + 1]) && (!strcmp($subs[$level + 1].'/', $file) || !strcmp(htmlentities($subs[$level + 1], ENT_QUOTES).'/', htmlentities($file)))) {
						$listing = showDirFiles($svnrep, $subs, $level + 1, $limit, $rev, $peg, $listing, $index);
						$index = count($listing);
					}
				}
			}
		}
	}

	// For an expanded tree, give the last entry an "L" node to close the grouping
	if ($treeview && $last_index != 0) {
		$listing[$last_index - 1]['node'] = 1; // l-node
	}

	return $listing;
}

function showTreeDir($svnrep, $path, $rev, $peg, $listing) {
	global $vars, $config;

	$subs = explode('/', $path);

	// For directory, the last element in the subs is empty.
	// For file, the last element in the subs is the file name.
	// Therefore, it is always count($subs) - 2
	$limit = count($subs) - 2;

	for ($n = 0; $n < $limit; $n++) {
		$vars['last_i_node'][$n] = false;
	}

	$vars['compare_box'] = ''; // Set blank once in case tree view is not enabled.
	return showDirFiles($svnrep, $subs, 0, $limit, $rev, $peg, $listing, 0, $config->treeView);
}

// Make sure that we have a repository
if ($rep) {
	$svnrep = new SVNRepository($rep);

	if (!empty($rev)) {
		$info = $svnrep->getInfo($path, $rev, $peg);
		if ($info) {
			$path = $info->path;
			$peg = (int)$info->rev;
		}
	}

	$history = $svnrep->getLog($path, 'HEAD', 1, false, 2, ($path == '/') ? '' : $peg);
	if (!$history) {
		unset($vars['error']);
		$history = $svnrep->getLog($path, '', '', false, 2, ($path == '/') ? '' : $peg);
		if (!$history) {
			header('HTTP/1.x 404 Not Found', true, 404);
			$vars['error'] = $lang['NOPATH'];
		}
	}
	$youngest = ($history && isset($history->entries[0])) ? $history->entries[0]->rev : 0;

	// Unless otherwise specified, we get the log details of the latest change
	$lastChangedRev = ($passrev) ? $passrev : $youngest;
	if ($lastChangedRev != $youngest) {
		$history = $svnrep->getLog($path, $lastChangedRev, 1, false, 2, $peg);
	}
	$logEntry = ($history && isset($history->entries[0])) ? $history->entries[0] : 0;

	$headlog = $svnrep->getLog('/', '', '', true, 1);
	$headrev = ($headlog && isset($headlog->entries[0])) ? $headlog->entries[0]->rev : 0;

	// If we're not looking at a specific revision, get the HEAD revision number
	// (the revision of the rest of the tree display)

	if (empty($rev)) {
		$rev = $headrev;
	}

	if ($path == '' || $path{0} != '/') {
		$ppath = '/'.$path;
	} else {
		$ppath = $path;
	}

	createPathLinks($rep, $ppath, $passrev, $peg);
	$passRevString = createRevAndPegString($passrev, $peg);
	$isDirString = 'isdir=1&amp;';

	$revurl = $config->getURL($rep, $path != '/' ? $path : '', 'dir');
	$revurlSuffix = $path != '/' ? '#'.anchorForPath($path) : '';
	if ($rev < $youngest) {
		if ($path == '/') {
			$vars['goyoungesturl'] = $config->getURL($rep, '', 'dir');
		} else {
			$vars['goyoungesturl'] = $config->getURL($rep, $path, 'dir').createRevAndPegString($youngest, $peg ? $peg: $rev).$revurlSuffix;
		}
		$vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'"'.($youngest ? ' title="'.$lang['REV'].' '.$youngest.'"' : '').'>'.$lang['GOYOUNGEST'].'</a>';

		$history2 = $svnrep->getLog($path, $rev, $youngest, false, 2, $peg);
		if (isset($history2->entries[1])) {
			$nextRev = $history2->entries[1]->rev;
			if ($nextRev != $youngest) {
				$vars['nextrev'] = $nextRev;
				$vars['nextrevurl'] = $revurl.createRevAndPegString($nextRev, $peg).$revurlSuffix;
			}
		}
		unset($vars['error']);
	}

	if (isset($history->entries[1])) {
		$prevRev = $history->entries[1]->rev;
		$prevPath = $history->entries[1]->path;
		$vars['prevrev'] = $prevRev;
		$vars['prevrevurl'] = $revurl.createRevAndPegString($prevRev, $peg).$revurlSuffix;
	}

	$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

	$vars['action'] = '';
	$vars['rev'] = $rev;
	$vars['peg'] = $peg;
	$vars['path'] = escape($ppath);
	$vars['lastchangedrev'] = $lastChangedRev;
	if ($logEntry) {
		$vars['date'] = $logEntry->date;
		$vars['age'] = datetimeFormatDuration(time() - strtotime($logEntry->date));
		$vars['author'] = $logEntry->author;
		$vars['log'] = nl2br($bugtraq->replaceIDs(create_anchors(xml_entities($logEntry->msg))));
	}
	$vars['revurl'] = $config->getURL($rep, ($path == '/' ? '' : $path), 'revision').$isDirString.$passRevString;
	$vars['revlink'] = '<a href="'.$vars['revurl'].'">'.$lang['LASTMOD'].'</a>';

	if ($history && count($history->entries) > 1) {
		$vars['compareurl'] = $config->getURL($rep, '', 'comp').'compare[]='.urlencode($history->entries[1]->path).'@'.$history->entries[1]->rev. '&amp;compare[]='.urlencode($history->entries[0]->path).'@'.$history->entries[0]->rev;
		$vars['comparelink'] = '<a href="'.$vars['compareurl'].'">'.$lang['DIFFPREV'].'</a>';
	}

	$vars['logurl'] = $config->getURL($rep, $path, 'log').$isDirString.$passRevString;
	$vars['loglink'] = '<a href="'.$vars['logurl'].'">'.$lang['VIEWLOG'].'</a>';

	if ($rep->isRssEnabled()) {
		$vars['rssurl'] = $config->getURL($rep, $path, 'rss').$isDirString.createRevAndPegString('', $peg);
		$vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
	}

	// Set up the tarball link
	$subs = explode('/', $path);
	$level = count($subs) - 2;
	if ($rep->isDownloadAllowed($path) && !isset($vars['warning'])) {
		$vars['downloadurl'] = $config->getURL($rep, $path, 'dl').$isDirString.$passRevString;
	}

	$vars['compare_form'] = '<form method="get" action="'.$config->getURL($rep, '', 'comp').'" id="compare">';
	if ($config->multiViews) {
		$vars['compare_form'] .= '<input type="hidden" name="op" value="comp"/>';
	} else {
		$vars['compare_form'] .= '<input type="hidden" name="repname" value="'.$repname.'" />';
	}
	$vars['compare_submit'] = '<input type="submit" value="'.$lang['COMPAREPATHS'].'" />';
	$vars['compare_endform'] = '</form>';

	$vars['showlastmod'] = $config->showLastModInListing();

	$listing = showTreeDir($svnrep, $path, $rev, $peg, array());

	if (!$rep->hasReadAccess($path, true)) {
		$vars['error'] = $lang['NOACCESS'];
		checkSendingAuthHeader($rep);
	}
	$vars['restricted'] = !$rep->hasReadAccess($path, false);

} else {
	header('HTTP/1.x 404 Not Found', true, 404);
}

renderTemplate('directory');
