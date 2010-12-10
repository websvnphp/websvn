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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
//
// --
//
// index.php
//
// Main page which lists all configured repositories (optionally by groups).

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/template.php';

$vars['action'] = $lang['PROJECTS'];
$vars['repname'] = '';
$vars['rev'] = 0;
$vars['path'] = '';
$vars['showlastmod'] = $config->showLastModInIndex();

// Sort the repositories by group
$config->sortByGroup();
$projects = $config->getRepositories();

if (count($projects) == 1 && $projects[0]->hasReadAccess('/', true)) {
	header('Location: '.str_replace('&amp;', '', $config->getURL($projects[0], '', 'dir')));
	exit;
}

$i = 0;
$parity = 0; // Alternates between every entry, whether it is a group or project
$groupparity = 0; // The first project (and first of any group) resets this to 0
$curgroup = null;
$groupcount = 0;
// Create listing of all configured projects (includes groups if they are used).
foreach ($projects as $project) {
	if (!$project->hasReadAccess('/', true))
		continue;

	// If this is the first project in a group, add an entry for the group.
	if ($curgroup != $project->group) {
		$groupcount++;
		$groupparity = 0;
		$listing[$i]['notfirstgroup'] = !empty($curgroup);
		$curgroup = $project->group;
		$listing[$i]['groupname'] = $curgroup; // Applies until next group is set.
		$listing[$i]['groupid'] = strtr(base64_encode('grp'.$curgroup), array('+' => '-', '/' => '_', '=' => ''));

		$listing[$i]['projectlink'] = null; // Because template.php won't unset this
		$i++; // Causes the subsequent lines to store data in the next array slot.
		$listing[$i]['groupid'] = null; // Because template.php won't unset this
	}
	$listing[$i]['clientrooturl'] = $project->clientRootURL;

	// Populate variables for latest modification to the current repository
	if ($config->showLastModInIndex()) {
		$svnrep = new SVNRepository($project);
		$log = $svnrep->getLog('/', '', '', true, 1);
		if (isset($log->entries[0])) {
			$head = $log->entries[0];
			$listing[$i]['revision'] = $head->rev;
			$listing[$i]['date'] = $head->date;
			$listing[$i]['age'] = datetimeFormatDuration(time() - strtotime($head->date));
			$listing[$i]['author'] = $head->author;
		} else {
			$listing[$i]['revision'] = 0;
			$listing[$i]['date'] = '';
			$listing[$i]['age'] = '';
			$listing[$i]['author'] = '';
		}
	}

	// Create project (repository) listing
	$url = str_replace('&amp;', '', $config->getURL($project, '', 'dir'));
	$name = ($config->flatIndex) ? $project->getDisplayName() : $project->name;
	$listing[$i]['projectlink'] = '<a href="'.$url.'">'.escape($name).'</a>';
	$listing[$i]['rowparity'] = $parity % 2;
	$parity++;
	$listing[$i]['groupparity'] = $groupparity % 2;
	$groupparity++;
	$listing[$i]['groupname'] = ($curgroup != null) ? $curgroup : '';
	$i++;
}
if (empty($listing) && !empty($projects)) {
	$vars['error'] = $lang['NOACCESS'];
	checkSendingAuthHeader();
}

$vars['flatview'] = $config->flatIndex;
$vars['treeview'] = !$config->flatIndex;
$vars['opentree'] = $config->openTree;
$vars['groupcount'] = $groupcount; // Indicates whether any groups were present.

renderTemplate('index');
