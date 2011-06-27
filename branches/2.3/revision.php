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
// revision.php
//
// Show the details for a given revision

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';
require_once 'include/bugtraq.php';

// Make sure that we have a repository
if ($rep) {
	$svnrep = new SVNRepository($rep);

	$ppath = ($path == '' || $path{0} != '/') ? '/'.$path : $path;
	createPathLinks($rep, $ppath, $rev, $peg);
	$passRevString = createRevAndPegString($rev, $peg);

	// Find the youngest revision containing changes for the given path
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
	$vars['youngestrev'] = $youngest;

	// TODO The "youngest" rev is often incorrect when both path and rev are specified.
	// If a path was last modified at rev M and the URL contains rev N, it uses rev N.

	// Unless otherwise specified, we get the log details of the latest change
	$lastChangedRev = ($rev) ? $rev : $youngest;
	if ($lastChangedRev != $youngest) {
		$history = $svnrep->getLog($path, $lastChangedRev, 1, false, 2, $peg);
		if (!$history) {
			header('HTTP/1.x 404 Not Found', true, 404);
			$vars['error'] = $lang['NOPATH'];
		}
	}
	if (empty($rev))
		$rev = $lastChangedRev;

	// Generate links to newer and older revisions
	$revurl = $config->getURL($rep, $path, 'revision');
	if ($rev < $youngest) {
		$vars['goyoungesturl'] = $config->getURL($rep, $path, 'revision');
		$vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'"'.($youngest ? ' title="'.$lang['REV'].' '.$youngest.'"' : '').'>'.$lang['GOYOUNGEST'].'</a>';

		$history2 = $svnrep->getLog($path, $rev, $youngest, false, 2, $peg);
		if (isset($history2->entries[1])) {
			$nextRev = $history2->entries[1]->rev;
			if ($nextRev != $youngest) {
				$vars['nextrev'] = $nextRev;
				$vars['nextrevurl'] = $revurl.createRevAndPegString($nextRev, $path != '/' ? $peg ? $peg : $rev : '');
				//echo 'NEXT='.$vars['nextrevurl'].'<br/>';
			}
		}
		unset($vars['error']);
	}
	if (isset($history->entries[1])) {
		$prevRev = $history->entries[1]->rev;
		$prevPath = $history->entries[1]->path;
		$vars['prevrev'] = $prevRev;
		$vars['prevrevurl'] = $revurl.createRevAndPegString($prevRev, $path != '/' ? ($peg ? $peg : $rev) : '');
		//echo 'PREV='.$vars['prevrevurl'].'<br/>';
	}
	// Save the entry from which we pull information for the current revision.
	$logEntry = (isset($history->entries[0])) ? $history->entries[0] : null;

	$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

	$vars['action'] = '';
	$vars['rev'] = $rev;
	$vars['peg'] = $peg;
	$vars['path'] = escape($ppath);
	if ($logEntry) {
		$vars['date'] = $logEntry->date;
		$vars['age'] = datetimeFormatDuration(time() - strtotime($logEntry->date));
		$vars['author'] = $logEntry->author;
		$vars['log'] = nl2br($bugtraq->replaceIDs(create_anchors(xml_entities($logEntry->msg))));
	}

	$isDir = @$_REQUEST['isdir'] == 1 || $path == '' || $path == '/';
	$vars['logurl'] = $config->getURL($rep, $path, 'log').$passRevString.($isDir ?  '&amp;isdir=1' : '');
	$vars['loglink'] = '<a href="'.$vars['logurl'].'">'.$lang['VIEWLOG'].'</a>';

	$dirPath = $isDir ? $path : dirname($path).'/';
	$vars['directoryurl'] = $config->getURL($rep, $dirPath, 'dir').$passRevString.'#'.anchorForPath($dirPath);
	$vars['directorylink'] = '<a href="'.$vars['directoryurl'].'">'.$lang['LISTING'].'</a>';

	if ($path != $dirPath) {
		$vars['filedetailurl'] = $config->getURL($rep, $path, 'file').$passRevString;
		$vars['filedetaillink'] = '<a href="'.$vars['filedetailurl'].'">'.$lang['FILEDETAIL'].'</a>';
		$vars['blameurl'] = $config->getURL($rep, $path, 'blame').$passRevString;
		$vars['blamelink'] = '<a href="'.$vars['blameurl'].'">'.$lang['BLAME'].'</a>';
	}

	if ($rep->isRssEnabled()) {
		$vars['rssurl'] = $config->getURL($rep, $path, 'rss').createRevAndPegString('', $peg);
		$vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
	}

	$changes = $logEntry ? $logEntry->mods : array();
	if (!is_array($changes)) {
		$changes = array();
	}
	usort($changes, 'SVNLogEntry_compare');

	$row = 0;

	$prevRevString = createRevAndPegString($rev - 1, $rev - 1);
	$thisRevString = createRevAndPegString($rev, $rev);
	foreach ($changes as $change) {
		$linkRevString = ($change->action == 'D') ? $prevRevString : $thisRevString;
		// NOTE: This is a hack (runs `svn info` on each path) to see if it's a file.
		// `svn log --verbose --xml` should really provide this info, but doesn't yet.
		$lastSeenRev = ($change->action == 'D') ? $rev - 1 : $rev;
		$isFile = $svnrep->isFile($change->path, $lastSeenRev, $lastSeenRev);
		if (!$isFile && $change->path != '/') {
			$change->path .= '/';
		}
		$resourceExisted = $change->action == 'M' || $change->copyfrom;
		$listing[] = array(
			'path' => $change->path,
			'oldpath' => $change->copyfrom ? $change->copyfrom.' @ '.$change->copyrev : '',
			'action' => $change->action,
			'added' => $change->action == 'A',
			'deleted' => $change->action == 'D',
			'modified' => $change->action == 'M',
			'detailurl' => $config->getURL($rep, $change->path, ($isFile ? 'file' : 'dir')).$linkRevString,
			// For deleted resources, the log link points to the previous revision.
			'logurl' => $config->getURL($rep, $change->path, 'log').$linkRevString.($isFile ? '' : '&amp;isdir=1'),
			'diffurl' => $resourceExisted ? $config->getURL($rep, $change->path, 'diff').$linkRevString : '',
			'blameurl' => $resourceExisted ? $config->getURL($rep, $change->path, 'blame').$linkRevString : '',
			'rowparity' => $row,
			'notinpath' => substr($change->path, 0, strlen($path)) != $path,
		);

		$row = 1 - $row;
	}

	if (isset($prevRev)) {
		$vars['compareurl'] = $config->getURL($rep, '', 'comp').'compare[]='.urlencode($prevPath).'@'.$prevRev. '&amp;compare[]='.urlencode($path).'@'.$rev;	
		$vars['comparelink'] = '<a href="'.$vars['compareurl'].'">'.$lang['DIFFPREV'].'</a>';
	}

	if (!$rep->hasReadAccess($path, true)) {
		$vars['error'] = $lang['NOACCESS'];
		checkSendingAuthHeader($rep);
	}
	$vars['restricted'] = !$rep->hasReadAccess($path, false);

} else {
	header('HTTP/1.x 404 Not Found', true, 404);
}

renderTemplate('revision');
