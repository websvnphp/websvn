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
require("include/template.inc");

$rep = @$_REQUEST["rep"];
$path = @$_REQUEST["path"];
$rev = @$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;


function fileLink($path, $file)
{
   global $rep, $passrev, $showchanged, $config;
   
   $isDir = $file{strlen($file) - 1} == "/";
      
   if ($isDir)
   {
      $url = $config->getURL($rep, $path.$file, "dir");
      return "<a href=\"${url}rev=$passrev&sc=$showchanged\">$file</a>";
   }
   else
   {
      $url = $config->getURL($rep, $path.$file, "file");
      return "<a href=\"${url}rev=$passrev&sc=$showchanged\">$file</a>";
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
$youngest = $history[0]["rev"];

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

if ($rev != $youngest)
   $vars["goyoungestlink"] = "<a href=\"${dirurl}opt=dir&sc=1\">${lang["GOYOUNGEST"]}</a>";
else
   $vars["goyoungestlink"] = "";

$vars["author"] = $log['author'];
$vars["date"] = $log['date'];
$vars["log"] = nl2br($log['message']);
$vars["rev"] = $log["rev"];
$vars["path"] = $ppath;

if (!$showchanged)
{
   $vars["showchangeslink"] = "<a href=\"${dirurl}rev=$passrev&sc=1\">${lang["SHOWCHANGED"]}</a>";
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

   $vars["hidechangeslink"] = "<a href=\"${dirurl}rev=$passrev&sc=0\">${lang["HIDECHANGED"]}</a>";
   
   $vars["hidechanges"] = false;
   $vars["showchanges"] = true;
}

$subs = explode("/", $ppath);
$sofar = "";
$count = count($subs);
$vars["curdirlinks"] = "";

for ($n = 0; $n < $count - 2; $n++)
{
   $sofar .= $subs[$n]."/";
   $sofarurl = $config->getURL($rep, $sofar, "dir");
   $vars["curdirlinks"] .= "[<a href=\"${sofarurl}rev=$passrev&sc=$showchanged\">".$subs[$n]."/]</a> ";
}
$vars["curdirlinks"] .=  "[".$subs[$n]."/]";
$vars["curdirloglink"] = "<a href=\"${logurl}rev=$passrev&sc=$showchanged&isdir=1\">${lang["VIEWLOG"]}</a>";

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
   $fileurl = $config->getURL($rep, $path.$file, "log");
   $listing[$index]["fileviewloglink"] = "<a href=\"${fileurl}rev=$passrev&sc=$showchanged&isdir=$isDir\">${lang["VIEWLOG"]}</a>";
   
   $row = 1 - $row;
   $index++;
}

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."directory.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);

?>