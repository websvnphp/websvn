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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// --
//
// rss.php
//
// Creates an rss feed for the given repository number

require_once('include/setup.php');
require_once('include/svnlook.php');
require_once('include/utils.php');
require_once('include/template.php');

$isDir = @$_REQUEST['isdir'] == 1;

$maxmessages = 20;

// Find the base URL name
if ($config->multiViews) {
  $baseurl = '';
} else {
  $baseurl = dirname($_SERVER['PHP_SELF']);
  if ($baseurl != '' && $baseurl != DIRECTORY_SEPARATOR && $baseurl != "\\" && $baseurl != '/') {
    $baseurl .= '/';
  } else {
    $baseurl = '/';
  }
}

$svnrep = new SVNRepository($rep);

if ($path == '' || $path{0} != '/') {
  $ppath = '/'.$path;
} else {
  $ppath = $path;
}

// Make sure that the user has full access to the specified directory
if (!$rep->hasReadAccess($path, false)) {
  exit;
}

header('Content-Type: application/xml; charset=utf-8');

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, $rev, '', false, $maxmessages, $peg);

// Filename reflecting full path for a cached RSS feed for this particular query
$cache = $locwebsvnreal.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.strtr($rep->getDisplayName().$path, ":/\\?", "____").($peg ? '@'.$peg : '').($rev ? '_r'.$rev : '').'.rss.xml';

// If a recent-enough cached version exists, use it and avoid all the work below
if ($rep->getRSSCaching() && file_exists($cache) && filemtime($cache) >= $history->curEntry->committime) {
  readfile($cache);
  exit();
}

// Generate RSS 2.0 feed
$rss  = '<?xml version="1.0" encoding="utf-8"?>';
$rss .= '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
$rss .= '<title>'.htmlspecialchars($rep->getDisplayName().($path ? ' - '.$path : '')).'</title>';
$rss .= '<description>'.htmlspecialchars($lang['RSSFEEDTITLE'].' - '.$repname).'</description>';
$rss .= '<lastBuildDate>'.date('r').'</lastBuildDate>'; // RFC 2822 date format
$rss .= '<generator>WebSVN '.$vars['version'].'</generator>';
$rss .= '<link>'.getFullURL($baseurl.$config->getURL($rep, $path, 'log').createRevAndPegString($passrev, $peg)).'</link>'; // Matching WebSVN page
$rss .= '<atom:link href="'.htmlspecialchars(getFullURL($_SERVER['REQUEST_URI']), ENT_NOQUOTES).'" rel="self" type="application/rss+xml" />'; // Originating URL where this RSS feed can be found

if ($history && is_array($history->entries)) {
  foreach ($history->entries as $r) {
    $wordLimit = 10; // Display only up to the first 10 words of the log message
    $title = trim($r->msg);
    $words = explode(' ', $title, $wordLimit + 1);
    if (count($words) > $wordLimit) {
      $title = implode(' ', array_slice($words, 0, $wordLimit)).' ...';
    }
    $title = $lang['REV'].' '.$r->rev.' - '.$title;
    $description = '<div><strong>'.$r->author.' &mdash; '.count($r->mods).' '.$lang['FILESMODIFIED'].'</strong><br/>'.nl2br(create_anchors(str_replace('<', '&lt;', $r->msg))).'</div>';
    usort($r->mods, 'SVNLogEntry_compare');
    foreach ($r->mods as $modifiedResource) {
      switch ($modifiedResource->action) {
        case 'A': $description .= '+ '; break;
        case 'M': $description .= '~ '; break;
        case 'D': $description .= 'x '; break;
      }
      $description .= $modifiedResource->path.'<br />';
    }
    $itemLink = htmlspecialchars(html_entity_decode(getFullURL($baseurl.$config->getURL($rep, $r->path, 'revision').createRevAndPegString($r->rev, $peg).($isDir ? '&amp;isdir=1' : ''))));

    $rss .= '<item>';
    $rss .= '<pubDate>'.date('r', $r->committime).'</pubDate>';
    $rss .= '<dc:creator>'.htmlspecialchars($r->author).'</dc:creator>';
    $rss .= '<title>'.htmlspecialchars($title).'</title>';
    $rss .= '<description>'.htmlspecialchars($description).'</description>';
    $rss .= '<link>'.$itemLink.'</link>';
    $rss .= '<guid>'.$itemLink.'</guid>';
    $rss .= '</item>';
  }
}
$rss .= '</channel></rss>';

if ($rep->getRSSCaching()) {
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
