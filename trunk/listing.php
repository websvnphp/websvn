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
// listing.php
//
// Show the listing for the given repository/path/revision

require("include/config.inc");
require("include/svnlook.inc");

include("templates/header.php");

$rep = @$_REQUEST["rep"];
$path = @$_REQUEST["path"];
$rev = @$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;

function fileLink($file)
{
   global $rep, $path, $rev, $showchanged;
   
   $isDir = $file{strlen($file) - 1} == "/";

   if ($isDir)
      return "<a href=\"listing.php?rep=$rep&path=$file&rev=$rev&sc=$showchanged\">$file</a>";
   else
      return "<a href=\"filedetails.php?rep=$rep&path=$file&rev=$rev&sc=$showchanged\">$file</a>";
}

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);
$contents = $svnrep->dirContents($path, $rev);
$log = $svnrep->getLogDetails($path, $rev);
$youngest = $svnrep->getLogDetails($path);
$youngest = $youngest["rev"];

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

echo "<h1>$repname - ${lang["REV"]} ${log["rev"]}</h1>";
if ($log["rev"] < $youngest)
{
   echo "<a href=\"listing.php?rep=$rep&path=$path&sc=1\">${lang["GOHEAD"]}</a>";
}
echo "<p><b>${lang["LASTMOD"]}:</b> ${log['author']} - ${log['date']}";
echo "<p><b>${lang["LOGMSG"]}:</b><br>".nl2br($log['message']);
echo "<p>";

if (!$showchanged)
{
   echo "<a href=\"listing.php?rep=$rep&path=$path&rev=$rev&sc=1\">${lang["SHOWCHANGED"]}</a>";
}
else
{
   echo "<p><b>${lang["CHANGES"]}:</b><br>";
   
   $changes = $svnrep->getChangedFiles($rev);
   
   echo "<center><table border=1 class=\"bordered\" cellpadding=4><tr><td><center><b>${lang["NEWFILES"]}</b></center></td><td><center><b>${lang["CHANGEDFILES"]}</b></center></td><td><center><b>${lang["DELETEDFILES"]}</b></center></td></tr><tr>";
   
   echo "<td valign=\"top\">";
   $first = true;
   foreach ($changes["added"] as $file)
   {
      if (!$first) echo "<br>";
      $first = false;
      echo fileLink($file);
   }
   echo "</td>";
      
   echo "<td valign=\"top\">";
   $first = true;
   foreach ($changes["updated"] as $file)
   {
      if (!$first) echo "<br>";
      $first = false;
      echo fileLink($file);
   }
   echo "</td>";

   echo "<td valign=\"top\">";
   $first = true;
   foreach ($changes["deleted"] as $file)
   {
      if (!$first) echo "<br>";
      $first = false;
      echo $file;
   }
   echo "</td>";

   echo "</tr></table>";
   echo "<p><a href=\"listing.php?rep=$rep&path=$path&rev=$rev&sc=0\">${lang["HIDECHANGED"]}</a></center>";
}

echo "<p><hr><h1>$ppath&nbsp;- <font size=\"smaller\"><a href=\"log.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&isdir=1\">${lang["VIEWLOG"]}</a></font></h1>";

echo "<table border=1 class=\"outlined\" width=\"100%\" cellpadding=2><tr><th><b>${lang["PATH"]}</b></td><th align=\"center\"><b>${lang["LOG"]}</b></td></tr>";

// Give the user a chance to go back up the tree
if ($ppath != "/")
{
   // Find the parent path (or the whole path if it's already a directory)
   $pos = strrpos(substr($ppath, 0, -1), "/");
   $parent = substr($ppath, 0, $pos + 1);

   echo "<tr><td><a href=\"listing.php?rep=$rep&path=$parent&rev=$rev&sc=$showchanged\">..</a></td><td>&nbsp;</td></tr>";
}

// List each file in the current directory
foreach($contents as $file)
{
   echo "<tr>";
      
   echo "<td>".fileLink("$path$file")."</td>";
   
   // The history command doesn't return with a trailing slash.  We need to remember here if the
   // file is a directory or not! 
   
   $isDir = ($file{strlen($file) - 1} == "/"?1:0);
   echo "<td width=1><a href=\"log.php?rep=$rep&path=$path$file&rev=$rev&sc=$showchanged&isdir=$isDir\">${lang["VIEWLOG"]}</a></td>";
   echo "</td>";
}

echo "</table>";

include("templates/footer.php");

?>