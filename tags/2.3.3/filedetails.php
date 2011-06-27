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
// filedetails.php
//
// Simply lists the contents of a file

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';

// Make sure that we have a repository
if ($rep) {
	$svnrep = new SVNRepository($rep);

	if ($path{0} != '/') {
		$ppath = '/'.$path;
	} else {
		$ppath = $path;
	}

	$useMime = false;

	// If there's no revision info, go to the lastest revision for this path
	$history = $svnrep->getLog($path, 'HEAD', 1, false, 2, ($path == '/') ? '' : $peg);
	if (!$history) {
		unset($vars['error']);
		$history = $svnrep->getLog($path, '', '', false, 2, ($path == '/') ? '' : $peg);
		if (!$history) {
			header('HTTP/1.x 404 Not Found', true, 404);
			$vars['error'] = $lang['NOPATH'];
		}
	}
	$youngest = ($history && isset($history->entries[0])) ? $history->entries[0]->rev : false;

	if (empty($rev)) {
		$rev = !$peg ? $youngest : min($peg, $youngest);
	}

	$extn = strtolower(strrchr($path, '.'));

	// Check to see if the user has requested that this type be zipped and sent
	// to the browser as an attachment

	if ($history && isset($zipped) && in_array($extn, $zipped) && $rep->hasReadAccess($path, false)) {
		$base = basename($path);
		header('Content-Type: application/x-gzip');
		header('Content-Disposition: attachment; filename='.urlencode($base).'.gz');

		// Get the file contents and pipe into gzip. All this without creating
		// a temporary file. Damn clever.
		$svnrep->getFileContents($path, '', $rev, $peg, '| '.$config->gzip.' -n -f');
		exit;
	}

	// Check to see if we should serve it with a particular content-type.
	// The content-type could come from an svn:mime-type property on the
	// file, or from the $contentType array in setup.php.

	if (!$rep->getIgnoreSvnMimeTypes()) {
		$svnMimeType = $svnrep->getProperty($path, 'svn:mime-type', $rev);
	}

	if (!$rep->getIgnoreWebSVNContentTypes()) {
		$setupContentType = @$contentType[$extn];
	}

	// Use the documented priorities when establishing what content-type to use.
	if (!empty($svnMimeType) && $svnMimeType != 'application/octet-stream') {
		$mimeType = $svnMimeType;
	} else if (!empty($setupContentType)) {
		$mimeType = $setupContentType;
	} else if (!empty($svnMimeType)) {
		$mimeType = $svnMimeType; // Use SVN's default of 'application/octet-stream'
	} else {
		$mimeType = '';
	}

	$useMime = ($mimeType) ? @$_REQUEST['usemime'] : false;
	if ($history && !empty($mimeType) && !$useMime) {
		$useMime = $mimeType; // Save MIME type for later before possibly clobbering
		// If a MIME type exists but is set to be ignored, set it to an empty string.
		foreach ($config->inlineMimeTypes as $inlineType) {
			if (preg_match('|'.$inlineType.'|', $mimeType)) {
				$mimeType = '';
				break;
			}
		}
	}

	// If a MIME type is associated with the file, deliver with Content-Type header.
	if ($history && !empty($mimeType) && $rep->hasReadAccess($path, false)) {
		$base = basename($path);
		header('Content-Type: '.$mimeType);
		//header('Content-Length: '.$size);
		header('Content-Disposition: inline; filename='.urlencode($base));
		$svnrep->getFileContents($path, '', $rev, $peg);
		exit;
	}

	// Display the file inline using WebSVN.

	$vars['action'] = '';
	$vars['path'] = escape($ppath);

	if (isset($history->entries[0])) {
		$vars['log'] = xml_entities($history->entries[0]->msg);
		$vars['date'] = $history->entries[0]->date;
		$vars['age'] = datetimeFormatDuration(time() - strtotime($history->entries[0]->date));
		$vars['author'] = $history->entries[0]->author;
	}
	createPathLinks($rep, $ppath, !$passrev && $peg ? $rev : $passrev, $peg);
	$passRevString = createRevAndPegString($rev, $peg);

	if ($rev != $youngest) {
		$vars['goyoungesturl'] = $config->getURL($rep, $path, 'file').createRevAndPegString($youngest, $peg);
		$vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'"'.($youngest ? ' title="'.$lang['REV'].' '.$youngest.'"' : '').'>'.$lang['GOYOUNGEST'].'</a>';
	}

	$revurl = $config->getURL($rep, $path, 'file');
	if ($rev < $youngest) {
		$history2 = $svnrep->getLog($path, $rev, $youngest, false, 2, $peg ? $peg : 'HEAD');
		if (isset($history2->entries[1])) {
			$nextRev = $history2->entries[1]->rev;
			if ($nextRev != $youngest) {
				$vars['nextrev'] = $nextRev;
				$vars['nextrevurl'] = $revurl.createRevAndPegString($nextRev, $peg);
			}
		}
		unset($vars['error']);
	}

	$history3 = $svnrep->getLog($path, $rev, 1, false, 2, $peg ? $peg : 'HEAD');
	if (isset($history3->entries[1])) {
		$prevRev = $history3->entries[1]->rev;
		$prevPath = $history3->entries[1]->path;
		$vars['prevrev'] = $prevRev;
		$vars['prevrevurl'] = $revurl.createRevAndPegString($prevRev, $peg);
	}
	unset($vars['error']);

	$vars['revurl'] = $config->getURL($rep, $path, 'revision').$passRevString;
	$vars['revlink'] = '<a href="'.$vars['revurl'].'">'.$lang['LASTMOD'].'</a>';

	$vars['logurl'] = $config->getURL($rep, $path, 'log').$passRevString;
	$vars['loglink'] = '<a href="'.$vars['logurl'].'">'.$lang['VIEWLOG'].'</a>';

	$vars['blameurl'] = $config->getURL($rep, $path, 'blame').$passRevString;
	$vars['blamelink'] = '<a href="'.$vars['blameurl'].'">'.$lang['BLAME'].'</a>';

	if ($history == null || count($history->entries) > 1) {
		$vars['diffurl'] = $config->getURL($rep, $path, 'diff').$passRevString;
		$vars['difflink'] = '<a href="'.$vars['diffurl'].'">'.$lang['DIFFPREV'].'</a>';
	}

	if ($rep->isDownloadAllowed($path)) {
		$vars['downloadlurl'] = $config->getURL($rep, $path, 'dl').$passRevString;
		$vars['downloadlink'] = '<a href="'.$vars['downloadlurl'].'">'.$lang['DOWNLOAD'].'</a>';
	}

	if ($rep->isRssEnabled()) {
		$vars['rssurl'] = $config->getURL($rep, $path, 'rss').createRevAndPegString('', $peg);
		$vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
	}

	$mimeType = $useMime; // Restore preserved value to use for 'mimelink' variable.
	// If there was a MIME type, create a link to display file with that type.
	if ($mimeType && !isset($vars['warning'])) {
		$vars['mimeurl'] = $config->getURL($rep, $path, 'file').'usemime=1&amp;'.$passRevString;
		$vars['mimelink'] = '<a href="'.$vars['mimeurl'].'">'.$lang['VIEWAS'].' "'.$mimeType.'"</a>';
	}

	$vars['rev'] = escape($rev);
	$vars['peg'] = $peg;

	if (!$rep->hasReadAccess($path, true)) {
		$vars['error'] = $lang['NOACCESS'];
		checkSendingAuthHeader($rep);
	} else if (!$svnrep->isFile($path, $rev, $peg)) {
		header('HTTP/1.x 404 Not Found', true, 404);
		$vars['error'] = $lang['NOPATH'];
	}

} else {
	header('HTTP/1.x 404 Not Found', true, 404);
}

// $listing is populated with file data when file.tmpl calls [websvn-getlisting]

renderTemplate('file');
