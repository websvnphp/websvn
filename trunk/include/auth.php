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
// auth.php
//
// Handle reading and interpretation of an SVN auth file

require_once 'include/accessfile.php';

define('UNDEFINED', 0);
define('ALLOW', 1);
define('DENY', 2);

class Authentication {
	var $rights;
	var $user = null;
	var $usersGroups = array();
	var $basicRealm = 'WebSVN';

	// {{{ __construct

	function Authentication($accessfile, $basicRealm = false) {
		$this->rights = new IniFile();
		$this->rights->readIniFile($accessfile);
		$this->setUsername();
		$this->identifyGroups();
		if ($basicRealm !== false) {
			$this->basicRealm = $basicRealm;
		}
	}

	// }}}

	function hasUsername() {
		return $this->user !== null;
	}

	function getBasicRealm() {
		return $this->basicRealm;
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

	// {{{ identifyGroups()
	//
	// Checks to see which groups and aliases the user belongs to

	function identifyGroups() {
		$this->usersGroups[] = '*';

		$aliases = $this->rights->getValues('aliases');
		if (is_array($aliases)) {
			foreach ($aliases as $alias => $user) {
				if ($user == strtolower($this->user)) {
					$this->usersGroups[] = '&'.$alias;
				}
			}
		}

		$groups = $this->rights->getValues('groups');
		if (is_array($groups)) {
			foreach ($groups as $group => $names) {
				if (empty($names))
					continue;
				if (in_array(strtolower($this->user), preg_split('/\s*,\s*/', $names))) {
					$this->usersGroups[] = '@'.$group;
				}

				foreach ($this->usersGroups as $users_group) {
					if (in_array($users_group, preg_split('/\s*,\s*/', $names))) {
						$this->usersGroups[] = '@'.$group;
					}
				}
			}
		}
	}

	// }}}

	// {{{ inList
	//
	// Check if the user is in the given list and return their read status
	// if they are (UNDEFINED, ALLOW or DENY)

	function inList($accessors, $user) {
		$output = UNDEFINED;
		foreach ($accessors as $key => $rights) {
			if (in_array($key, $this->usersGroups) || strcasecmp($key, $user) === 0) {
				if (strpos($rights, 'r') !== false) {
					return ALLOW;
				} else {
					$output = DENY;
				}
			}
		}
		return $output;
	}

	// }}}

	// {{{ hasReadAccess
	//
	// Returns true if the user has read access to the given path

	function hasReadAccess($repos, $path, $checkSubFolders = false) {
		$access = UNDEFINED;
		$repos = strtolower($repos); // .ini parser converts groups to lower-case
		$path = strtolower($path);
		if ($path == '' || $path{0} != '/') {
			$path = '/'.$path;
		}

		// If were told to, we should check sub folders of the path to see if there's
		// a read access below this level.	This is used to display the folders needed
		// to get to the folder to which read access is granted.

		if ($checkSubFolders) {
			$sections = $this->rights->getSections();

			foreach ($sections as $section => $accessers) {
				$qualified = $repos.':'.$path;
				$len = strlen($qualified);
				if ($len < strlen($section) && strncmp($section, $qualified, $len) == 0) {
					$access = $this->inList($accessers, $this->user);
				}

				if ($access != ALLOW) {
					$len = strlen($path);
					if ($len < strlen($section) && strncmp($section, $path, $len) == 0) {
						$access = $this->inList($accessers, $this->user);
					}
				}

				if ($access == ALLOW) {
					break;
				}
			}
		}

		// If we still don't have access, check each subpath of the path until we find an
		// access level...

		if ($access != ALLOW) {
			$access = UNDEFINED;

			if ($path != '/' && substr($path, -1) == '/') {
				$path = substr($path, 0, -1);
			}
			do {
				$accessers = $this->rights->getValues($repos.':'.$path);
				if (!empty($accessers)) {
					$access = $this->inList($accessers, $this->user);
				}
				if ($access == UNDEFINED) {
					$accessers = $this->rights->getValues($path);
					if (!empty($accessers)) {
						$access = $this->inList($accessers, $this->user);
					}
				}

				// If we've not got a match, remove the sub directory and start again
				if ($access == UNDEFINED) {
					if ($path == '/') {
						break;
					}
					$path = substr($path, 0, strrpos($path, '/'));
					if ($path == '') $path = '/';
				}

			} while ($access == UNDEFINED && $path != '');
		}

		return $access == ALLOW;
	}

	// }}}

	// {{{ hasUnrestrictedReadAccess
	//
	// Returns true if the user has read access to the given path and too
	// all subfolders

	function hasUnrestrictedReadAccess($repos, $path) {
		// First make sure that we have full read access at this level

		if (!$this->hasReadAccess($repos, $path, false)) {
			return false;
		}

		// Now check to see if there is a sub folder that's protected
		$repos = strtolower($repos); // .ini parser converts groups to lower-case
		$path = strtolower($path);
		if ($path != '/' && substr($path, -1) == '/') {
			$path = substr($path, 0, -1);
		}

		$sections = $this->rights->getSections();

		foreach ($sections as $section => $accessers) {
			$qualified = $repos.':'.$path;
			$len = strlen($qualified);
			$access = UNDEFINED;

			if ($len <= strlen($section) && strncmp($section, $qualified, $len) == 0) {
				$access = $this->inList($accessers, $this->user);
			}

			if ($access != DENY) {
				$len = strlen($path);
				if ($len <= strlen($section) && strncmp($section, $path, $len) == 0) {
					$access = $this->inList($accessers, $this->user);
				}
			}

			if ($access == DENY) {
				return false;
			}
		}

		return true;
	}

	// }}}

}
