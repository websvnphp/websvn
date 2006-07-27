<?php

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004 Tim Armes
//
// RSS feed initial version by Lübbe Onken
// Modifications for the first official RSS feed release by Tim Armes
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

include("include/feedcreator.class.php");

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");

$isDir = (@$_REQUEST["isdir"] == 1)?1:0;

$maxmessages = 20;

// Find the base URL name
if ($config->multiViews)
{
   $baseurl = "";
}
else
{
   $baseurl = dirname($_SERVER["PHP_SELF"]);
   if ($baseurl != "" && $baseurl != DIRECTORY_SEPARATOR && $baseurl != "\\" && $baseurl != "/" )
      $baseurl .= "/";
   else
      $baseurl = "/";
}

$svnrep = new SVNRepository($rep->path);

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$url = $config->getURL($rep, $path, "log");
$listurl = $config->getURL($rep, $path, "dir");

$history = $svnrep->getHistory($path, $rev);

$cachename = strtr($svnrep->repPath, ":/\\", "___");
$cachename = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachename."_rssfeed";

$rss = new UniversalFeedCreator();
$rss->useCached($cachename);
$rss->title = $rep->name;
$rss->description = "${lang["RSSFEEDTITLE"]} - $repname";
$rss->link = getFullURL($baseurl.$listurl);
$rss->syndicationURL = $rss->link;

//$divbox = "<div>";
//$divfont = "<span>";

if ($maxmessages > count($history))
   $maxmessages = count($history);

for ($n = 0; $n < $maxmessages; $n++)
{
   $r = $history[$n];
   
   $log = $svnrep->getLogDetails($path, $r["rev"]);
   $changes = $svnrep->getChangedFiles($r["rev"]);
   $files = count($changes["added"]) + count($changes["deleted"]) + count($changes["updated"]);

   // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
   $rpath = $r["path"];
   if ($isDir && $rpath{strlen($rpath) - 1} != "/")
      $rpath .= "/";
   
   // Find the parent path (or the whole path if it's already a directory)
   $pos = strrpos($rpath, "/");
   $parent = substr($rpath, 0, $pos + 1);
 
   $url = $config->getURL($rep, $parent, "dir");
   
   $desc = $log["message"];
   $item = new FeedItem();
   
   // For the title, we show the first 10 words of the description
   $pos = 0;
   $len = strlen($desc);
   for ($i = 0; $i < 10; $i++)
   {
      if ($pos >= $len) break;
      
      $pos = strpos($desc, " ", $pos);
      
      if ($pos === FALSE) break;
      $pos++;
   }
   
   if ($pos !== FALSE)
   {
      $sdesc = substr($desc, 0, $pos) . "...";
   }
   else
   {
      $sdesc = $desc;
   }
   
   if ($desc == "") $sdesc = "${lang["REV"]} ${r["rev"]}";
   
   $item->title = "$sdesc";
   $item->link = getFullURL($baseurl."${url}rev=${r["rev"]}&amp;sc=$showchanged");
   $item->description = "<div><strong>${lang["REV"]} ${r["rev"]} - ${log["author"]}</strong> ($files ${lang["FILESMODIFIED"]})</div><div>".nl2br(create_anchors($desc))."</div>";
   $item->date = $log["committime"];
   $item->author = $log["author"];
     
   $rss->addItem($item);
}

// valid format strings are: RSS0.91, RSS1.0, RSS2.0, PIE0.1, MBOX, OPML
header("Content-Type: text/xml");
echo $rss->createFeed("RSS2.0");

?>
