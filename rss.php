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

$listurl = $config->getURL($rep, $path, 'dir');

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, $rev, '', false, $maxmessages);
if (is_string($history)) {
  echo $history;
  exit;
}

// Cachename reflecting full path to and rev for rssfeed. Must end with xml to work
$cachename = strtr(getFullURL($listurl), ":/\\?", "____");
$cachename = $locwebsvnreal.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$cachename.$rev.'_rssfeed.xml';

$rss = new UniversalFeedCreator();
$rss->useCached($feedformat, $cachename);
$rss->title = $rep->getDisplayName();
$rss->description = $lang['RSSFEEDTITLE'].' - '.$repname;
$rss->link = htmlspecialchars(html_entity_decode(getFullURL($baseurl.$listurl)));
$rss->syndicationURL = $rss->link;
$rss->xslStyleSheet = ''; //required for UniversalFeedCreator since 1.7
$rss->cssStyleSheet = ''; //required for UniversalFeedCreator since 1.7

if ($history && is_array($history->entries)) {
  foreach ($history->entries as $r) {
    $thisrev = $r->rev;
    $changes = $r->mods;
    $files = count($changes);

    // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
    $rpath = $r->path;
    if ($isDir && $rpath{strlen($rpath) - 1} != '/') {
      $rpath .= '/';
    }

    // Find the parent path (or the whole path if it's already a directory)
    $pos = strrpos($rpath, '/');
    $parent = substr($rpath, 0, $pos + 1);

    $url = $config->getURL($rep, $parent, 'revision');

    $desc = $r->msg;
    $item = new FeedItem();

    // For the title, we show the first 10 words of the description
    $pos = 0;
    $len = strlen($desc);
    for ($i = 0; $i < 10; $i++) {
      if ($pos >= $len) {
        break;
      }

      $pos = strpos($desc, ' ', $pos);

      if ($pos === false) {
        break;
      }
      $pos++;
    }

    if ($pos !== false) {
      $desc = substr($desc, 0, $pos).'...';
    }

    if ($desc == '') {
      $desc = $lang['REV'].' '.$thisrev;
    }

    $item->title = $desc;
    $item->link = html_entity_decode(getFullURL($baseurl.$url.'rev='.$thisrev));
    $item->description = '<div><strong>'.$lang['REV'].' '.$thisrev.' - '.$r->author.'</strong> ('.$files.' '.$lang['FILESMODIFIED'].')</div><div>'.nl2br(create_anchors($r->msg)).'</div>';

    if (true) {
      usort($changes, 'SVNLogEntry_compare');

      foreach ($changes as $file) {
        switch ($file->action) {
          case 'A': $item->description .= '+ '; break;
          case 'M': $item->description .= '~ '; break;
          case 'D': $item->description .= '- '; break;
        }
        $item->description .= $file->path.'<br />';
      }
    }

    $item->date = $r->committime;
    $item->author = $r->author;
    $item->guid = $item->link;

    $rss->addItem($item);
  }
}

// Save the feed
@$rss->saveFeed($feedformat, $cachename, false);
header('Content-Type: application/xml');
echo @$rss->createFeed($feedformat);
