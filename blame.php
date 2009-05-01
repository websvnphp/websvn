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
// blame.php
//
// Show the blame information of a file.
//

require_once'include/setup.php';
require_once'include/svnlook.php';
require_once'include/utils.php';
require_once'include/template.php';

$vars['action'] = $lang['BLAME'];

$svnrep = new SVNRepository($rep);

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, '', '', true);
if (is_string($history)) {
  echo $history;
  exit;
}
$youngest = $history->entries[0]->rev;

if (empty($rev)) {
  $rev = $youngest;
}

if ($path{0} != '/') {
  $ppath = '/'.$path;
} else {
  $ppath = $path;
}

// Find the parent path (or the whole path if it's already a directory)
$pos = strrpos($ppath, '/');
$parent = substr($ppath, 0, $pos + 1);

$vars['repname'] = htmlentities($rep->getDisplayName(), ENT_QUOTES, 'UTF-8');
$vars['rev'] = $rev;
$vars['path'] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');

createDirLinks($rep, $ppath, $rev);

if ($rev != $youngest) {
  $url = $config->getURL($rep, $path, 'blame');
  $vars["goyoungestlink"] = '<a href="'.$url.'">'.$lang["GOYOUNGEST"].'</a>';
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

$listing = array();

// Get the contents of the file
$tfname = tempnam('temp', '');
$highlighted = $svnrep->getFileContents($path, $tfname, $rev, '', true);

if ($file = fopen($tfname, 'r')) {
  // Get the blame info
  $tbname = tempnam('temp', '');
  $svnrep->getBlameDetails($path, $tbname, $rev);

  if ($blame = fopen($tbname, 'r')) {
    // Create an array of version/author/line

    $index = 0;
    $seen_rev = array();
    $last_rev = "";
    $row_class = '';

    while (!feof($blame) && !feof($file)) {
      $blameline = fgets($blame);

      if ($blameline != '') {
        list($revision, $author, $remainder) = sscanf($blameline, '%d %s %s');
        $empty = !$remainder;

        $listing[$index]['lineno'] = $index + 1;

        if ($last_rev != $revision) {
          $url = $config->getURL($rep, $parent, 'revision');
          $listing[$index]['revision'] = "<a id=\"l$index-rev\" class=\"blame-revision\" href=\"${url}rev=$revision\">$revision</a>";
          $seen_rev[$revision] = 1;
          $row_class = ($row_class == 'light') ? 'dark' : 'light';
          $listing[$index]['author'] = $author;
        } else {
          $listing[$index]['revision'] = "";
          $listing[$index]['author'] = '';
        }

        $listing[$index]['row_class'] = $row_class;
        $last_rev = $revision;

        $line = rtrim(fgets($file));
        if (!$highlighted) $line = replaceEntities($line, $rep);

        if ($empty) $line = '&nbsp;';
        $listing[$index]['line'] = hardspace($line);

        $index++;
      }
    }

    fclose($blame);
  }

  fclose($file);

  @unlink($tbname);
}

@unlink($tfname);

$vars['version'] = $version;

if (!$rep->hasReadAccess($path, false)) {
  $vars['noaccess'] = true;
}

$vars['javascript'] =  <<<HTML

<script type='text/javascript'>
/* <![CDATA[ */
var rev = new Array();
var a = document.getElementsByTagName('a');
for (var i = 0; i < a.length; i++) {
  if (a[i].className == 'blame-revision') {
    var id = a[i].id;
    addEvent(a[i], 'mouseover', function() { mouseover(this) } );
    addEvent(a[i], 'mouseout', function() { mouseout(this) } );
  }
}

function mouseover(a) {
  // Find the revision by using the link
  var m = /rev=(\d+)/.exec(a.href);
  var r = m[1];

  div = document.createElement('div');
  div.className = 'blame-popup';
  div.innerHTML = rev[r];
  a.parentNode.appendChild(div);
}

function mouseout(a) {
  var div = a.parentNode.parentNode.getElementsByTagName('div');
  for (var i = 0; i < div.length; i++) {
    if (div[i].className = 'blame-popup') {
      div[i].parentNode.removeChild(div[i]);
    }
  }
}

function addEvent(obj, type, func) {
  if (obj.addEventListener) {
    obj.addEventListener(type, func, false);
    return true;
  } else if (obj.attachEvent) {
    return obj.attachEvent('on' + type, func);
  } else {
    return false;
  }
}

HTML;

foreach ($seen_rev as $key => $val) {
  $history = $svnrep->getLog($path, $key, $key, false, 1);
  if (!is_string($history)) {
    $vars['javascript'] .= "rev[$key] = '";
    $vars['javascript'] .= "<div class=\"info\">";
    $vars['javascript'] .= "<span class=\"date\">".$history->curEntry->date."<\/span>";
    $vars['javascript'] .= "<\/div>";
    $vars['javascript'] .= "<div class=\"msg\">".addslashes(preg_replace('/\n/', "<br />", $history->curEntry->msg))."<\/div>";
    $vars['javascript'] .= "';\n";
  }
}
$vars['javascript'] .= "/* ]]> */\n</script>";

// ob_start('ob_gzhandler');

parseTemplate($rep->getTemplatePath().'header.tmpl', $vars, $listing);
parseTemplate($rep->getTemplatePath().'blame.tmpl', $vars, $listing);
parseTemplate($rep->getTemplatePath().'footer.tmpl', $vars, $listing);
