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
// rss.php
//
// Creates an rss feed for the given repository number

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';
require_once 'include/bugtraq.php';

$max = (int)@$_REQUEST['max'];
if ($max == 0)
	$max = $config->getRssMaxEntries();
$quiet = (isset($_REQUEST['quiet']));

// Find the base URL name
if ($config->multiViews) {
	$baseurl = '';
} else {
	$baseurl = dirname($_SERVER['PHP_SELF']);
	if ($baseurl != '' && $baseurl != DIRECTORY_SEPARATOR && $baseurl != '\\' && $baseurl != '/') {
		$baseurl .= '/';
	} else {
		$baseurl = '/';
	}
}

if ($path == '' || $path{0} != '/') {
	$ppath = '/'.$path;
} else {
	$ppath = $path;
}

if (!$rep) {
	header('HTTP/1.x 404 Not Found', true, 404);
	print 'Unable to access resource at path: '.xml_entities($path);
	exit;
}
// Make sure that the user has full access to the specified directory
if (!$rep->hasReadAccess($path, false)) {
	header('HTTP/1.x 403 Forbidden', true, 403);
	print 'Unable to access resource at path: '.xml_entities($path);
	exit;
}

// If there's no revision info, go to the lastest revision for this path
$svnrep = new SVNRepository($rep);
$history = $svnrep->getLog($path, $rev, '', false, $max, $peg);
if (!$history) {
	header('HTTP/1.x 404 Not Found', true, 404);
	echo $lang['NOPATH'];
	exit;
}

header('Content-Type: application/xml; charset=utf-8');

if ($rep->isRssCachingEnabled()) {
	// Filename for storing a cached RSS feed for this particular path/revision
	$cache = $locwebsvnreal.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.strtr($rep->getDisplayName().$path, '\\/:*?"<>|.', '__________').($peg ? '@'.$peg : '').($rev ? '_r'.$rev : '').'m'.$max.($quiet ? 'q' : '').'.rss.xml';
	// If a recent-enough cached version exists, use it and avoid the work below
	if (file_exists($cache) && filemtime($cache) >= $history->curEntry->committime) {
		readfile($cache);
		exit();
	}
}

$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

// Generate RSS 2.0 feed
$rss = '<?xml version="1.0" encoding="utf-8"?>';
$rss .= '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
$rss .= '<title>'.escape($rep->getDisplayName().($path ? ' - '.$path : '')).'</title>';
$rss .= '<description>'.escape($lang['RSSFEEDTITLE'].' - '.$repname).'</description>';
$rss .= '<lastBuildDate>'.date('r').'</lastBuildDate>'; // RFC 2822 date format
$rss .= '<generator>WebSVN '.$vars['version'].'</generator>';
// URL for matching WebSVN log page
$rss .= '<link>'.getFullURL($baseurl.$config->getURL($rep, $path, 'log').(@$_REQUEST['isdir'] == 1 ? 'isdir=1&amp;' : '').'max='.$max.'&amp;'.createRevAndPegString($passrev, $peg)).'</link>';
// URL where this original RSS feed can be found
$rss .= '<atom:link href="'.escape(getFullURL($_SERVER['REQUEST_URI'])).'" rel="self" type="application/rss+xml" />'."\n";
if (is_array($history->entries)) {
	$itemURL = $baseurl.$config->getURL($rep, $path, 'revision');
	if (@$_REQUEST['isdir'] == 1 || $path == '' || $path == '/')
		$itemURL .= 'isdir=1&amp;';
	foreach ($history->entries as $r) {
		$wordLimit = 10; // Display only up to the first 10 words of the log message
		$title = trim($r->msg);
		$title = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $title);
		$words = explode(' ', $title, $wordLimit + 1);
		if (count($words) > $wordLimit) {
			$title = implode(' ', array_slice($words, 0, $wordLimit)).' ...';
		}
		$title = $lang['REV'].' '.$r->rev.' -- '.$title;
		$description = '<div><strong>'.$r->author.' -- '.count($r->mods).' '.$lang['FILESMODIFIED'].'</strong><br/>'.nl2br($bugtraq->replaceIDs(create_anchors(str_replace('<', '&lt;', $r->msg)))).'</div>';
		if (!$quiet) {
			usort($r->mods, 'SVNLogEntry_compare');
			foreach ($r->mods as $modifiedResource) {
				switch ($modifiedResource->action) {
					case 'A': $description .= '+ '; break;
					case 'M': $description .= '~ '; break;
					case 'D': $description .= 'x '; break;
				}
				$description .= $modifiedResource->path;
				if ($modifiedResource->copyfrom != '') {
					$description .= ' <i>(copied from '.$modifiedResource->copyfrom.'@'.$modifiedResource->copyrev.')</i>';
				}
				$description .= '<br />';
			}
		}

		// skip items with no access
		if ($r->committime) {
			$rss .= '<item>';
			$rss .= '<pubDate>'.date('r', $r->committime).'</pubDate>';
			$rss .= '<dc:creator>'.escape($r->author).'</dc:creator>';
			$rss .= '<title>'.escape($title).'</title>';
			$rss .= '<description>'.escape($description).'</description>';
			$itemLink = getFullURL($itemURL.createRevAndPegString($r->rev,$peg));
			$rss .= '<link>'.$itemLink.'</link>';
			$rss .= '<guid>'.$itemLink.'</guid>';
			$rss .= '</item>'."\n";
		}
	}
}
$rss .= '</channel></rss>';

if ($rep->isRssCachingEnabled()) {
	$file = fopen($cache, 'w+');
	if ($file) {
		fputs($file, $rss);
		fclose($file);
		touch($cache, $history->curEntry->committime); // set timestamp to commit time
	} else {
		echo 'Error creating RSS cache file, please check write permissions.';
	}
}
echo $rss;
