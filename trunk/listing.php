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

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");

function fileLink($path, $file)
{
   global $rep, $passrev, $showchanged, $config;
   
   if ($path == "" || $path{0} != "/")
      $ppath = "/".$path;
   else
      $ppath = $path;

   $isDir = $file{strlen($file) - 1} == "/";
      
   if ($isDir)
   {
      $url = $config->getURL($rep, $ppath.$file, "dir");
      return "<a href=\"${url}rev=$passrev&amp;sc=$showchanged\">$file</a>";
   }
   else
   {
      $url = $config->getURL($rep, $ppath.$file, "file");
      return "<a href=\"${url}rev=$passrev&amp;sc=$showchanged\">$file</a>";
   }
}

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);

// Revision info to pass along chain
$passrev = $rev;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);

if (!empty($history[0]))
   $youngest = $history[0]["rev"];
else
   $youngest = -1;

if (empty($rev))
   $rev = $youngest;

$contents = $svnrep->dirContents($path, $rev);
$log = $svnrep->getLogDetails($path, $rev);

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$vars["repname"] = $repname;

$dirurl = $config->getURL($rep, $path, "dir");
$logurl = $config->getURL($rep, $path, "log");
$rssurl = $config->getURL($rep, $path, "rss");

if ($rev != $youngest && $youngest != -1)
   $vars["goyoungestlink"] = "<a href=\"${dirurl}opt=dir&amp;sc=1\">${lang["GOYOUNGEST"]}</a>";
else
   $vars["goyoungestlink"] = "";

$vars["action"] = "";
$vars["author"] = $log['author'];
$vars["date"] = $log['date'];
$vars["log"] = nl2br(create_anchors($log['message']));
$vars["rev"] = $log["rev"];
$vars["path"] = $ppath;

if (!$showchanged)
{
   $vars["showchangeslink"] = "<a href=\"${dirurl}rev=$passrev&amp;sc=1\">${lang["SHOWCHANGED"]}</a>";
   $vars["hidechangeslink"] = "";

   $vars["hidechanges"] = true;
   $vars["showchanges"] = false;
}
else
{
   $vars["showchangeslink"] = "";
   
   $changes = $svnrep->getChangedFiles($rev);

   $first = true;
   $vars["newfilesbr"] = "";
   $vars["newfiles"] = "";
   foreach ($changes["added"] as $file)
   {
      if (!$first) $vars["newfilesbr"] .= "<br>";
      $first = false;
      $vars["newfilesbr"] .= fileLink("", $file);
      $vars["newfiles"] .= " ".fileLink("", $file);
   }
      
   $first = true;
   $vars["changedfilesbr"] = "";
   $vars["changedfiles"] = "";
   foreach ($changes["updated"] as $file)
   {
      if (!$first) $vars["changedfilesbr"] .= "<br>";
      $first = false;
      $vars["changedfilesbr"] .= fileLink("", $file);
      $vars["changedfiles"] .= " ".fileLink("", $file);
   }

   $first = true;
   $vars["deletedfilesbr"] = "";
   $vars["deletedfiles"] = "";
   foreach ($changes["deleted"] as $file)
   {
      if (!$first) $vars["deletedfilesbr"] .= "<br>";
      $first = false;
      $vars["deletedfilesbr"] .= $file;
      $vars["deletedfiles"] .= " ".$file;
   }

   $vars["hidechangeslink"] = "<a href=\"${dirurl}rev=$passrev&amp;sc=0\">${lang["HIDECHANGED"]}</a>";
   
   $vars["hidechanges"] = false;
   $vars["showchanges"] = true;
}

createDirLinks($rep, $ppath, $passrev, $showchanged);
$vars["curdirloglink"] = "<a href=\"${logurl}rev=$passrev&amp;sc=$showchanged&amp;isdir=1\">${lang["VIEWLOG"]}</a>";
if ($config->rss)
{
   $vars["curdirrsslink"] = "<a href=\"${rssurl}rev=$passrev&amp;sc=$showchanged&amp;isdir=1\">${lang["RSSFEED"]}</a>";
   $vars["curdirrssanchor"] = "<a href=\"${rssurl}rev=$passrev&amp;sc=$showchanged&amp;isdir=1\">";
}

$index = 0;
$listing = array();

// List each file in the current directory
$row = 0;
foreach($contents as $file)
{
   $listing[$index]["rowparity"] = "$row";
   $listing[$index]["filelink"] = fileLink($path, $file);

   // The history command doesn't return with a trailing slash.  We need to remember here if the
   // file is a directory or not! 
   
   $isDir = ($file{strlen($file) - 1} == "/"?1:0);
   $listing[$index]["isDir"] = $isDir;
   
   if ($isDir)
   {
      $listing[$index]["filetype"] = "dir";
   }
   else
   {
      $listing[$index]["filetype"] = strrchr($file, ".");
   }   
         
   $fileurl = $config->getURL($rep, $path.$file, "log");
   $listing[$index]["fileviewloglink"] = "<a href=\"${fileurl}rev=$passrev&amp;sc=$showchanged&amp;isdir=$isDir\">${lang["VIEWLOG"]}</a>";
   
   $rssurl = $config->getURL($rep, $path.$file, "rss");
   if ($config->rss)
   {
      $listing[$index]["rsslink"] = "<a href=\"${rssurl}rev=$passrev&amp;sc=$showchanged&amp;isdir=$isDir\">${lang["RSSFEED"]}</a>";
      $listing[$index]["rssanchor"] = "<a href=\"${rssurl}rev=$passrev&amp;sc=$showchanged&amp;isdir=$isDir\">";
   }
   
   $row = 1 - $row;
   $index++;
}

$vars["version"] = $version;

parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."directory.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);

?>