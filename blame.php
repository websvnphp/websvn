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
$history = $svnrep->getLog($path, '', '', false, 2, $peg);
if (is_string($history)) {
  echo $history;
  exit;
}
$youngest = $history->entries[0]->rev;

if (empty($rev)) {
  $rev = $youngest;
} else {
  $history = $svnrep->getLog($path, $rev, '', false, 2, $peg);
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

$vars['log'] = $history->entries[0]->msg;
$vars['date'] = $history->entries[0]->date;
$vars['author'] = $history->entries[0]->author;

createDirLinks($rep, $ppath, $rev, $peg);
$passRevString = ($rev) ? 'rev='.$rev : '';
if ($peg)
  $passRevString .= '&amp;peg='.$peg;

if ($rev != $youngest) {
  $url = $config->getURL($rep, $path, 'blame');
  $vars['goyoungestlink'] = '<a href="'.$url.'">'.$lang['GOYOUNGEST'].'</a>';
}

$vars['indexurl'] = $config->getURL($rep, '', 'index');
$vars['repurl'] = $config->getURL($rep, '', 'dir');

$url = $config->getURL($rep, $path, 'file').$passRevString;
$vars['filedetaillink'] = '<a href="'.$url.'">'.$lang['FILEDETAIL'].'</a>';

$url = $config->getURL($rep, $path, 'log').$passRevString;
$vars['loglink'] = '<a href="'.$url.'">'.$lang['VIEWLOG'].'</a>';

if (sizeof($history->entries) > 1) {
  $url = $config->getURL($rep, $path, 'diff').$passRevString;
  $vars['difflink'] = '<a href="'.$url.'">'.$lang['DIFFPREV'].'</a>';
}

if ($rep->getHideRss()) {
  $url = $config->getURL($rep, $path, 'rss');
  $vars['rssurl'] = $url;
  $vars['rsslink'] = '<a href="'.$url.'">'.$lang['RSSFEED'].'</a>';
}

$listing = array();

// Check for binary file type before grabbing blame information.
$svnMimeType = $svnrep->getProperty($path, 'svn:mime-type', $rev, $peg);

if (!$rep->getIgnoreSvnMimeTypes() && preg_match('~application/*~', $svnMimeType)) {
  $vars['warning'] = 'Cannot display blame info for binary file. (svn:mime-type = '.$svnMimeType.')';
  $vars['javascript'] = '';
}
else {
  // Get the contents of the file
  $tfname = tempnam('temp', '');
  $highlighted = $svnrep->getFileContents($path, $tfname, $rev, '', true);
  
  if ($file = fopen($tfname, 'r')) {
    // Get the blame info
    $tbname = tempnam('temp', '');

    $svnrep->getBlameDetails($path, $tbname, $rev, $peg);
    
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
            $listing[$index]['revision'] = '<a id="l'.$index.'-rev" class="blame-revision" href="'.$url.'rev='.$revision.'&amp;peg='.$rev.'">'.$revision.'</a>';
            $seen_rev[$revision] = 1;
            $row_class = ($row_class == 'light') ? 'dark' : 'light';
            $listing[$index]['author'] = $author;
          } else {
            $listing[$index]['revision'] = '';
            $listing[$index]['author'] = '';
          }
  
          $listing[$index]['row_class'] = $row_class;
          $last_rev = $revision;
  
          $line = rtrim(fgets($file));
          if (!$highlighted) $line = replaceEntities($line, $rep);
  
          if ($empty)
            $line = '&nbsp;';
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
  
  if (!$rep->hasReadAccess($path, false)) {
    $vars['noaccess'] = true;
  }
  
  // Build the necessary JavaScript as an array of lines, then join them with \n
  $javascript = array();
  $javascript[] = '<script type="text/javascript" src="'.$locwebsvnhttp.'/javascript/blame-popup.js"></script>';
  $javascript[] = '<script type="text/javascript">';
  $javascript[] = '/* <![CDATA[ */';
  $javascript[] = 'var rev = new Array();';

  ksort($seen_rev); // Sort revisions in descending order by key
  if (empty($peg))
    $peg = $rev;
  foreach ($seen_rev as $key => $val) {
    $history = $svnrep->getLog($path, $key, $key, false, 1, $peg);
    if (!is_string($history)) {
      $javascript[] = 'rev['.$key.'] = \'<div class="date">'.$history->curEntry->date.'</div><div class="msg">'.addslashes(preg_replace('/\n/', ' ', $history->curEntry->msg)).'</div>\';';
    }
  }
  $javascript[] = '/* ]]> */';
  $javascript[] = '</script>';
  $vars['javascript'] = implode("\n", $javascript);
}

$vars['template'] = 'blame';
parseTemplate($rep->getTemplatePath().'header.tmpl', $vars, $listing);
parseTemplate($rep->getTemplatePath().'blame.tmpl', $vars, $listing);
parseTemplate($rep->getTemplatePath().'footer.tmpl', $vars, $listing);
