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
// revision.php
//
// Show the details for a given revision

require_once('include/setup.php');
require_once('include/svnlook.php');
require_once('include/utils.php');
require_once('include/template.php');
require_once('include/bugtraq.php');

// Make sure that we have a repository
if ($rep) {
$svnrep = new SVNRepository($rep);

// Revision info to pass along chain
$passrev = $rev;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, '', '', false, 2, $peg);
$youngest = ($history) ? $history->entries[0]->rev : 0;

// Unless otherwise specified, we get the log details of the latest change
$logrev = (empty($rev)) ? $youngest : $rev;

if ($logrev != $youngest) {
  $history = $svnrep->getLog($path, $logrev, $logrev, false, 2, $peg);
}
$logEntry = ($history && isset($history->entries[0])) ? $history->entries[0] : null;

$headlog = $svnrep->getLog('/', '', '', true, 1, $peg);
$headrev = ($headlog && isset($headlog->entries[0])) ? $headlog->entries[0]->rev : 0;

// If we're not looking at a specific revision, get the HEAD revision number
// (the revision of the rest of the tree display)

if (empty($rev)) {
  $rev = $headrev;
}

if ($path == '' || $path{0} != '/') {
  $ppath = '/'.$path;
} else {
  $ppath = $path;
}

if ($passrev != 0 && $passrev != $headrev && $youngest != 0) {
  $vars['goyoungesturl'] = $config->getURL($rep, $path, 'revision');
  $vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'">'.$lang['GOYOUNGEST'].'</a>';
}

$vars['listingurl'] = $config->getURL($rep, $path, 'dir').'rev='.$passrev;
$vars['listinglink'] = '<a href="'.$vars['listingurl'].'">'.$lang['LISTING'].'</a>';

$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

$vars['action'] = '';
$vars['rev'] = $rev;
$vars['path'] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');
$vars['lastchangedrev'] = $logrev;
$vars['date'] = $logEntry ? $logEntry->date: '';
$vars['author'] = $logEntry ? $logEntry->author: '';
$vars['log'] = $logEntry ? nl2br($bugtraq->replaceIDs(create_anchors($logEntry->msg))): '';

$changes = $logEntry ? $logEntry->mods : array();
if (!is_array($changes)) {
  $changes = array();
}
usort($changes, 'SVNLogEntry_compare');

$row = 0;

$prevRevString = createRevAndPegString($passrev-1, $passrev-1);
$thisRevString = createRevAndPegString($passrev, ($peg ? $peg : $passrev));

foreach ($changes as $file) {
  $linkRevString = ($file->action == 'D') ? $prevRevString : $thisRevString;
  $listing[] = array(
    'file' => $file->path,
    'added'    => $file->action == 'A',
    'deleted'  => $file->action == 'D',
    'modified' => $file->action == 'M',
     // TODO: Figure out how to differentiate directories (detailurl / logurl)
    'detailurl' => $config->getURL($rep, $file->path, 'file').$linkRevString,
    // For deleted resources, make log link start at previous revision
    'logurl' => $config->getURL($rep, $file->path, 'log').$linkRevString,
    'diffurl' => ($file->action == 'M') ? $config->getURL($rep, $file->path, 'diff').$linkRevString : '',
    'blameurl' => ($file->action == 'M') ? $config->getURL($rep, $file->path, 'blame').$linkRevString : '',
    'rowparity' => $row,
  );

  $row = 1 - $row;
}

createDirLinks($rep, $ppath, $passrev, $peg);

$vars['logurl'] = $config->getURL($rep, $path, 'log').'rev='.$passrev.'&amp;isdir=1';
$vars['loglink'] = '<a href="'.$vars['logurl'].'">'.$lang['VIEWLOG'].'</a>';

if ($rev != $headrev) {
  $history = $svnrep->getLog($ppath, $rev, '', false, 2, $peg);
}

if ($history && isset($history->entries[1]->rev)) {
  $vars['compareurl'] = $config->getURL($rep, '/', 'comp').'compare[]='.urlencode($history->entries[1]->path).'@'.$history->entries[1]->rev. '&amp;compare[]='.urlencode($history->entries[0]->path).'@'.$history->entries[0]->rev;
  $vars['comparelink'] = '<a href="'.$vars['compareurl'].'">'.$lang['DIFFPREV'].'</a>';
}

if ($rep->getHideRss()) {
  $vars['rssurl'] = $config->getURL($rep, $path, 'rss').($peg ? 'peg='.$peg : '');
  $vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
}


if (!$rep->hasReadAccess($path, true)) {
  $vars['error'] = $lang['NOACCESS'];
}
$vars['restricted'] = !$rep->hasReadAccess($path, false);
}

$vars["template"] = "revision";
$template = ($rep) ? $rep->getTemplatePath() : $config->templatePath;
parseTemplate($template."header.tmpl", $vars, $listing);
parseTemplate($template."revision.tmpl", $vars, $listing);
parseTemplate($template."footer.tmpl", $vars, $listing);
