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
// listing.php
//
// Show the listing for the given repository/path/revision

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");
require_once("include/template.php");
require_once("include/bugtraq.php");

function removeURLSeparator($url) {
  return preg_replace('#(\?|&(amp;)?)$#', '', $url);
}

function fileLink($path, $file, $returnjoin = false) {
  global $rep, $passrev, $config;

  if ($path == "" || $path{0} != "/") {
    $ppath = "/".$path;
  } else {
    $ppath = $path;
  }

  if ($ppath{strlen($ppath)-1} != "/") {
    $ppath .= "/";
  }

  if ($file{0} == "/") {
    $pfile = substr($file, 1);
  } else {
    $pfile = $file;
  }

  if ($returnjoin) {
    return $ppath.$pfile;
  }

  $isDir = $pfile{strlen($pfile) - 1} == "/";

  if ($passrev) $passrevstr = "rev=$passrev&amp;"; else $passrevstr = "";

  if ($isDir) {
    $url = $config->getURL($rep, $ppath.$pfile, "dir");

    // XHTML doesn't allow slashes in IDs and must begin with a letter
    $id = str_replace('/', '_', 'path'.$ppath.$pfile);
    $url = "<a id='$id' href=\"${url}$passrevstr";

    $url = removeURLSeparator($url);
    if ($config->treeView) $url .= "#$id";
    $url .= "\">$pfile</a>";

  } else {
    $url = $config->getURL($rep, $ppath.$pfile, "file");
    $url .= $passrevstr;
    $url = removeURLSeparator($url);
    $url = "<a href=\"${url}\">$pfile</a>";
  }

  return $url;
}

function showDirFiles($svnrep, $subs, $level, $limit, $rev, $listing, $index, $treeview = true) {
  global $rep, $passrev, $config, $lang;

  $path = "";

  if (!$treeview) {
    $level = $limit;
  }

  for ($n = 0; $n <= $level; $n++) {
    $path .= $subs[$n]."/";
  }

  $logList = $svnrep->getList($path, $rev);

  // List each file in the current directory
  $loop = 0;
  $last_index = 0;
  $openDir = false;
  $fullaccess = $rep->hasReadAccess($path, false);

  foreach ($logList->entries as $entry) {
    $file = $entry->file;
    $isDir = $entry->isdir;
    $access = false;

    if ($isDir) {
      if ($rep->hasReadAccess($path.$file, true)) {
        $access = true;
        $openDir = isset($subs[$level+1]) && (!strcmp($subs[$level+1]."/", $file) || !strcmp($subs[$level+1], $file));

        if ($openDir) {
          $listing[$index]["filetype"] = "diropen";
        } else {
          $listing[$index]["filetype"] = "dir";
        }

        if ($rep->isDownloadAllowed($path.$file)) {
          $dlurl = $config->getURL($rep, $path.$file, "dl");
          $listing[$index]["fileviewdllink"] = "<a href=\"${dlurl}rev=$passrev&amp;isdir=1\">${lang["TARBALL"]}</a>";
          $listing[$index]['downloadurl'] = $dlurl.'rev='.$passrev.'&amp;isdir=1';
        } else {
          $listing[$index]['downloadurl'] = '';
        }
        $listing[$index]['downloadplainurl'] = '';
      }

    } else {
      if ($fullaccess) {
        $access = true;
        if ($level != $limit) {
          // List directories only, skip all files
          continue;
        }

        $listing[$index]['downloadurl'] = '';
        if ($rep->isDownloadAllowed($path.$file)) {
          $dlurl = $config->getURL($rep, $path.$file, "dl");
          $listing[$index]['downloadplainurl'] = $dlurl.'rev='.$passrev;
        } else {
          $listing[$index]['downloadplainurl'] = '';
        }
        $listing[$index]["filetype"] = strtolower(strrchr($file, "."));
      }
    }

    if ($access) {
      $listing[$index]["rowparity"] = ($index % 2)?"1":"0";

      if ($treeview) {
        $listing[$index]["compare_box"] = "<input type=\"checkbox\" name=\"compare[]\" value=\"".fileLink($path, $file, true)."@$passrev\" onclick=\"checkCB(this)\" />";
      } else {
        $listing[$index]["compare_box"] = "";
      }

      if ($openDir) {
        $listing[$index]["filelink"] = "<b>".fileLink($path, $file)."</b>";
      } else {
        $listing[$index]["filelink"] = fileLink($path, $file);
      }

      // The history command doesn't return with a trailing slash.  We need to remember here if the
      // file is a directory or not!

      $listing[$index]["isDir"] = $isDir;

      if ($treeview) {
        $listing[$index]["level"] = $level;
      } else {
        $listing[$index]["level"] = 0;
      }

      $listing[$index]["node"] = 0; // t-node

      $fileurl = $config->getURL($rep, $path.$file, "log");
      $listing[$index]["fileviewloglink"] = "<a href=\"${fileurl}rev=$passrev&amp;isdir=$isDir\">${lang["VIEWLOG"]}</a>";
      $listing[$index]['logurl'] = $fileurl.'rev='.$passrev.'&amp;isdir='.$isDir;

      if ($rep->getHideRss()) {
        $rssurl = $config->getURL($rep, $path.$file, 'rss');
        $listing[$index]['rssurl'] = $rssurl.'rev='.$passrev.'&amp;isdir='.$isDir;
      }

      if ($config->showLastMod) {
        $listing[$index]['revision'] = $entry->rev;
        $listing[$index]['author'] = $entry->author;
        $listing[$index]['date'] = $entry->date;
        $listing[$index]['committime'] = $entry->committime;
        $listing[$index]['age'] = $entry->age;
        $listing[$index]['revurl'] = $config->getURL($rep, $path.$file, 'revision').'rev='.$entry->rev.'&amp;';
      }

      $index++;
      $loop++;
      $last_index = $index;

      if (($level != $limit) && ($isDir)) {
        if (isset($subs[$level + 1]) && !strcmp(htmlentities($subs[$level + 1],ENT_QUOTES).'/', htmlentities($file))) {
          $listing = showDirFiles($svnrep, $subs, $level + 1, $limit, $rev, $listing, $index);
          $index = count($listing);
        }
      }
    }
  }

  if ($last_index != 0 && $treeview) {
    $listing[$last_index - 1]["node"] = 1; // l-node
  }

  return $listing;
}

function showTreeDir($svnrep, $path, $rev, $listing) {
  global $vars, $config;

  $subs = explode("/", $path);

  // For directory, the last element in the subs is empty.
  // For file, the last element in the subs is the file name.
  // Therefore, it is always count($subs) - 2
  $limit = count($subs) - 2;

  for ($n = 0; $n < $limit; $n++) {
    $vars["last_i_node"][$n] = FALSE;
  }

  return showDirFiles($svnrep, $subs, 0, $limit, $rev, $listing, 0, $config->treeView);
}

// Make sure that we have a repository
if (!isset($rep)) {
  echo $lang["NOREP"];
  exit;
}

$svnrep = new SVNRepository($rep);

// Revision info to pass along chain
$passrev = $rev;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, $rev, "", false);
if (is_string($history)) {
  echo $history;
  exit;
}

if (!empty($history->entries[0])) {
  $youngest = $history->entries[0]->rev;
} else {
  $youngest = -1;
}

// Unless otherwise specified, we get the log details of the latest change
if (empty($rev)) {
  $logrev = $youngest;
} else {
  $logrev = $rev;
}

if ($logrev != $youngest) {
  $logEntry = $svnrep->getLog($path, $logrev, $logrev, false);
  if (is_string($logEntry)) {
    echo $logEntry;
    exit;
  }
  $logEntry = isset($logEntry->entries[0]) ? $logEntry->entries[0] : false;
} else {
  $logEntry = isset($history->entries[0]) ? $history->entries[0] : false;
}

$headlog = $svnrep->getLog("/", "", "", true, 1);
if (is_string($headlog)) {
  echo $headlog;
  exit;
}
$headrev = isset($headlog->entries[0]) ? $headlog->entries[0]->rev : 0;

// If we're not looking at a specific revision, get the HEAD revision number
// (the revision of the rest of the tree display)

if (empty($rev)) {
  $rev = $headrev;
}

if ($path == "" || $path{0} != "/") {
  $ppath = "/".$path;
} else {
  $ppath = $path;
}

$vars["repname"] = $rep->getDisplayName();

$compurl = $config->getURL($rep, "/", "comp");
$revisionurl = $config->getURL($rep, $path, 'revision');

if ($passrev != 0 && $passrev != $headrev && $youngest != -1) {
  $vars['goyoungesturl'] = $config->getURL($rep, $path, 'dir').'opt=dir';
} else {
  $vars['goyoungesturl'] = '';
}

$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

$vars["action"] = "";
$vars["rev"] = $rev;
$vars["path"] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');
$vars["lastchangedrev"] = $logrev;
$vars["date"] = $logEntry ? $logEntry->date : '';
$vars["author"] = $logEntry ? $logEntry->author : '';
$vars["log"] = $logEntry ? nl2br($bugtraq->replaceIDs(create_anchors($logEntry->msg))) : '';
$vars["changesurl"] = $revisionurl.'rev='.$passrev;

createDirLinks($rep, $ppath, $passrev);

$logurl = $config->getURL($rep, $path, "log");
$vars['logurl'] = $logurl.'rev='.$passrev.'&amp;isdir=1';

$vars['indexurl'] = $config->getURL($rep, '', 'index');
$vars['repurl'] = $config->getURL($rep, '', 'dir');

if ($rep->getHideRss()) {
  $rssurl = $config->getURL($rep, $path, "rss");
  // $vars["curdirrsslink"] = "<a href=\"${rssurl}isdir=1\">${lang["RSSFEED"]}</a>";
  $vars['rssurl'] = $rssurl.'isdir=1';
  // $vars["curdirrssanchor"] = "<a href=\"${rssurl}isdir=1\">";
}

// Set up the tarball link

$subs = explode("/", $path);
$level = count($subs) - 2;
if ($rep->isDownloadAllowed($path)) {
  $dlurl = $config->getURL($rep, $path, "dl");
  $vars["curdirdllink"] = "<a href=\"${dlurl}rev=$passrev&amp;isdir=1\">${lang["TARBALL"]}</a>";
  $vars['downloadurl'] = $dlurl.'rev='.$passrev.'&amp;isdir=1';
} else {
  $vars["curdirdllink"] = '';
  $vars['downloadurl'] = '';
}

$url = $config->getURL($rep, "/", "comp");

$vars["compare_form"] = "<form action=\"$url\" method=\"post\">";
$vars["compare_submit"] = "<input name=\"comparesubmit\" type=\"submit\" value=\"${lang["COMPAREPATHS"]}\" />";
$vars["compare_hidden"] = "<input type=\"hidden\" name=\"op\" value=\"comp\" />";
$vars["compare_endform"] = "</form>";

$vars['showlastmod'] = $config->showLastMod;
$vars['showageinsteadofdate'] = $config->showAgeInsteadOfDate;

$listing = array();
$listing = showTreeDir($svnrep, $path, $rev, $listing);

$vars["version"] = $version;

if (!$rep->hasReadAccess($path, true)) {
  $vars["noaccess"] = true;
}
if (!$rep->hasReadAccess($path, false)) {
  $vars["restricted"] = true;
}

parseTemplate($rep->getTemplatePath()."header.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."directory.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."footer.tmpl", $vars, $listing);
