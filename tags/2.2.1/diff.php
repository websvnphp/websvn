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
// diff.php
//
// Show the differences between 2 revisions of a file.
//

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");
require_once("include/template.php");

require_once("include/diff_inc.php");

$vars["action"] = $lang["DIFF"];
$all = (@$_REQUEST["all"] == 1);
$ignoreWhitespace = (@$_REQUEST["ignorews"] == 1);

// Make sure that we have a repository
if (!isset($rep)) {
  echo $lang["NOREP"];
  exit;
}

$svnrep = new SVNRepository($rep);

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, "", "", true);
if (is_string($history)) {
  echo $history;
  exit;
}
$youngest = $history->entries[0]->rev;

if (empty($rev)) {
  $rev = $youngest;
}

$history = $svnrep->getLog($path, $rev);
if (is_string($history)) {
  echo $history;
  exit;
}

if ($path{0} != "/") {
  $ppath = "/".$path;
} else {
  $ppath = $path;
}

$prevrev = @$history->entries[1]->rev;

$vars["repname"] = htmlentities($rep->getDisplayName(), ENT_QUOTES, 'UTF-8');
$vars["rev"] = $rev;
$vars["path"] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');
$vars["prevrev"] = $prevrev;

$vars["rev1"] = $history->entries[0]->rev;
$vars["rev2"] = $prevrev;

createDirLinks($rep, $ppath, $rev);

$listing = array();

$url = $config->getURL($rep, $path, "file");
if ($rev != $youngest) {
  $vars["goyoungestlink"] = '<a href="'.$url.'">'.$lang['GOYOUNGEST'].'</a>';
} else {
  $vars["goyoungestlink"] = "";
}

$vars['indexurl'] = $config->getURL($rep, '', 'index');
$vars['repurl'] = $config->getURL($rep, '', 'dir');

$url = $config->getURL($rep, $path, "file");
$vars["filedetaillink"] = "<a href=\"${url}rev=$rev&amp;isdir=0\">${lang["FILEDETAIL"]}</a>";

$url = $config->getURL($rep, $path, "log");
$vars["fileviewloglink"] = "<a href=\"${url}rev=$rev&amp;isdir=0\">${lang["VIEWLOG"]}</a>";

$url = $config->getURL($rep, $path, "diff");
$vars["prevdifflink"] = "<a href=\"${url}rev=$rev\">${lang["DIFFPREV"]}</a>";

$url = $config->getURL($rep, $path, "blame");
$vars["blamelink"] = "<a href=\"${url}rev=$rev\">${lang["BLAME"]}</a>";

if ($prevrev) {
  $url = $config->getURL($rep, $path, "diff");

  if (!$all) {
    $vars["showalllink"] = '<a href="'.$url.'rev='.$rev.'&amp;all=1&amp;ignorews='.($ignoreWhitespace ? '1' : '0').'">'.$lang['SHOWENTIREFILE'].'</a>';
    $vars["showcompactlink"] = '';
  } else {
    $vars["showcompactlink"] = '<a href="'.$url.'rev='.$rev.'&amp;all=0&amp;ignorews='.($ignoreWhitespace ? '1' : '0').'">'.$lang['SHOWCOMPACT'].'</a>';
    $vars["showalllink"] = '';
  }
  if (!$ignoreWhitespace) {
    $vars['ignorewhitespacelink'] = '<a href="'.$url.'rev='.$rev.'&amp;all='.($all ? '1' : '0').'&amp;ignorews=1">'.$lang['IGNOREWHITESPACE'].'</a>';
    $vars['regardwhitespacelink'] = '';
  } else {
    $vars['regardwhitespacelink'] = '<a href="'.$url.'rev='.$rev.'&amp;all='.($all ? '1' : '0').'&amp;ignorews=0">'.$lang['REGARDWHITESPACE'].'</a>';
    $vars['ignorewhitespacelink'] = '';
  }

  // Get the contents of the two files
  $newtname = tempnam("temp", "");
  $highlightedNew = $svnrep->getFileContents($history->entries[0]->path, $newtname, $history->entries[0]->rev, "", true);

  $oldtname = tempnam("temp", "");
  $highlightedOld = $svnrep->getFileContents($history->entries[1]->path, $oldtname, $history->entries[1]->rev, "", true);

  $ent = (!$highlightedNew && !$highlightedOld);
  $listing = do_diff($all, $ignoreWhitespace, $rep, $ent, $newtname, $oldtname);

  // Remove our temporary files
  @unlink($oldtname);
  @unlink($newtname);

} else {
  $vars["noprev"] = 1;
  $url = $config->getURL($rep, $path, "file");
  $vars["filedetaillink"] = "<a href=\"${url}rev=$rev\">${lang["SHOWENTIREFILE"]}.</a>";
}

$vars["version"] = $version;

if (!$rep->hasReadAccess($path, false)) {
  $vars["noaccess"] = true;
}

parseTemplate($rep->getTemplatePath()."header.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."diff.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."footer.tmpl", $vars, $listing);
