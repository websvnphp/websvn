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
// filedetails.php
//
// Simply lists the contents of a file

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

$svnrep = new SVNRepository($rep->path);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$passrev = $rev;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);
$youngest = $history[0]["rev"];

if (empty($rev))
   $rev = $youngest;

$extn = strrchr($path, ".");

// Check to see if the user has requested that this type be zipped and sent
// to the browser as an attachment

if (in_array($extn, $zipped))
{
   $base = basename($path);
   header("Content-Type: application/x-gzip");
   header("Content-Disposition: attachment; filename=".urlencode($base).".gz");

   // Get the file contents and pipe into gzip.  All this without creating
   // a temporary file.  Damn clever.
   $svnrep->getFileContents($path, "", $rev, "| ".$config->gzip." -n -f");
   
   exit;
}

// Check to see if we should serve it with a particular content-type.
// The content-type could come from an svn:mime-type property on the
// file, or from the $contentType array in setup.inc.

if (!$rep->getIgnoreSvnMimeTypes()) 
{
  $svnMimeType = $svnrep->getProperty($path, 'svn:mime-type', $rev);
}

if (!$rep->getIgnoreWebSVNContentTypes()) 
{
  $setupContentType = @$contentType[$extn];
}

// Use this set of priorities when establishing what content-type to
// actually use.

if (!empty($svnMimeType) && $svnMimeType != 'application/octet-stream') 
{
  $cont = $svnMimeType;
}
else if (!empty($setupContentType))
{
  $cont = $setupContentType;
} 
else if (!empty($svnMimeType))
{
  // It now is equal to application/octet-stream due to logic
  // above....
  $cont = $svnMimeType;
}

// If there's a MIME type associated with this format, then we deliver it
// with this information 

if (!empty($cont))
{
   $base = basename($path);
   
   header("Content-Type: $cont");
   //header("Content-Length: $size");
   header("Content-Disposition: inline; filename=".urlencode($base));
   
   $svnrep->getFileContents($path, "", $rev);
   
   exit;
}

// There's no associated MIME type.  Show the file using WebSVN.

$url = $config->getURL($rep, $path, "file");

if ($rev != $youngest)
   $vars["goyoungestlink"] = "<a href=\"${url}sc=1\">${lang["GOYOUNGEST"]}</a>";
else
   $vars["goyoungestlink"] = "";

$vars["action"] = "";
$vars["repname"] = $rep->name;
$vars["rev"] = $rev;
$vars["path"] = $ppath;

createDirLinks($rep, $ppath, $passrev, $showchanged);

$url = $config->getURL($rep, $path, "diff");
$vars["prevdifflink"] = "<a href=\"${url}rev=$passrev&amp;sc=$showchanged\">${lang["DIFFPREV"]}</a>";

$url = $config->getURL($rep, $path, "blame");
$vars["blamelink"] = "<a href=\"${url}rev=$passrev&amp;sc=$showchanged\">${lang["BLAME"]}</a>";

$listing = array ();

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."file.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);
?>