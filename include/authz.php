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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
//
// --
//
// authz.php
//
// Handle SVN access file

class Authorization {
	var $accessCache	= array();
	var $accessFile		= null;
	var $user			= null;

	// {{{ __construct

	function __construct() {
		$this->setUsername();
	}

	// }}}

	function hasUsername() {
		return $this->user !== null;
	}

	function addAccessFile($accessFile) {
		$this->accessFile = $accessFile;
	}

	// {{{ setUsername()
	//
	// Set the username from the current http session

	function setUsername() {
		if (isset($_SERVER['REMOTE_USER'])) {
			$this->user = $_SERVER['REMOTE_USER'];
		} else if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
			$this->user = $_SERVER['REDIRECT_REMOTE_USER'];
		} else if (isset($_SERVER['PHP_AUTH_USER'])) {
			$this->user = $_SERVER['PHP_AUTH_USER'];
		}
	}

	// }}}

	// Private function to simplify creation of common SVN authz command string text.
	function svnAuthzCommandString($repo, $path, $checkSubDirs = false) {
		global $config;

		$cmd			= $config->getSvnAuthzCommand();
		$repoAndPath	= '--repository ' . quote($repo) . ' --path ' . quote($path);
		$username		= !$this->hasUsername()	? '' : '--username ' . quote($this->user);
		$subDirs		= !$checkSubDirs		? '' : '-R';
		$authzFile		= quote($this->accessFile);
		$retVal			= "${cmd} ${repoAndPath} ${username} ${subDirs} ${authzFile}";

		return $retVal;
	}

	// {{{ hasReadAccess
	//
	// Returns true if the user has read access to the given path

	function hasReadAccess($repos, $path, $checkSubDirs = false) {
		if ($this->accessFile == null)
			return false;

		if ($path == '' || $path[0] != '/') {
			$path = '/'.$path;
		}

		$cmd	= $this->svnAuthzCommandString($repos, $path, $checkSubDirs);
		$result	= 'no';

		// Access checks might be issued multiple times for the same repos and paths within one and
		// the same request, introducing a lot of overhead because of "svnauthz" especially with
		// many repos under Windows. The easiest way to somewhat optimize it for different scenarios
		// is using a cache.
		//
		// https://github.com/websvnphp/websvn/issues/78#issuecomment-489306169
		$cache			=& $this->accessCache;
		$cached			= isset($cache[$cmd])	? $cache[$cmd]		: null;
		$cachedWhen		= isset($cached)		? $cached['when']	: 0;
		$cachedExpired	= (time() - 60) > $cachedWhen;

		if ($cachedExpired) {
			// Sorting by "when" should be established somehow to only remove the oldest element
			// instead of an arbitrary first one, which might be the newest added last time.
			if (count($cache) >= 1000) {
				array_shift($cache);
			}

			$result			= runCommand($cmd)[0];
			$cache[$cmd]	= array('when'		=> time(),
									'result'	=> $result);
		} else {
			$result = $cached['result'];
		}

		return $result != 'no';
	}

	// }}}

	// {{{ hasUnrestrictedReadAccess
	//
	// Returns true if the user has read access to the given path and too
	// all subdirectories

	function hasUnrestrictedReadAccess($repos, $path) {
		return $this->hasReadAccess($repos, $path, true);
	}

	// }}}

}
