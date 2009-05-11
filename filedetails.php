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
// filedetails.php
//
// Simply lists the contents of a file

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");
require_once("include/template.php");

// Make sure that we have a repository
if (!isset($rep)) {
  echo $lang["NOREP"];
  exit;
}

$svnrep = new SVNRepository($rep);

if ($path{0} != "/") {
  $ppath = "/".$path;
} else {
  $ppath = $path;
}

$passrev = $rev;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, "", "", true);
if (is_string($history)) {
  echo $history;
  exit;
}
$youngest = isset($history->entries[0]) ? $history->entries[0]->rev: false;

if (empty($rev)) {
  $rev = $youngest;
}

$extn = strtolower(strrchr($path, "."));

// Check to see if the user has requested that this type be zipped and sent
// to the browser as an attachment

if (in_array($extn, $zipped) && $rep->hasReadAccess($path, false)) {
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
// file, or from the $contentType array in setup.php.

if (!$rep->getIgnoreSvnMimeTypes()) {
  $svnMimeType = $svnrep->getProperty($path, 'svn:mime-type', $rev);
}

if (!$rep->getIgnoreWebSVNContentTypes()) {
  $setupContentType = @$contentType[$extn];
}

// Use the documented priorities when establishing what content-type to use.
if (!empty($svnMimeType) && $svnMimeType != 'application/octet-stream') {
  $mimeType = $svnMimeType;
} else if (!empty($setupContentType)) {
  $mimeType = $setupContentType;
} else if (!empty($svnMimeType)) {
  $mimeType = $svnMimeType; // Use SVN's default of "application/octet-stream"
}

// If the MIME type exists but is set to be ignored, set it to an empty string.
if (!empty($mimeType)) {
  foreach ($config->inlineMimeTypes as $inlineType) {
    if (preg_match('|'.$inlineType.'|', $mimeType)) {
      $mimeType = '';
      break;
    }
  }
}

// If a MIME type is associated with the file, deliver with Content-Type header.
if (!empty($mimeType) && $rep->hasReadAccess($path, false)) {
  $base = basename($path);
  header('Content-Type: '.$mimeType);
  //header('Content-Length: '.$size);
  header('Content-Disposition: inline; filename='.urlencode($base));
  $svnrep->getFileContents($path, "", $rev);
  exit;
}

// Display the file inline using WebSVN.

$url = $config->getURL($rep, $path, "file");

if ($rev != $youngest) {
  $vars["goyoungestlink"] = "<a href=\"${url}\">${lang["GOYOUNGEST"]}</a>";
} else {
  $vars["goyoungestlink"] = "";
}

$vars["action"] = "";
$vars["repname"] = htmlentities($rep->getDisplayName(), ENT_QUOTES, 'UTF-8');
$vars["rev"] = htmlentities($rev, ENT_QUOTES, 'UTF-8');
$vars["path"] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');

createDirLinks($rep, $ppath, $passrev);

$vars['indexurl'] = $config->getURL($rep, '', 'index');
$vars['repurl'] = $config->getURL($rep, '', 'dir');

$url = $config->getURL($rep, $path, "log");
$vars["fileviewloglink"] = "<a href=\"${url}rev=$passrev&amp;isdir=0\">${lang["VIEWLOG"]}</a>";

$url = $config->getURL($rep, $path, "diff");
$vars["prevdifflink"] = "<a href=\"${url}rev=$passrev\">${lang["DIFFPREV"]}</a>";

$url = $config->getURL($rep, $path, "blame");
$vars["blamelink"] = "<a href=\"${url}rev=$passrev\">${lang["BLAME"]}</a>";

if ($rep->isDownloadAllowed($path)) {
  $url = $config->getURL($rep, $path, "dl");
  $vars["dllink"] = "<a href=\"${url}rev=$passrev\">${lang["DOWNLOAD"]}</a>";
}

$listing = array();

$vars["version"] = $version;

if (!$rep->hasReadAccess($path, false)) {
  $vars["noaccess"] = true;
}

parseTemplate($rep->getTemplatePath()."header.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."file.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."footer.tmpl", $vars, $listing);
