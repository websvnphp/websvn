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

require("include/config.inc");
require("include/svnlook.inc");

include("templates/header.php");

$rep = @$_REQUEST["rep"];
$path = @$_REQUEST["path"];
$rev = @$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;
$page = @$_REQUEST["page"];
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
$log = $svnrep->getLogDetails($path, $rev);

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

echo "<h1>$repname - Rev ${log["rev"]} - $ppath</h1>";
echo "<p>";
   
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

echo "<table border=1 class=\"outlined\" width=\"100%\" cellpadding=2><tr><th>${lang["REV"]}</th><th>${lang["PATH"]}</th><th>${lang["AUTHOR"]}</th><th>${lang["LOGMSG"]}</th></tr>";

for ($n = $firstrevindex; $n <= $lastrevindex; $n++)
{
   $r = $history[$n];
   echo "<tr>";
   
   $log = $svnrep->getLogDetails($path, $r["rev"]);

   // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
   $rpath = $r["path"];
   if ($isDir && $rpath{strlen($rpath) - 1} != "/")
      $rpath .= "/";

   // Find the parent path (or the whole path if it's already a directory)
   $pos = strrpos($rpath, "/");
   $parent = substr($rpath, 0, $pos + 1);

   echo "<td valign=\"top\"><a href=\"listing.php?rep=$rep&path=$parent&rev=${r["rev"]}&sc=1\">${r["rev"]}</a></td>";

   if ($isDir)
      echo "<td valign=\"top\"><a href=\"listing.php?rep=$rep&path=$rpath&rev=${r["rev"]}&sc=$showchanged\">$rpath</a></td>";
   else
      echo "<td valign=\"top\"><a href=\"filedetails.php?rep=$rep&path=$rpath&rev=${r["rev"]}&sc=$showchanged\">$rpath</a></td>";
      
   echo "<td valign=\"top\">".nl2br($log["author"])."</td>";
   echo "<td valign=\"top\">".nl2br($log["message"])."</td>";
   
   echo "</tr>";
}

echo "</table>";

// Write out the paging options
if ($pages > 1)
{
   $prev = $page - 1;
   $next = $page + 1;
   echo "<p><center>";
   if ($page > 1) echo "<a href=\"log.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&page=$prev\"><&nbsp;${lang["PREV"]}</a> ";
   for ($p = 1; $p <= $pages; $p++)
   {
      if ($p != $page)
         echo "<a href=\"log.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&page=$p\">$p</a> "; 
      else
         echo "<b>$p </b>";
   }
   if ($page < $pages) echo " <a href=\"log.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&page=$next\">${lang["NEXT"]}&nbsp;></a>";   
   echo "<p><a href=\"log.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&all=1\">${lang["SHOWALL"]}</a>";
   echo "</center>";
}

include("templates/footer.php");

?>