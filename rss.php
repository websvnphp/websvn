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

include('lib/feedcreator.class.php');

require_once('include/setup.php');
require_once('include/svnlook.php');
require_once('include/utils.php');
require_once('include/template.php');

$isDir = @$_REQUEST['isdir'] == 1;

$maxmessages = 20;

// valid format strings are: RSS0.91, RSS1.0, RSS2.0, PIE0.1 (deprecated), MBOX, OPML, ATOM, ATOM0.3, HTML, JS
$feedformat = 'RSS2.0';

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

$rss = new UniversalFeedCreator();
$rss->title = $rep->getDisplayName().($path ? ' - '.$path : '');
$rss->description = $lang['RSSFEEDTITLE'].' - '.$repname;
$rss->link = htmlspecialchars(html_entity_decode(getFullURL($baseurl.$config->getURL($rep, $path, 'log').createRevAndPegString($passrev, $peg))));
$rss->syndicationURL = $rss->link;

if ($history && is_array($history->entries)) {
  foreach ($history->entries as $r) {
    // For the title, display only up to the first 10 words of the description.
    $title = trim($r->msg);
    if ($title == '') {
      $title = $lang['REV'].' '.$r->rev;
    } else {
      $wordLimit = 10;
      $words = explode(' ', $title, $wordLimit + 1);
      if (count($words) > $wordLimit) {
        $title = implode(' ', array_slice($words, 0, $wordLimit)).' ...';
      }
    }
    // Description includes rev number/author/message and changes sorted by path
    $description = '<div><strong>'.$lang['REV'].' '.$r->rev.' - '.$r->author.'</strong> ('.count($r->mods).' '.$lang['FILESMODIFIED'].')</div><div>'.nl2br(create_anchors($r->msg)).'</div>';
    usort($r->mods, 'SVNLogEntry_compare');
    foreach ($r->mods as $modifiedResource) {
      switch ($modifiedResource->action) {
        case 'A': $description .= '+ '; break;
        case 'M': $description .= '~ '; break;
        case 'D': $description .= 'x '; break;
      }
      $description .= $modifiedResource->path.'<br />';
    }
    // Create a new item and add it to the RSS feed
    $item = new FeedItem();
    $item->title = $title;
    $item->description = $description;
    $item->date = $r->committime;
    $item->author = $r->author;
    $item->link = html_entity_decode(getFullURL($baseurl.$config->getURL($rep, $r->path, 'revision').createRevAndPegString($r->rev, $peg).($isDir ? '&amp;isdir=1' : '')));
    $item->guid = $item->link;

    $rss->addItem($item);
  }
}

if ($rep->getRSSCaching()) {
  @$rss->saveFeed($feedformat, $cache, false);
  touch($cache, $history->curEntry->committime); // set timestamp to commit time
}
echo @$rss->createFeed($feedformat);
