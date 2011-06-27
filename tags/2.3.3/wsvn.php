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
// wsvn.php
//
// Glue for MultiViews

// --- CONFIGURE THIS VARIABLE ---

// Location of websvn directory via HTTP
//
// e.g. For "http://www.example.com/websvn" use '/websvn'
//
// Note that wsvn.php need not be in the /websvn directory (and often isn't).
// If you want to use the root server directory, just use a blank string ('').
$locwebsvnhttp = '/websvn';

// Physical location of websvn directory. Change this if your wsvn.php is not in
// the same folder as the rest of the distribution
$locwebsvnreal = dirname(__FILE__);

// --- DON'T CHANGE BELOW HERE ---

chdir($locwebsvnreal);

// Tell files that we are using multiviews if they are unable to access $config.
if (!defined('WSVN_MULTIVIEWS')) {
	define('WSVN_MULTIVIEWS', 1);
}

ini_set('include_path', $locwebsvnreal);

require_once 'include/setup.php';
require_once 'include/svnlook.php';

if (!isset($_REQUEST['sc'])) {
	$_REQUEST['sc'] = 1;
}

if ($config->multiViews) {
	$op = @$_REQUEST['op'];
	// This means the user wants to browse another project, so we switch to it and exit.
	if ($op == 'rep') {
		$rep =& $config->findRepository(@$_REQUEST['repname']);
		if ($rep != null) {
			header('Location: '.$config->getURL($rep, '', 'dir'));
		} else {
			include $locwebsvnreal.'/index.php';
		}
		exit;
	}

	$origPathInfo = isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '';
	$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$path = trim(empty($pathInfo) ? $origPathInfo : $pathInfo);

	// Remove initial slash
	$path = substr($path, 1);
	if (empty($path)) {
		include $locwebsvnreal.'/index.php';
		exit;
	}

	// Split the path into repository and path elements
	// Note: we have to cope with the repository name having a slash in it

	$pos = strpos($path, '/');
	if ($pos === false) {
		$pos = strlen($path);
	}
	$name = substr($path, 0, $pos);

	$rep =& $config->findRepository($name);
	if ($rep != null && is_object($rep)) {
		$path = substr($path, $pos);
		if ($path == '') {
			$path = '/';
		}
		$repname = $name;
	} else {
		include $locwebsvnreal.'/index.php';
		exit;
	}

	createProjectSelectionForm();
	createRevisionSelectionForm();
	$vars['repurl'] = $config->getURL($rep, '', 'dir');
	$vars['clientrooturl'] = $rep->clientRootURL;
	$vars['allowdownload'] = $rep->getAllowDownload();
	$vars['repname'] = escape($rep->getDisplayName());

	// find the operation type
	switch ($op) {
		case 'dir':
			$file = 'listing.php';
			break;
		case 'revision':
			$file = 'revision.php';
			break;
		case 'file':
			$file = 'filedetails.php';
			break;
		case 'log':
			$file = 'log.php';
			break;
		case 'diff':
			$file = 'diff.php';
			break;
		case 'blame':
			$file = 'blame.php';
			break;
		case 'rss':
			$file = 'rss.php';
			break;
		case 'dl':
			$file = 'dl.php';
			break;
		case 'comp':
			$file = 'comp.php';
			break;
		default:
			$svnrep = new SVNRepository($rep);
			if ($svnrep->isFile($path, $rev, $peg)) {
				$file = 'filedetails.php';
			} else {
				$file = 'listing.php';
			}
			break;
	}

	// Now include the file that handles it
	include $locwebsvnreal.'/'.$file;

} else {
	$vars['error'] = 'MultiViews must be enabled in <code>include/config.php</code> in order to use <code>wsvn.php</code>. See <a href="'.$locwebsvnhttp.'/doc/install.html#multiviews">the install docs</a> for details, or use <a href="'.$locwebsvnhttp.'">this path</a> instead.';
	include $locwebsvnreal.'/index.php';
	exit;
}
