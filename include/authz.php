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
	var $user = null;
	var $accessfile = null;

	// {{{ __construct

	function __construct() {
		$this->setUsername();
	}

	// }}}

	function hasUsername() {
		return $this->user !== null;
	}

	function addAccessFile($accessfile) {
		$this->accessfile = $accessfile;
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
	function svnAuthzCommandString($repos, $path, $checkSubDirs = false) {
		global $config;
		return $config->getSvnAuthzCommand().' --repository '.$repos.' --path '.quote($path).
			' '.($this->hasUsername() ? '--username '.quote($this->user).' ' : '').($checkSubDirs ? '-R ' : '').quote($this->accessfile);
	}

	// {{{ hasReadAccess
	//
	// Returns true if the user has read access to the given path

	function hasReadAccess($repos, $path, $checkSubDirs = false) {
		if ($this->accessfile == null)
			return false;

		if ($path == '' || $path{0} != '/') {
			$path = '/'.$path;
		}

		$cmd = $this->svnAuthzCommandString($repos, $path, $checkSubDirs);
		$ret = runCommand($cmd);

		return $ret[0] != "no";
	}

	// }}}

	// {{{ hasUnrestrictedReadAccess
	//
	// Returns true if the user has read access to the given path and too
	// all subfolders

	function hasUnrestrictedReadAccess($repos, $path) {
		return $this->hasReadAccess($repos, $path, true);
	}

	// }}}

}
