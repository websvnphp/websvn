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
// configclass.php
//
// General class for handling configuration options

require_once 'include/command.php';
require_once 'include/auth.php';
require_once 'include/version.php';

// Auxillary functions used to sort repositories by name/group

// {{{ cmpReps($a, $b)

function cmpReps($a, $b) {
	// First, sort by group
	$g = strcasecmp($a->group, $b->group);
	if ($g) return $g;

	// Same group? Sort by name
	return strcasecmp($a->name, $b->name);
}

// }}}

// {{{ cmpGroups($a, $b)

function cmpGroups($a, $b) {
	$g = strcasecmp($a->group, $b->group);
	if ($g) return $g;

	return 0;
}

// }}}

// {{{ mergesort(&$array, [$cmp_function])

function mergesort(&$array, $cmp_function = 'strcmp') {
	// Arrays of size < 2 require no action

	if (count($array) < 2) return;

	// Split the array in half
	$halfway = count($array) / 2;
	$array1 = array_slice($array, 0, $halfway);
	$array2 = array_slice($array, $halfway);

	// Recurse to sort the two halves
	mergesort($array1, $cmp_function);
	mergesort($array2, $cmp_function);

	// If all of $array1 is <= all of $array2, just append them.
	if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
		$array = array_merge($array1, $array2);
		return;
	}

	// Merge the two sorted arrays into a single sorted array
	$array = array();
	$array1count = count($array1);
	$array2count = count($array2);
	$ptr1 = 0;
	$ptr2 = 0;
	while ($ptr1 < $array1count && $ptr2 < $array2count) {
		if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
			$array[] = $array1[$ptr1++];
		} else {
			$array[] = $array2[$ptr2++];
		}
	}

	// Merge the remainder
	while ($ptr1 < $array1count) $array[] = $array1[$ptr1++];
	while ($ptr2 < $array2count) $array[] = $array2[$ptr2++];

	return;
}

// }}}

// A Repository parent path configuration class

class ParentPath {
	// {{{ Properties

	var $path;
	var $group;
	var $pattern;
	var $skipAlreadyAdded;
	var $clientRootURL;

	// }}}

	// {{{ __construct($path [, $group [, $pattern [, $skipAlreadyAdded [, $clientRootURL]]]])
	function ParentPath($path, $group = null, $pattern = false, $skipAlreadyAdded = true, $clientRootURL = '') {
		$this->path = $path;
		$this->group = $group;
		$this->pattern = $pattern;
		$this->skipAlreadyAdded = $skipAlreadyAdded;
		$this->clientRootURL = rtrim($clientRootURL, '/');
	}
	// }}}

	// {{{ findRepository($name)
	// look for a repository with $name
	function &findRepository($name) {
		global $config;
		if ($this->group != null) {
			$prefix = $this->group.'.';
			if (substr($name, 0, strlen($prefix)) == $prefix) {
				$name = substr($name, strlen($prefix));
			} else {
				$null = null;
				return $null;
			}
		}
		if ($handle = @opendir($this->path)) {
			// is there a directory named $name?
			$fullpath = $this->path.DIRECTORY_SEPARATOR.$name;
			if (is_dir($fullpath) && is_readable($fullpath)) {
				// And that contains a db directory (in an attempt to not include non svn repositories.
				$dbfullpath = $fullpath.DIRECTORY_SEPARATOR.'db';
				if (is_dir($dbfullpath) && is_readable($dbfullpath)) {
					// And matches the pattern if specified
					if ($this->pattern === false || preg_match($this->pattern, $name)) {
						$url = 'file:///'.$fullpath;
						$url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
						if ($url{strlen($url) - 1} == '/') {
							$url = substr($url, 0, -1);
						}

						if (!in_array($url, $config->_excluded, true)) {
							$clientRootURL = ($this->clientRootURL) ? $this->clientRootURL.'/'.$name : '';
							$rep = new Repository($name, $name, $url, $this->group, null, null, null, $clientRootURL);
							return $rep;
						}
					}
				}
			}
			closedir($handle);
		}
		$null = null;
		return $null;
	}
	// }}}

	// {{{ getRepositories()
	// return all repositories in the parent path matching pattern
	function &getRepositories() {
		$repos = array();
		$handle = @opendir($this->path);

		if (!$handle) return $repos;

		// For each file...
		while (false !== ($name = readdir($handle))) {
			$fullpath = $this->path.DIRECTORY_SEPARATOR.$name;
			if ($name{0} != '.' && is_dir($fullpath) && is_readable($fullpath)) {
				// And that contains a db directory (in an attempt to not include non svn repositories.
				$dbfullpath = $fullpath.DIRECTORY_SEPARATOR.'db';
				if (is_dir($dbfullpath) && is_readable($dbfullpath)) {
					// And matches the pattern if specified
					if ($this->pattern === false || preg_match($this->pattern, $name)) {
						$url = 'file:///'.$fullpath;
						$url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
						if ($url{strlen($url) - 1} == '/') {
							$url = substr($url, 0, -1);
						}
						$clientRootURL = ($this->clientRootURL) ? $this->clientRootURL.'/'.$name : '';
						$repos[] = new Repository($name, $name, $url, $this->group, null, null, null, $clientRootURL);
					}
				}
			}
		}
		closedir($handle);

		// Sort the repositories into alphabetical order
		if (!empty($repos)) {
			usort($repos, 'cmpReps');
		}

		return $repos;
	}
	// }}}

	// {{{ getSkipAlreadyAdded()
	// Return if we should skip already added repos for this parent path.
	function getSkipAlreadyAdded() {
		return $this->skipAlreadyAdded;
	}
	// }}}
}

// A Repository configuration class

class Repository {
	// {{{ Properties

	var $name;
	var $svnName;
	var $path;
	var $subpath;
	var $group;
	var $username = null;
	var $password = null;
	var $clientRootURL;

	// Local configuration options must start off unset

	var $allowDownload;
	var $minDownloadLevel;
	var $allowedExceptions = array();
	var $disallowedExceptions = array();
	var $logsShowChanges;
	var $rss;
	var $rssCaching;
	var $rssMaxEntries;
	var $spaces;
	var $ignoreSvnMimeTypes;
	var $ignoreWebSVNContentTypes;
	var $bugtraq;
	var $bugtraqProperties;
	var $auth = null;
	var $authBasicRealm;
	var $templatePath = false;

	// }}}

	// {{{ __construct($name, $svnName, $serverRootURL [, $group [, $username [, $password [, $clientRootURL]]]])

	function Repository($name, $svnName, $serverRootURL, $group = null, $username = null, $password = null, $subpath = null, $clientRootURL = null) {
		$this->name = $name;
		$this->svnName = $svnName;
		$this->path = $serverRootURL;
		$this->subpath = $subpath;
		$this->group = $group;
		$this->username = $username;
		$this->password = $password;
		$this->clientRootURL = rtrim($clientRootURL, '/');
	}

	// }}}

	// {{{ getDisplayName()

	function getDisplayName() {
		if (!empty($this->group)) {
			return $this->group.'.'.$this->name;
		}

		return $this->name;
	}

	// }}}

	// {{{ svnCredentials

	function svnCredentials() {
		$params = '';
		if ($this->username !== null && $this->username !== '') {
			$params .= ' --username '.quote($this->username);
		}
		if ($this->password !== null) {
			$params .= ' --password '.quote($this->password);
		}
		return $params;
	}

	// }}}

	// Local configuration accessors

	function setLogsShowChanges($enabled = true) {
		$this->logsShowChanges = $enabled;
	}

	function logsShowChanges() {
		global $config;

		if (isset($this->logsShowChanges))
			return $this->logsShowChanges;
		else
			return $config->logsShowChanges();
	}

	// {{{ RSS Feed

	function setRssEnabled($enabled) {
		$this->rss = $enabled;
	}

	function isRssEnabled() {
		global $config;

		if (isset($this->rss))
			return $this->rss;
		else
			return $config->isRssEnabled();
	}

	function setRssCachingEnabled($enabled = true) {
		$this->rssCaching = $enabled;
	}

	function isRssCachingEnabled() {
		global $config;

		if (isset($this->rssCaching))
			return $this->rssCaching;
		else
			return $config->isRssCachingEnabled();
	}

	function setRssMaxEntries($max) {
		$this->rssMaxEntries = $max;
	}

	function getRssMaxEntries() {
		global $config;

		if (isset($this->rssMaxEntries))
			return $this->rssMaxEntries;
		else
			return $config->getRssMaxEntries();
	}

	// }}}

	// {{{ Download

	function allowDownload() {
		$this->allowDownload = true;
	}

	function disallowDownload() {
		$this->allowDownload = false;
	}

	function getAllowDownload() {
		global $config;

		if (isset($this->allowDownload)) {
			return $this->allowDownload;
		}

		return $config->getAllowDownload();
	}

	function setMinDownloadLevel($level) {
		$this->minDownloadLevel = $level;
	}

	function getMinDownloadLevel() {
		global $config;

		if (isset($this->minDownloadLevel)) {
			return $this->minDownloadLevel;
		}

		return $config->getMinDownloadLevel();
	}

	function addAllowedDownloadException($path) {
		if ($path{strlen($path) - 1} != '/') $path .= '/';

		$this->allowedExceptions[] = $path;
	}

	function addDisallowedDownloadException($path) {
		if ($path{strlen($path) - 1} != '/') $path .= '/';

		$this->disallowedExceptions[] = $path;
	}

	function isDownloadAllowed($path) {
		global $config;

		// Check global download option
		if (!$this->getAllowDownload()) {
			return false;
		}

		// Check with access module
		if (!$this->hasUnrestrictedReadAccess($path)) {
			return false;
		}

		$subs = explode('/', $path);
		$level = count($subs) - 2;
		if ($level >= $this->getMinDownloadLevel()) {
			// Level OK, search for disallowed exceptions

			if ($config->findException($path, $this->disallowedExceptions)) {
				return false;
			}

			if ($config->findException($path, $config->disallowedExceptions)) {
				return false;
			}

			return true;

		} else {
			// Level not OK, search for disallowed exceptions

			if ($config->findException($path, $this->allowedExceptions)) {
				return true;
			}

			if ($config->findException($path, $config->allowedExceptions)) {
				return true;
			}

			return false;
		}
	}

	// }}}

	// {{{ Templates

	function setTemplatePath($path) {
		$this->templatePath = $path;
	}

	function getTemplatePath() {
		global $config;
		if (!empty($this->templatePath)) {
			return $this->templatePath;
		}

		return $config->getTemplatePath();
	}

	// }}}

	// {{{ Tab expansion

	function expandTabsBy($sp) {
		$this->spaces = $sp;
	}

	function getExpandTabsBy() {
		global $config;

		if (isset($this->spaces)) {
			return $this->spaces;
		}

		return $config->getExpandTabsBy();
	}

	// }}}

	// {{{ MIME-Type Handing

	function ignoreSvnMimeTypes() {
		$this->ignoreSvnMimeTypes = true;
	}

	function useSvnMimeTypes() {
		$this->ignoreSvnMimeTypes = false;
	}

	function getIgnoreSvnMimeTypes() {
		global $config;

		if (isset($this->ignoreSvnMimeTypes)) {
			return $this->ignoreSvnMimeTypes;
		}

		return $config->getIgnoreSvnMimeTypes();
	}

	function ignoreWebSVNContentTypes() {
		$this->ignoreWebSVNContentTypes = true;
	}

	function useWebSVNContentTypes() {
		$this->ignoreWebSVNContentTypes = false;
	}

	function getIgnoreWebSVNContentTypes() {
		global $config;

		if (isset($this->ignoreWebSVNContentTypes)) {
			return $this->ignoreWebSVNContentTypes;
		}

		return $config->getIgnoreWebSVNContentTypes();
	}

	// }}}

	// {{{ Bugtraq issue tracking

	function setBugtraqEnabled($enabled) {
		$this->bugtraq = $enabled;
	}

	function isBugtraqEnabled() {
		global $config;

		if (isset($this->bugtraq))
			return $this->bugtraq;
		else
			return $config->isBugtraqEnabled();
	}

	function setBugtraqProperties($properties) {
		$this->bugtraqProperties = $properties;
	}

	function getBugtraqProperties() {
		global $config;

		if (isset($this->bugtraqProperties))
			return $this->bugtraqProperties;
		else
			return $config->getBugtraqProperties();
	}

	// }}}

	// {{{ Authentication

	function useAuthenticationFile($file, $basicRealm = false) {
		if (is_readable($file)) {
			if ($this->auth === null) {
				$this->auth = new Authentication($basicRealm);
			}
			$this->auth->addAccessFile($file);
		} else {
			die('Unable to read authentication file "'.$file.'"');
		}
	}

	function &getAuth() {
		global $config;

		$a = null;
		if ($this->auth !== null) {
			$a =& $this->auth;
		} else {
			$a =& $config->getAuth();
		}
		return $a;
	}

	function hasReadAccess($path, $checkSubFolders = false) {
		global $config;

		$a =& $this->getAuth();

		if (!empty($a)) {
			return $a->hasReadAccess($this->svnName, $path, $checkSubFolders);
		}

		// No auth file - free access...
		return true;
	}

	function hasUnrestrictedReadAccess($path) {
		global $config;

		$a =& $this->getAuth();

		if (!empty($a)) {
			return $a->hasUnrestrictedReadAccess($this->svnName, $path);
		}

		// No auth file - free access...
		return true;
	}

	// }}}

}

// The general configuration class

class WebSvnConfig {
	// {{{ Properties

	// Tool path locations

	var $_svnCommandPrefix = '';
	var $_svnCommandPath = '';
	var $_svnConfigDir = '/tmp';
	var $_svnTrustServerCert = false;
	var $svn = 'svn --non-interactive --config-dir /tmp';
	var $diff = 'diff';
	var $enscript = 'enscript -q';
	var $sed = 'sed';
	var $gzip = 'gzip';
	var $tar = 'tar';
	var $zip = 'zip';

	// different modes for file and folder download

	var $defaultFileDlMode = 'plain';
	var $defaultFolderDlMode = 'gzip';

	var $validFileDlModes = array( 'gzip', 'zip', 'plain' );
	var $validFolderDlModes = array( 'gzip', 'zip' );

	// Other configuration items

	var $treeView = true;
	var $flatIndex = true;
	var $openTree = false;
	var $alphabetic = false;
	var $showLastModInIndex = true;
	var $showLastModInListing = true;
	var $showAgeInsteadOfDate = true;
	var $_showRepositorySelectionForm = true;
	var $_ignoreWhitespacesInDiff = false;
	var $serverIsWindows = false;
	var $multiViews = false;
	var $useEnscript = false;
	var $useEnscriptBefore_1_6_3 = false;
	var $useGeshi = false;
	var $inlineMimeTypes = array();
	var $allowDownload = false;
	var $tempDir = '';
	var $minDownloadLevel = 0;
	var $allowedExceptions = array();
	var $disallowedExceptions = array();
	var $logsShowChanges = false;
	var $rss = true;
	var $rssCaching = false;
	var $rssMaxEntries = 40;
	var $spaces = 8;
	var $bugtraq = false;
	var $bugtraqProperties = null;
	var $auth = null;
	var $blockRobots = false;

	var $templatePaths = array();
	var $userTemplate = false;

	var $ignoreSvnMimeTypes = false;
	var $ignoreWebSVNContentTypes = false;

	var $subversionVersion = '';
	var $subversionMajorVersion = '';
	var $subversionMinorVersion = '';

	var $defaultLanguage = 'en';
	var $ignoreAcceptedLanguages = false;

	var $quote = "'";
	var $pathSeparator = ':';

	var $_repositories = array();

	var $_parentPaths = array();	// parent paths to load

	var $_parentPathsLoaded = false;

	var $_excluded = array();

	// }}}

	// {{{ __construct()

	function WebSvnConfig() {
	}

	// }}}

	// {{{ Repository configuration

	function addRepository($name, $serverRootURL, $group = null, $username = null, $password = null, $clientRootURL = null) {
		$this->addRepositorySubpath($name, $serverRootURL, null, $group, $username, $password, $clientRootURL);
	}

	function addRepositorySubpath($name, $serverRootURL, $subpath, $group = null, $username = null, $password = null, $clientRootURL = null) {
		if (DIRECTORY_SEPARATOR != '/') {
			$serverRootURL = str_replace(DIRECTORY_SEPARATOR, '/', $serverRootURL);
			if ($subpath !== null) {
				$subpath = str_replace(DIRECTORY_SEPARATOR, '/', $subpath);
			}
		}
		$serverRootURL = trim($serverRootURL, '/');
		$svnName = substr($serverRootURL, strrpos($serverRootURL, '/') + 1);
		$this->_repositories[] = new Repository($name, $svnName, $serverRootURL, $group, $username, $password, $subpath, $clientRootURL);
	}

	// Automatically set up the repositories based on a parent path

	function parentPath($path, $group = null, $pattern = false, $skipAlreadyAdded = true, $clientRootURL = '') {
		$this->_parentPaths[] = new ParentPath($path, $group, $pattern, $skipAlreadyAdded, $clientRootURL);
	}

	function addExcludedPath($path) {
		$url = 'file:///'.$path;
		$url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
		if ($url{strlen($url) - 1} == '/') {
			$url = substr($url, 0, -1);
		}
		$this->_excluded[] = $url;
	}

	function getRepositories() {
		// lazily load parent paths
		if ($this->_parentPathsLoaded) return $this->_repositories;

		$this->_parentPathsLoaded = true;

		foreach ($this->_parentPaths as $parentPath) {
			$parentRepos = $parentPath->getRepositories();
			foreach ($parentRepos as $repo) {
				if (!$parentPath->getSkipAlreadyAdded()) {
					$this->_repositories[] = $repo;
				} else {
					// we have to check if we already have a repo with the same svn name
					$duplicate = false;
					foreach ($this->_repositories as $knownRepos) {
						if ($knownRepos->path == $repo->path && $knownRepos->subpath == $repo->subpath) {
							$duplicate = true;
							break;
						}
					}

					if (!$duplicate && !in_array($repo->path, $this->_excluded, true)) {
						$this->_repositories[] = $repo;
					}
				}
			}
		}

		return $this->_repositories;
	}

	function &findRepository($name) {
		// first look in the "normal repositories"
		foreach ($this->_repositories as $index => $rep) {
			if (strcmp($rep->getDisplayName(), $name) == 0) {
				$repref =& $this->_repositories[$index];
				return $repref;
			}
		}

		// now if the parent repos have not already been loaded
		// check them
		if (!$this->_parentPathsLoaded) {
			foreach ($this->_parentPaths as $parentPath) {
				$repref =& $parentPath->findRepository($name);
				if ($repref != null) {
					$this->_repositories[] = $repref;
					return $repref;
				}
			}
		}

		// Hack to return a string by reference; value retrieved at setup.php:414
		$str = 'Unable to find repository "'.escape($name).'".';
		$error =& $str;
		return $error;
	}

	// }}}

	// {{{ setServerIsWindows
	//
	// The server is running on Windows

	function setServerIsWindows() {
		$this->serverIsWindows = true;

		// On Windows machines, use double quotes around command line parameters
		$this->quote = '"';

		// On Windows, semicolon separates path entries in a list rather than colon.
		$this->pathSeparator = ';';
	}

	// }}}

	// {{{ MultiViews

	// useMultiViews
	//
	// Use MultiViews to access the repository

	function useMultiViews() {
		$this->multiViews = true;
	}

	function getUseMultiViews() {
		return $this->multiViews;
	}

	// }}}

	// {{{ Enscript

	// useEnscript
	//
	// Use Enscript to colourise listings

	function useEnscript($before_1_6_3 = false) {
		$this->useEnscript = true;
		$this->useEnscriptBefore_1_6_3 = $before_1_6_3;
	}

	function getUseEnscript() {
		return $this->useEnscript;
	}

	function getUseEnscriptBefore_1_6_3() {
		return $this->useEnscriptBefore_1_6_3;
	}

	// }}}

	// {{{ GeSHi

	// useGeshi
	//
	// Use GeSHi to colourise listings
	function useGeshi() {
		$this->useGeshi = true;
	}

	function getUseGeshi() {
		return $this->useGeshi;
	}

	// }}}

	// {{{ Inline MIME Types

	// inlineMimeTypes
	//
	// Specify MIME types to display inline in WebSVN pages
	function addInlineMimeType($type) {
		if (!in_array($type, $this->inlineMimeTypes)) {
			$this->inlineMimeTypes[] = $type;
		}
	}

	// }}}

	// {{{ Show changed files by default on log.php

	function setLogsShowChanges($enabled = true, $myrep = 0) {
		if (empty($myrep)) {
			$this->logsShowChanges = $enabled;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->logsShowChanges = $enabled;
		}
	}

	function logsShowChanges() {
		return $this->logsShowChanges;
	}

	// }}}

	// {{{ RSS

	function setRssEnabled($enabled = true, $myrep = 0) {
		if (empty($myrep)) {
			$this->rss = $enabled;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->setRssEnabled($enabled);
		}
	}

	function isRssEnabled() {
		return $this->rss;
	}

	function setRssCachingEnabled($enabled = true, $myrep = 0) {
		if (empty($myrep)) {
			$this->rssCaching = true;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->setRssCachingEnabled($enabled);
		}
	}

	function isRssCachingEnabled() {
		return $this->rssCaching;
	}

	// Maximum number of entries in RSS feed

	function setRssMaxEntries($max, $myrep = 0) {
		if (empty($myrep)) {
			$this->rssMaxEntries = $max;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->setRssMaxEntries($max);
		}
	}

	function getRssMaxEntries() {
		return $this->rssMaxEntries;
	}

	function getHideRSS() {
		return $this->rss;
	}

	// cacheRSS
	//
	// Enable caching of RSS feeds

	function enableRSSCaching($myrep = 0) {
		if (empty($myrep)) {
			$this->rssCaching = true;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->enableRSSCaching();
		}
	}

	function getRSSCaching() {
		return $this->rssCaching;
	}

	// }}}

	// {{{ Downloads

	// allowDownload
	//
	// Allow download of tarballs

	function allowDownload($myrep = 0) {
		if (empty($myrep)) {
			$this->allowDownload = true;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->allowDownload();
		}
	}

	function disallowDownload($myrep = 0) {
		if (empty($myrep)) {
			$this->allowDownload = false;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->disallowDownload();
		}
	}

	function getAllowDownload() {
		return $this->allowDownload;
	}

	function setTempDir($tempDir) {
		$this->tempDir = $tempDir;
	}

	function getTempDir() {
		if (empty($this->tempDir)) {
			if (!function_exists('sys_get_temp_dir')) {
				function sys_get_temp_dir() {
					if (($tmp = getenv('TMPDIR')) ||
						($tmp = getenv('TMP')) ||
						($tmp = getenv('TEMP')) ||
						($tmp = ini_get('upload_tmp_dir')))
						return $tmp;
					$tmp = tempnam(__FILE__, '');
					if (file_exists($tmp)) {
						unlink($tmp);
						return dirname($tmp);
					}
					return null;
				}
			}
			$this->tempDir = sys_get_temp_dir();
		}
		return $this->tempDir;
	}

	function setMinDownloadLevel($level, $myrep = 0) {
		if (empty($myrep)) {
			$this->minDownloadLevel = $level;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->setMinDownloadLevel($level);
		}
	}

	function getMinDownloadLevel() {
		return $this->minDownloadLevel;
	}

	function addAllowedDownloadException($path, $myrep = 0) {
		if ($path{strlen($path) - 1} != '/') {
			$path .= '/';
		}

		if (empty($myrep)) {
			$this->allowedExceptions[] = $path;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->addAllowedDownloadException($path);
		}
	}

	function addDisallowedDownloadException($path, $myrep = 0) {
		if ($path{strlen($path) - 1} != '/') {
			$path .= '/';
		}

		if (empty($myrep)) {
			$this->disallowedExceptions[] = $path;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->addDisallowedDownloadException($path);
		}
	}

	function findException($path, $exceptions) {
		foreach ($exceptions as $key => $exc) {
			if (strncmp($exc, $path, strlen($exc)) == 0) {
				return true;
			}
		}

		return false;
	}

	// }}}

	// {{{ getURL
	//
	// Get the URL to a path name based on the current config

	function getURL($rep, $path, $op) {
		list($base, $params) = $this->getUrlParts($rep, $path, $op);
		$url = $base.'?';
		foreach ($params as $k => $v) {
			$url .= $k.'='.urlencode($v).'&amp;';
		}
		return $url;
	}

	// }}}

	// {{{ getUrlParts
	//
	// Get the URL and parameters for a path name based on the current config

	function getUrlParts($rep, $path, $op) {
		$params = array();

		if ($this->multiViews) {
			$url = $_SERVER['SCRIPT_NAME'];
			if (preg_match('|\.php$|i', $url)) {
				// remove the .php extension
				$url = substr($url, 0, -4);
			}

			if ($path && $path{0} != '/') {
				$path = '/'.$path;
			}

			if (substr($url, -5) == 'index') {
				$url = substr($url, 0, -5).'wsvn';
			}

			if ($op == 'index') {
				$url .= '/';
			} else if (is_object($rep)) {
				$url .= '/'.$rep->getDisplayName().str_replace('%2F', '/', rawurlencode($path));

				if ($op && $op != 'dir' && $op != 'file') {
					$params['op'] = $op;
				}
			}

		} else {
			switch ($op) {
				case 'index':
					$url = '.';
					break;

				case 'dir':
					$url = 'listing.php';
					break;

				case 'revision':
					$url = 'revision.php';
					break;

				case 'file':
					$url = 'filedetails.php';
					break;

				case 'log':
					$url = 'log.php';
					break;

				case 'diff':
					$url = 'diff.php';
					break;

				case 'blame':
					$url = 'blame.php';
					break;

				case 'form':
					$url = 'form.php';
					break;

				case 'rss':
					$url = 'rss.php';
					break;

				case 'dl':
					$url = 'dl.php';
					break;

				case 'comp':
					$url = 'comp.php';
					break;
			}

			if (is_object($rep) && $op != 'index') {
				$params['repname'] = $rep->getDisplayName();
			}
			if (!empty($path)) {
				$params['path'] = $path;
			}
		}

		return array($url, $params);
	}

	// }}}

	// {{{ Paths and Commands

	// setPath
	//
	// Set the location of the given path

	function _setPath(&$var, $path, $name, $params = '') {
		if ($path == '') {
			// Search in system search path. No check for existence possible
			$var = $name;
		} else {
			$lastchar = substr($path, -1, 1);
			$isDir = ($lastchar == DIRECTORY_SEPARATOR || $lastchar == '/' || $lastchar == '\\');

			if (!$isDir) $path .= DIRECTORY_SEPARATOR;

			if (($this->serverIsWindows && !file_exists($path.$name.'.exe')) || (!$this->serverIsWindows && !file_exists($path.$name))) {
				echo 'Unable to find "'.$name.'" tool at location "'.$path.$name.'"';
				exit;
			}

			// On a windows machine we need to put quotes around the
			// entire command to allow for spaces in the path
			if ($this->serverIsWindows) {
				$var = '"'.$path.$name.'"';
			} else {
				$var = $path.$name;
			}
		}

		// Append parameters
		if ($params != '') $var .= ' '.$params;
	}

	// Define directory path to use for --config-dir parameter
	function setSvnConfigDir($path) {
		$this->_svnConfigDir = $path;
		$this->_updateSVNCommand();
	}

	// Define flag to use --trust-server-cert parameter
	function setTrustServerCert() {
		$this->_svnTrustServerCert = true;
		$this->_updateSVNCommand();
	}

	// Define the location of the svn command (e.g. '/usr/bin')
	function setSvnCommandPath($path) {
		$this->_svnCommandPath = $path;
		$this->_updateSVNCommand();
	}

	// Define a prefix to include before every SVN command (e.g. 'arch -i386')
	function setSvnCommandPrefix($prefix) {
		$this->_svnCommandPrefix = $prefix;
		$this->_updateSVNCommand();
	}

	function _updateSVNCommand() {
		$this->_setPath($this->svn, $this->_svnCommandPath, 'svn', '--non-interactive --config-dir '.$this->_svnConfigDir.($this->_svnTrustServerCert ? ' --trust-server-cert' : ''));
		$this->svn = $this->_svnCommandPrefix.' '.$this->svn;
	}

	function getSvnCommand() {
		return $this->svn;
	}

	// setDiffPath
	//
	// Define the location of the diff command

	function setDiffPath($path) {
		$this->_setPath($this->diff, $path, 'diff');
	}

	function getDiffCommand() {
		return $this->diff;
	}

	// setEnscriptPath
	//
	// Define the location of the enscript command

	function setEnscriptPath($path) {
		$this->_setPath($this->enscript, $path, 'enscript', '-q');
	}

	function getEnscriptCommand() {
		return $this->enscript;
	}

	// setSedPath
	//
	// Define the location of the sed command

	function setSedPath($path) {
		$this->_setPath($this->sed, $path, 'sed');
	}

	function getSedCommand() {
		return $this->sed;
	}

	// setTarPath
	//
	// Define the location of the tar command

	function setTarPath($path) {
		$this->_setPath($this->tar, $path, 'tar');
	}

	function getTarCommand() {
		return $this->tar;
	}

	// setGzipPath
	//
	// Define the location of the GZip command

	function setGzipPath($path) {
		$this->_setPath($this->gzip, $path, 'gzip');
	}

	function getGzipCommand() {
		return $this->gzip;
	}

	// setZipPath
	//
	// Define the location of the zip command
	function setZipPath($path) {
		$this->_setPath($this->zip, $path, 'zip');
	}

	function getZipPath() {
		return $this->zip;
	}

	// setDefaultFileDlMode
	//
	// Define the default file download mode - one of [gzip, zip, plain]
	function setDefaultFileDlMode($dlmode) {
		if (in_array($dlmode, $this->validFileDlModes)) {
			$this->defaultFileDlMode = $dlmode;
		} else {
			echo 'Setting default file download mode to an invalid value "'.$dlmode.'"';
			exit;
		}
	}

	function getDefaultFileDlMode() {
		return $this->defaultFileDlMode;
	}

	// setDefaultFolderDlMode
	//
	// Define the default folder download mode - one of [gzip, zip]
	function setDefaultFolderDlMode($dlmode) {
		if (in_array($dlmode, $this->validFolderDlModes)) {
			$this->defaultFolderDlMode = $dlmode;
		} else {
			echo 'Setting default file download mode to an invalid value "'.$dlmode.'"';
			exit;
		}
	}

	function getDefaultFolderDlMode() {
		return $this->defaultFolderDlMode;
	}

	// Templates

	function addTemplatePath($path) {
		$lastchar = substr($path, -1, 1);
		if ($lastchar != '/' && $lastchar != '\\') {
			$path .= DIRECTORY_SEPARATOR;
		}

		if (!in_array($path, $this->templatePaths)) {
			$this->templatePaths[] = $path;
		}
	}

	function setTemplatePath($path, $myrep = null) {
		$lastchar = substr($path, -1, 1);
		if ($lastchar != '/' && $lastchar != '\\') {
			$path .= DIRECTORY_SEPARATOR;
		}

		if ($myrep !== null) {
			// fixed template for specific repository
			$repo =& $this->findRepository($myrep);
			$repo->setTemplatePath($path);
		} else {
			// for backward compatibility
			if (in_array($path, $this->templatePaths)) {
				array_splice($this->templatePaths, array_search($path, $this->templatePaths), 1);
			}
			array_unshift($this->templatePaths, $path);
		}
	}

	function getTemplatePath() {
		if (count($this->templatePaths) == 0) {
			echo 'No template path added in config file';
			exit;
		}
		if ($this->userTemplate !== false)
			return $this->userTemplate;
		else
			return $this->templatePaths[0];
	}

	// }}}

	function setDefaultLanguage($language) {
		$this->defaultLanguage = $language;
	}

	function getDefaultLanguage() {
		return $this->defaultLanguage;
	}

	function ignoreUserAcceptedLanguages() {
		$this->ignoreAcceptedLanguages = true;
	}

	function useAcceptedLanguages() {
		return !$this->ignoreAcceptedLanguages;
	}

	// {{{ Tab expansion functions

	function expandTabsBy($sp, $myrep = 0) {
		if (empty($myrep)) {
			$this->spaces = $sp;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->expandTabsBy($sp);
		}
	}

	function getExpandTabsBy() {
		return $this->spaces;
	}

	// }}}

	// {{{ Bugtraq issue tracking

	function setBugtraqEnabled($enabled, $myrep = 0) {
		if (empty($myrep)) {
			$this->bugtraq = $enabled;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->setBugtraqEnabled($enabled);
		}
	}

	function isBugtraqEnabled() {
		return $this->bugtraq;
	}

	function setBugtraqProperties($message, $logregex, $url, $append = true, $myrep = null) {
		$properties = array();
		$properties['bugtraq:message'] = $message;
		$properties['bugtraq:logregex'] = $logregex;
		$properties['bugtraq:url'] = $url;
		$properties['bugtraq:append'] = (bool)$append;
		if ($myrep === null) {
			$this->bugtraqProperties = $properties;
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->setBugtraqProperties($properties);
		}
	}

	function getBugtraqProperties() {
		return $this->bugtraqProperties;
	}

	// }}}

	// {{{ Misc settings

	function ignoreSvnMimeTypes() {
		$this->ignoreSvnMimeTypes = true;
	}

	function getIgnoreSvnMimeTypes() {
		return $this->ignoreSvnMimeTypes;
	}

	function ignoreWebSVNContentTypes() {
		$this->ignoreWebSVNContentTypes = true;
	}

	function getIgnoreWebSVNContentTypes() {
		return $this->ignoreWebSVNContentTypes;
	}

	function useAuthenticationFile($file, $myrep = 0, $basicRealm = false) {
		if (empty($myrep)) {
			if (is_readable($file)) {
				if ($this->auth === null) {
					$this->auth = new Authentication($basicRealm);
				}
				$this->auth->addAccessFile($file);
			} else {
				echo 'Unable to read authentication file "'.$file.'"';
				exit;
			}
		} else {
			$repo =& $this->findRepository($myrep);
			$repo->useAuthenticationFile($file);
		}
	}

	function &getAuth() {
		return $this->auth;
	}

	function areRobotsBlocked() {
		return $this->blockRobots;
	}

	function setBlockRobots($value = true) {
		$this->blockRobots = $value;
	}

	function useTreeView() {
		$this->treeView = true;
	}

	function getUseTreeView() {
		return $this->treeView;
	}

	function useFlatView() {
		$this->treeView = false;
	}

	function useTreeIndex($open) {
		$this->flatIndex = false;
		$this->openTree = $open;
	}

	function getUseFlatIndex() {
		return $this->flatIndex;
	}

	function getOpenTree() {
		return $this->openTree;
	}

	function setAlphabeticOrder($flag) {
		$this->alphabetic = $flag;
	}

	function isAlphabeticOrder() {
		return $this->alphabetic;
	}

	function showLastModInIndex() {
		return $this->showLastModInIndex;
	}

	function setShowLastModInIndex($show) {
		$this->showLastModInIndex = $show;
	}

	function showLastModInListing() {
		return $this->showLastModInListing;
	}

	function setShowLastModInListing($show) {
		$this->showLastModInListing = $show;
	}

	function showAgeInsteadOfDate() {
		return $this->showAgeInsteadOfDate;
	}

	function setShowAgeInsteadOfDate($show) {
		$this->showAgeInsteadOfDate = $show;
	}

	function showRepositorySelectionForm() {
		return $this->_showRepositorySelectionForm;
	}

	function setShowRepositorySelectionForm($show) {
		$this->_showRepositorySelectionForm = $show;
	}

	function getIgnoreWhitespacesInDiff() {
		return $this->_ignoreWhitespacesInDiff;
	}

	function setIgnoreWhitespacesInDiff($ignore) {
		$this->_ignoreWhitespacesInDiff = $ignore;
	}

	// Methods for storing version information for the command-line svn tool

	function setSubversionVersion($subversionVersion) {
		$this->subversionVersion = $subversionVersion;
	}

	function getSubversionVersion() {
		return $this->subversionVersion;
	}

	function setSubversionMajorVersion($subversionMajorVersion) {
		$this->subversionMajorVersion = $subversionMajorVersion;
	}

	function getSubversionMajorVersion() {
		return $this->subversionMajorVersion;
	}

	function setSubversionMinorVersion($subversionMinorVersion) {
		$this->subversionMinorVersion = $subversionMinorVersion;
	}

	function getSubversionMinorVersion() {
		return $this->subversionMinorVersion;
	}

	// }}}

	// {{{ Sort the repostories
	//
	// This function sorts the repositories by group name.	The contents of the
	// group are left in there original order, which will either be sorted if the
	// group was added using the parentPath function, or defined for the order in
	// which the repositories were included in the user's config file.
	//
	// Note that as of PHP 4.0.6 the usort command no longer preserves the order
	// of items that are considered equal (in our case, part of the same group).
	// The mergesort function preserves this order.

	function sortByGroup() {
		if (!empty($this->_repositories)) {
			mergesort($this->_repositories, 'cmpGroups');
		}
	}

	// }}}
}
