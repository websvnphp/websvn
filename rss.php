<?php
# vim:et:ts=3:sts=3:sw=3:fdm=marker:

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright © 2004-2006 Tim Armes, Matt Sicker
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

$svnrep = new SVNRepository($rep);

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

// Make sure that the user has full access to the specified directory
if (!$rep->hasReadAccess($path, false))
   exit;

$url = $config->getURL($rep, $path, "log");
$listurl = $config->getURL($rep, $path, "dir");

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, $rev, "", false, 20);
$youngest = $history->entries[0]->rev;

// Cachename reflecting full path to and rev for rssfeed. Must end with xml to work
$cachename = strtr(getFullURL($listurl), ":/\\?", "____");
$cachename = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachename.@$_REQUEST["rev"]."_rssfeed.xml";

$rss = new UniversalFeedCreator();
$rss->useCached("RSS2.0", $cachename);
$rss->title = $rep->getDisplayName();
$rss->description = "${lang["RSSFEEDTITLE"]} - $repname";
$rss->link = html_entity_decode(getFullURL($baseurl.$listurl));
$rss->syndicationURL = $rss->link;
$rss->xslStyleSheet = ""; //required for UniversalFeedCreator since 1.7
$rss->cssStyleSheet = ""; //required for UniversalFeedCreator since 1.7

//$divbox = "<div>";
//$divfont = "<span>";

foreach ($history->entries as $r)
{
   $thisrev = $r->rev;
   $changes = $r->mods;
   $files = count($changes);

   // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
   $rpath = $r->path;
   if ($isDir && $rpath{strlen($rpath) - 1} != "/")
      $rpath .= "/";
   
   // Find the parent path (or the whole path if it's already a directory)
   $pos = strrpos($rpath, "/");
   $parent = substr($rpath, 0, $pos + 1);
 
   $url = $config->getURL($rep, $parent, "dir");
   
   $desc = $r->msg;
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
   
   if ($desc == "") $sdesc = "${lang["REV"]} $thisrev";
   
   $item->title = "$sdesc";
   $item->link = html_entity_decode(getFullURL($baseurl."${url}rev=$thisrev&amp;sc=$showchanged"));
   $item->description = "<div><strong>${lang["REV"]} $thisrev - ".$r->author."</strong> ($files ${lang["FILESMODIFIED"]})</div><div>".nl2br(create_anchors($desc))."</div>";

   if ($showchanged)
   {
      foreach ($changes as $file)
      {
         switch ($file->action)
         {
            case "A":
               $item->description .= "+ ".$file->path."<br />";  
               break;
                                     
            case "M":
               $item->description .= "~ ".$file->path."<br />";  
               break;
                                     
   
            case "D":
               $item->description .= "-".$file->path."<br />";  
               break;
                                     
         }
      }
   }

   $item->date = $r->committime;
   $item->author = $r->author;
     
   $rss->addItem($item);
}

// valid format strings are: RSS0.91, RSS1.0, RSS2.0, PIE0.1, MBOX, OPML

// Save the feed
$rss->saveFeed("RSS2.0",$cachename, false);
header("Content-Type: application/xml");
echo $rss->createFeed("RSS2.0");

?>
