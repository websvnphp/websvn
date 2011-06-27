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
// diff.php
//
// Show the differences between 2 revisions of a file.
//

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';

require_once 'include/diff_inc.php';

$vars['action'] = $lang['DIFF'];
$all = (@$_REQUEST['all'] == 1);
$ignoreWhitespace = $config->getIgnoreWhitespacesInDiff();
if (array_key_exists('ignorews', $_REQUEST)) {
  $ignoreWhitespace = (bool)$_REQUEST['ignorews'];
}

// Make sure that we have a repository
if ($rep) {
	$svnrep = new SVNRepository($rep);

	// If there's no revision info, go to the lastest revision for this path
	$history = $svnrep->getLog($path, 'HEAD', 1, false, 2, ($path == '/') ? '' : $peg);
	if (!$history) {
		unset($vars['error']);
		$history = $svnrep->getLog($path, '', '', false, 2, ($path == '/') ? '' : $peg);
	}
	$youngest = ($history && isset($history->entries[0])) ? $history->entries[0]->rev : false;

	if (empty($rev)) {
		$rev = $youngest;
	}

	$history = $svnrep->getLog($path, $rev, 1, false, 2, $peg);

	if ($path{0} != '/') {
		$ppath = '/'.$path;
	} else {
		$ppath = $path;
	}

	$prevrev = @$history->entries[1]->rev;

	$vars['path'] = escape($ppath);
	$vars['rev1'] = $rev;
	$vars['rev2'] = $prevrev;
	$vars['prevrev'] = $prevrev;

	if (isset($history->entries[0])) {
		$vars['log'] = xml_entities($history->entries[0]->msg);
		$vars['date'] = $history->entries[0]->date;
		$vars['age'] = datetimeFormatDuration(time() - strtotime($history->entries[0]->date));
		$vars['author'] = $history->entries[0]->author;
		$vars['rev'] = $vars['rev1'] = $history->entries[0]->rev;
		$vars['peg'] = $peg;
	}

	createPathLinks($rep, $ppath, $passrev, $peg);
	$passRevString = createRevAndPegString($rev, $peg);

	$passIgnoreWhitespace = '';
	if ($ignoreWhitespace != $config->getIgnoreWhitespacesInDiff()) {
		$passIgnoreWhitespace = '&amp;ignorews='.($ignoreWhitespace ? '1' : '0');
	}

	if ($rev != $youngest) {
		$vars['goyoungesturl'] = $config->getURL($rep, $path, 'diff').createRevAndPegString('', $peg).$passIgnoreWhitespace;
		$vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'"'.($youngest ? ' title="'.$lang['REV'].' '.$youngest.'"' : '').'>'.$lang['GOYOUNGEST'].'</a>';
	}

	$revurl = $config->getURL($rep, $path, 'diff');
	if ($rev < $youngest) {
		$history2 = $svnrep->getLog($path, $rev, $youngest, false, 2, $peg ? $peg : 'HEAD');
		if (isset($history2->entries[1])) {
			$nextRev = $history2->entries[1]->rev;
			if ($nextRev != $youngest) {
				$vars['nextrev'] = $nextRev;
				$vars['nextrevurl'] = $revurl.createRevAndPegString($nextRev, $peg).$passIgnoreWhitespace;
			}
		}
		unset($vars['error']);
	}

	if (isset($history->entries[1])) {
		$prevRev = $history->entries[1]->rev;
		$prevPath = $history->entries[1]->path;
		$vars['prevrev'] = $prevRev;
		$vars['prevrevurl'] = $revurl.createRevAndPegString($prevRev, $peg).$passIgnoreWhitespace;
	}

	$vars['revurl'] = $config->getURL($rep, $path, 'revision').$passRevString;
	$vars['revlink'] = '<a href="'.$vars['revurl'].'">'.$lang['LASTMOD'].'</a>';

	$vars['logurl'] = $config->getURL($rep, $path, 'log').$passRevString;
	$vars['loglink'] = '<a href="'.$vars['logurl'].'">'.$lang['VIEWLOG'].'</a>';

	$vars['filedetailurl'] = $config->getURL($rep, $path, 'file').$passRevString;
	$vars['filedetaillink'] = '<a href="'.$vars['filedetailurl'].'">'.$lang['FILEDETAIL'].'</a>';

	$vars['blameurl'] = $config->getURL($rep, $path, 'blame').$passRevString;
	$vars['blamelink'] = '<a href="'.$vars['blameurl'].'">'.$lang['BLAME'].'</a>';

	if ($rep->isRssEnabled()) {
		$vars['rssurl'] = $config->getURL($rep, $path, 'rss').createRevAndPegString('', $peg);
		$vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
	}

	// Check for binary file type before diffing.
	$svnMimeType = $svnrep->getProperty($path, 'svn:mime-type', $rev);

	// If no previous revision exists, bail out before diffing
	if (!$rep->getIgnoreSvnMimeTypes() && preg_match('~application/*~', $svnMimeType)) {
		$vars['warning'] = 'Cannot display diff of binary file. (svn:mime-type = '.$svnMimeType.')';

	} else if (!$prevrev) {
		$vars['noprev'] = 1;

	} else {
		$diff = $config->getURL($rep, $path, 'diff').$passRevString;

		if ($all) {
			$vars['showcompactlink'] = '<a href="'.$diff.$passIgnoreWhitespace.'">'.$lang['SHOWCOMPACT'].'</a>';
		} else {
			$vars['showalllink'] = '<a href="'.$diff.$passIgnoreWhitespace.'&amp;all=1'.'">'.$lang['SHOWENTIREFILE'].'</a>';
		}
		$passShowAll = ($all ? '&amp;all=1' : '');
		$toggleIgnoreWhitespace = '';
		if ($ignoreWhitespace == $config->getIgnoreWhitespacesInDiff()) {
			$toggleIgnoreWhitespace = '&amp;ignorews='.($ignoreWhitespace ? '0' : '1');
		}
		if ($ignoreWhitespace) {
			$vars['regardwhitespacelink'] = '<a href="'.$diff.$passShowAll.$toggleIgnoreWhitespace.'">'.$lang['REGARDWHITESPACE'].'</a>';
		} else {
			$vars['ignorewhitespacelink'] = '<a href="'.$diff.$passShowAll.$toggleIgnoreWhitespace.'">'.$lang['IGNOREWHITESPACE'].'</a>';
		}

		// Get the contents of the two files
		$newerFile = tempnamWithCheck($config->getTempDir(), '');
		$newerFileHl = $newerFile.'highlight';
		$normalNew = $svnrep->getFileContents($history->entries[0]->path, $newerFile, $history->entries[0]->rev, $peg, '', 'no');
		$highlightedNew = $svnrep->getFileContents($history->entries[0]->path, $newerFileHl, $history->entries[0]->rev, $peg, '', 'line');

		$olderFile = tempnamWithCheck($config->getTempDir(), '');
		$olderFileHl = $olderFile.'highlight';
		$normalOld = $svnrep->getFileContents($history->entries[0]->path, $olderFile, $history->entries[1]->rev, $peg, '', 'no');
		$highlightedOld = $svnrep->getFileContents($history->entries[0]->path, $olderFileHl, $history->entries[1]->rev, $peg, '', 'line');
		// TODO: Figured out why diffs across a move/rename are currently broken.

		$highlighted = ($highlightedNew && $highlightedOld);
		if ($highlighted) {
			$listing = do_diff($all, $ignoreWhitespace, $highlighted, $newerFile, $olderFile, $newerFileHl, $olderFileHl);
		} else {
			$listing = do_diff($all, $ignoreWhitespace, $highlighted, $newerFile, $olderFile, null, null);
		}

		// Remove our temporary files
		@unlink($newerFile);
		@unlink($olderFile);
		@unlink($newerFileHl);
		@unlink($olderFileHl);
	}

	if (!$rep->hasReadAccess($path, false)) {
		$vars['error'] = $lang['NOACCESS'];
		checkSendingAuthHeader($rep);
	}

} else {
	header('HTTP/1.x 404 Not Found', true, 404);
}

renderTemplate('diff');
