<?php

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004 Tim Armes
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
// log.php
//
// Show the logs for the given path

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require("include/template.inc");

$rep = (int)@$_REQUEST["rep"];
$path = escapeshellcmd(@$_REQUEST["path"]);
$rev = (int)@$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;
$page = (int)@$_REQUEST["page"];
$all = (@$_REQUEST["all"] == 1)?1:0;
$isDir = (@$_REQUEST["isdir"] == 1)?1:0;

if (empty($page)) $page = 1;

$maxperpage = 20;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);
$youngest = $history[0]["rev"];

if (empty($rev))
   $rev = $youngest;

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$vars["action"] = $lang["LOG"];
$vars["repname"] = $repname;
$vars["rev"] = $rev;
$vars["path"] = $ppath;

$logurl = $config->getURL($rep, $path, "log");

if ($rev != $youngest)
   $vars["goyoungestlink"] = "<a href=\"${logurl}sc=1\">${lang["GOYOUNGEST"]}</a>";
else
   $vars["goyoungestlink"] = "";

$history = $svnrep->getHistory($path, $rev);

// Get the number of separate revisions
$revisions = count($history);

if ($all)
{
   $firstrevindex = 0;
   $lastrevindex = $revisions - 1;
   $pages = 1;
}
else
{
   // Calculate the number of pages
   $pages = floor($revisions / $maxperpage);
   if (($revisions % $maxperpage) > 0) $pages++;
   
   if ($page > $pages) $page = $pages;
   
   // Word out where to start and stop
   $firstrevindex = ($page - 1) * $maxperpage;
   $lastrevindex = $firstrevindex + $maxperpage - 1;
   if ($lastrevindex > $revisions - 1) $lastrevindex = $revisions - 1;
}

$row = 0;
$index = 0;
$listing = array();

for ($n = $firstrevindex; $n <= $lastrevindex; $n++)
{
   $r = $history[$n];
   
   $log = $svnrep->getLogDetails($path, $r["rev"]);

   // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
   $rpath = $r["path"];
   if ($isDir && $rpath{strlen($rpath) - 1} != "/")
      $rpath .= "/";

   // Find the parent path (or the whole path if it's already a directory)
   $pos = strrpos($rpath, "/");
   $parent = substr($rpath, 0, $pos + 1);

   $url = $config->getURL($rep, $parent, "dir");
   $listing[$index]["revlink"] = "<a href=\"${url}rev=${r["rev"]}&sc=1\">${r["rev"]}</a>";

   if ($isDir)
   {
      $url = $config->getURL($rep, $rpath, "dir"); 
      $listing[$index]["revpathlink"] = "<a href=\"${url}rev=${r["rev"]}&sc=$showchanged\">$rpath</a>";
   }
   else
   {
      $url = $config->getURL($rep, $rpath, "file"); 
      $listing[$index]["revpathlink"] = "<a href=\"${url}rev=${r["rev"]}&sc=$showchanged\">$rpath</a>";
   }
      
   $listing[$index]["revauthor"] = $log["author"];
   $listing[$index]["revage"] = $log["age"];
   $listing[$index]["revlog"] = nl2br($log["message"]);
   $listing[$index]["rowparity"] = "$row";;

   $row = 1 - $row;
   $index++;
}

// Work out the paging options

$vars["pagelinks"] = "";
$vars["showalllink"] = "";

if ($pages > 1)
{
   $prev = $page - 1;
   $next = $page + 1;
   echo "<p><center>";
      
   if ($page > 1) $vars["pagelinks"] .= "<a href=\"${logurl}rev=$rev&sc=$showchanged&page=$prev\"><&nbsp;${lang["PREV"]}</a> ";
   for ($p = 1; $p <= $pages; $p++)
   {
      if ($p != $page)
         $vars["pagelinks"].= "<a href=\"${logurl}rev=$rev&sc=$showchanged&page=$p\">$p</a> "; 
      else
         $vars["pagelinks"] .= "<b>$p </b>";
   }
   if ($page < $pages) $vars["pagelinks"] .=" <a href=\"${logurl}rev=$rev&sc=$showchanged&page=$next\">${lang["NEXT"]}&nbsp;></a>";   
   
   $vars["showalllink"] = "<a href=\"${logurl}rev=$rev&sc=$showchanged&all=1\">${lang["SHOWALL"]}</a>";
   echo "</center>";
}

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."log.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);

?>