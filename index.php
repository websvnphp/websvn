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

if (count($projects) == 1 && $projects[0]->hasReadAccess('/')) {
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
	if (!$project->hasReadAccess('/'))
		continue;

	$listvar = &$listing[$i];
	// If this is the first project in a group, add an entry for the group.
	if ($curgroup != $project->group) {
		$groupcount++;
		$groupparity = 0;
		$listvar['notfirstgroup'] = !empty($curgroup);
		$curgroup = $project->group;
		$listvar['groupname'] = $curgroup; // Applies until next group is set.
		$listvar['groupid'] = strtr(base64_encode('grp'.$curgroup), array('+' => '-', '/' => '_', '=' => ''));

		// setting to null because template.php won't unset them
		$listvar['projectlink'] = null;
		$listvar['projectname'] = null;
		$listvar['projecturl'] = null;
		$i++; // Causes the subsequent lines to store data in the next array slot.
		$listvar = &$listing[$i];
		$listvar['groupid'] = null;
	}
	$listvar['clientrooturl'] = $project->clientRootURL;

	// Populate variables for latest modification to the current repository
	if ($config->showLastModInIndex()) {
		$svnrep = new SVNRepository($project);
		$log = $svnrep->getLog('/', '', '', true, 1);
		if (isset($log->entries[0])) {
			$head = $log->entries[0];
			$listvar['revision'] = $head->rev;
			$listvar['date'] = $head->date;
			$listvar['age'] = datetimeFormatDuration(time() - strtotime($head->date));
			$listvar['author'] = $head->author;
		} else {
			$listvar['revision'] = 0;
			$listvar['date'] = '';
			$listvar['age'] = '';
			$listvar['author'] = '';
		}
	}

	// Create project (repository) listing
	$url = str_replace('&amp;', '', $config->getURL($project, '', 'dir'));
	$name = ($config->flatIndex) ? $project->getDisplayName() : $project->name;
	$listvar['projectlink'] = '<a href="'.$url.'">'.escape($name).'</a>';
	$listvar['projectname'] = escape($name);
	$listvar['projecturl'] = $url;
	$listvar['rowparity'] = $parity % 2;
	$parity++;
	$listvar['groupparity'] = $groupparity % 2;
	$groupparity++;
	$listvar['groupname'] = ($curgroup != null) ? $curgroup : '';
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
