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
// form.php
//
// Handling of WebSVN forms

require_once 'include/setup.php';
require_once 'include/utils.php';

// Generic redirect handling

function redirect($loc) {
	$url = getFullURL($loc);

	// technically, a die(header('Location: '.$url)); would suffice for all web browsers... ~J
	header('Location: '.$url);
	echo '<html>'."\n";
	echo "\t".' <head>'."\n";
	echo "\t\t".' <title>Redirecting...</title>'."\n";
	echo "\t\t".' <meta http-equiv="refresh" content="0; url='.$url.'" />'."\n";
	echo "\t\t".' <script type="application/x-javascript"><![CDATA[ window.location.href = \''.$url.'\'; ]]></script>'."\n";
	echo "\t".' </head>'."\n";
	echo "\t".' <body>'."\n";
	echo "\t\t".' <p>If you are not automatically redirected, please click <a href="'.$url.'">here</a> to continue.</p>'."\n";
	echo "\t".' </body>'."\n";
	echo '</html>';
}

// Handle project selection

if (@$_REQUEST['selectproj']) {
	$basedir = dirname($_SERVER['PHP_SELF']);
	if ($basedir != '' && $basedir != DIRECTORY_SEPARATOR && $basedir != '\\' && $basedir != '/' ) {
		$basedir .= '/';
	} else {
		$basedir = '/';
	}

	if ($config->multiViews) {
		$rep =& $config->findRepository(@$_REQUEST['repname']);
		if ($rep == null) {
			include $locwebsvnreal.'/index.php';
			exit;
		}
	}

	$url = $config->getURL($rep, '', 'dir');
	$url = html_entity_decode($url);

	if ($config->multiViews) {
		redirect($url);
	} else {
		redirect($basedir.$url);
	}
}
