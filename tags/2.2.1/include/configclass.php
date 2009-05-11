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
// configclass.php4
//
// General class for handling configuration options

require_once("include/command.php");
require_once("include/auth.php");
require_once("include/version.php");

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
  $ptr1 = $ptr2 = 0;
  while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
    if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
      $array[] = $array1[$ptr1++];
    } else {
      $array[] = $array2[$ptr2++];
    }
  }

  // Merge the remainder
  while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
  while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];

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

  // }}}

  // {{{ __construct($path, [$group, [$pattern, [$skipAlreadyAdded]]])
  function ParentPath($path, $group = null, $pattern = false, $skipAlreadyAdded = true) {
    $this->path = $path;
    $this->group = $group;
    $this->pattern = $pattern;
    $this->skipAlreadyAdded = $skipAlreadyAdded;
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
            if ($url{strlen($url) - 1} == "/") {
              $url = substr($url, 0, -1);
            }

            if (!in_array($url, $config->_excluded, true)) {
              $rep = new Repository($name, $name, $url, $this->group, null, null);
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
     if ($handle = @opendir($this->path)) {
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
              if ($url{strlen($url) - 1} == "/") {
                $url = substr($url, 0, -1);
              }

              $repos[] = new Repository($name, $name, $url, $this->group, null, null);
            }
          }
        }
      }
      closedir($handle);
    }


    // Sort the repositories into alphabetical order
    if (!empty($repos)) {
      usort($repos, "cmpReps");
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
  var $username;
  var $password;

  // Local configuration options must start off unset

  var $allowDownload;
  var $minDownloadLevel;
  var $allowedExceptions = array();
  var $disallowedExceptions = array();
  var $rss;
  var $spaces;
  var $ignoreSvnMimeTypes;
  var $ignoreWebSVNContentTypes;
  var $bugtraq;
  var $auth;
  var $contentEnc;
  var $templatePath;

  // }}}

  // {{{ __construct($name, $svnName, $path, [$group, [$username, [$password]]])

  function Repository($name, $svnName, $path, $group = NULL, $username = NULL, $password = NULL, $subpath = NULL) {
    $this->name = $name;
    $this->svnName = $svnName;
    $this->path = $path;
    $this->subpath = $subpath;
    $this->group = $group;
    $this->username = $username;
    $this->password = $password;
  }

  // }}}

  // {{{ getDisplayName()

  function getDisplayName() {
    if (!empty($this->group)) {
      return $this->group.".".$this->name;
    }

    return $this->name;
  }

  // }}}

  // {{{ svnParams

  function svnParams() {
    if (!empty($this->username)) {
      return " --username ".$this->username." --password ".$this->password." ";
    }

    return " ";
  }

  // }}}

  // Local configuration accessors

  // {{{ RSS Feed

  function hideRSS() {
    $this->rss = false;
  }

  function showRSS() {
    $this->rss = true;
  }

  function getHideRSS() {
    global $config;

    if (isset($this->rss)) {
      return $this->rss;
    }

    return $config->getHideRSS();
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
    if ($path{strlen($path) - 1} != "/") $path .= "/";

    $this->allowedExceptions[] = $path;
  }

  function addDisallowedDownloadException($path) {
    if ($path{strlen($path) - 1} != "/") $path .= "/";

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

    $subs = explode("/", $path);
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
    $lastchar = substr($path, -1, 1);
    if (!($lastchar == DIRECTORY_SEPARATOR || $lastchar == '/' || $lastchar == '\\')) {
      $path .= DIRECTORY_SEPARATOR;
    }

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

  // {{{ Issue Tracking

  function useBugtraqProperties() {
    $this->bugtraq = true;
  }

  function ignoreBugtraqProperties() {
    $this->bugtraq = false;
  }

  function getBugtraq() {
    global $config;

    if (isset($this->bugtraq)) {
      return $this->bugtraq;
    }

    return $config->getBugtraq();
  }

  // }}}

  // {{{ Encodings

  function setContentEncoding($contentEnc) {
    $this->contentEnc = $contentEnc;
  }

  function getContentEncoding() {
    global $config;

    if (isset($this->contentEnc)) {
      return $this->contentEnc;
    }

    return $config->getContentEncoding();
  }

  // }}}

  // {{{ Authentication

  function useAuthenticationFile($file) {
    if (is_readable($file)) {
      $this->auth = new Authentication($file);
    } else {
      die('Unable to read authentication file "'.$file.'"');
    }
  }

  function hasReadAccess($path, $checkSubFolders = false) {
    global $config;

    $a = null;
    if (isset($this->auth)) {
      $a =& $this->auth;
    } else {
      $a =& $config->getAuth();
    }

    if (!empty($a)) {
      return $a->hasReadAccess($this->svnName, $path, $checkSubFolders);
    }

    // No auth file - free access...
    return true;
  }

  function hasUnrestrictedReadAccess($path) {
    global $config;

    $a = null;
    if (isset($this->auth)) {
      $a =& $this->auth;
    } else {
      $a =& $config->getAuth();
    }

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

  var $svnlook = "svnlook";
  var $_commandPath = "";
  var $_configPath = "/tmp";
  var $svn = "svn --non-interactive --config-dir /tmp";
  var $svn_noparams = "svn --config-dir /tmp";
  var $diff = "diff";
  var $enscript ="enscript -q";
  var $sed = "sed";
  var $gzip = "gzip";
  var $tar = "tar";
  var $zip = "zip";

  // different modes for file and folder download

  var $defaultFileDlMode = "plain";
  var $defaultFolderDlMode = "gzip";

  var $validFileDlModes = array( 'gzip', 'zip', 'plain' );
  var $validFolderDlModes = array( 'gzip', 'zip' );

  // Other configuration items

  var $treeView = true;
  var $flatIndex = true;
  var $openTree = false;
  var $alphabetic = false;
  var $showLastMod = true;
  var $showAgeInsteadOfDate = true;
  var $_showRepositorySelectionForm = true;
  var $serverIsWindows = false;
  var $multiViews = false;
  var $useEnscript = false;
  var $useGeshi = false;
  var $inlineMimeTypes = array();
  var $allowDownload = false;
  var $tarballTmpDir = 'temp';
  var $minDownloadLevel = 0;
  var $allowedExceptions = array();
  var $disallowedExceptions = array();
  var $rss = true;
  var $spaces = 8;
  var $bugtraq = false;
  var $auth = "";

  var $templatePath = "./templates/calm/";

  var $ignoreSvnMimeTypes = false;
  var $ignoreWebSVNContentTypes = false;

  var $subversionVersion = "";
  var $subversionMajorVersion = "";
  var $subversionMinorVersion = "";

  // Default character encodings
  var $inputEnc = "";      // Encoding of output returned from command line
  var $contentEnc = "";    // Encoding of repository content
  var $outputEnc = "UTF-8";  // Encoding of web page.  Now forced to UTF-8

  var $defaultLanguage = 'en';
  var $ignoreAcceptedLanguages = false;

  var $quote = "'";
  var $pathSeparator = ":";

  var $_repositories = array();

  var $_parentPaths = array();  // parent paths to load

  var $_parentPathsLoaded = false;

  var $_excluded = array();

  // }}}

  // {{{ __construct()

  function WebSvnConfig() {
  }

  // }}}

  // {{{ Repository configuration

  function addRepository($name, $url, $group = NULL, $username = NULL, $password = NULL) {
    $url = str_replace(DIRECTORY_SEPARATOR, "/", $url);

    if ($url{strlen($url) - 1} == "/") {
      $url = substr($url, 0, -1);
    }

    $svnName = substr($url, strrpos($url, "/") + 1);
    $this->_repositories[] = new Repository($name, $svnName, $url, $group, $username, $password);
  }

  function addRepositorySubpath($name, $url, $subpath, $group = NULL, $username = NULL, $password = NULL) {
    $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
    $subpath = str_replace(DIRECTORY_SEPARATOR, '/', $subpath);

    if ($url{strlen($url) - 1} == '/') {
      $url = substr($url, 0, -1);
    }

    $svnName = substr($url, strrpos($url, '/') + 1);
    $this->_repositories[] = new Repository($name, $svnName, $url, $group, $username, $password, $subpath);
  }


  function getRepositories() {
    // lazily load parent paths
    if (!$this->_parentPathsLoaded) {
      $this->_parentPathsLoaded = true;
      foreach ($this->_parentPaths as $parentPath) {
        $parentRepos = $parentPath->getRepositories();
        foreach ($parentRepos as $repo) {
          if (!$parentPath->getSkipAlreadyAdded()) {
            $this->_repositories[] = $repo;
          } else {
            // we have to check if we already have a repo with the same svn name
            $duplicate = false;
            if (!empty($this->_repositories)) {
              foreach ($this->_repositories as $knownRepos) {
                if ($knownRepos->svnName == $repo->svnName && $knownRepos->subpath == $repo->subpath) {
                  $duplicate = true;
                  break;
                }
              }
            }

            if (!$duplicate && !in_array($repo->path, $this->_excluded, true)) {
              $this->_repositories[] = $repo;
            }
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
          return $repref;
        }
      }
    }

    print "ERROR: Unable to find repository '".htmlentities($name, ENT_QUOTES, 'UTF-8')."'";
    exit;
  }

  // }}}

  // {{{ setServerIsWindows
  //
  // The server is running on Windows

  function setServerIsWindows() {
    $this->serverIsWindows = true;

    // Try to set the input encoding intelligently

    $cp = 0;
    if ($cp = @shell_exec("CHCP")) {
      $cp = trim(substr($cp, strpos($cp, ":") + 1));
      settype($cp, "integer");
    }

    // Use the most sensible default value if that failed
    if ($cp == 0) $cp = 850;

    // We assume, as a default, that the encoding of the repository contents is
    // in iso-8859-1, to be compatible with compilers and the like.
    $this->setInputEncoding("CP$cp", "iso-8859-1");

    // On Windows machines, use double quotes around command line parameters
    $this->quote = '"';

    // On Windows, semicolon separates path entries in a list rather than colon.
    $this->pathSeparator = ";";
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

  function useEnscript() {
    $this->useEnscript = true;
  }

  function getUseEnscript() {
    return $this->useEnscript;
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
  
  function getInlineMimeTypes() {
    return $this->inlineMimeTypes;
  }

  // }}}

  // {{{ RSS

  // offerRSS
  //
  // Use Enscript to colourise listings

  function hideRSS($myrep = 0) {
    if (empty($myrep)) {
      $this->rss = false;
    } else {
      $repo =& $this->findRepository($myrep);
      $repo->hideRSS();
    }
  }

  function getHideRSS() {
    return $this->rss;
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

  function setTarballTmpDir($tmpdir) {
    $this->tarballTmpDir = $tmpdir;
  }

  function getTarballTmpDir() {
    return $this->tarballTmpDir;
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
    if ($path{strlen($path) - 1} != "/") {
      $path .= "/";
    }

    if (empty($myrep)) {
      $this->allowedExceptions[] = $path;
    } else {
      $repo =& $this->findRepository($myrep);
      $repo->addAllowedDownloadException($path);
    }
  }

  function addDisallowedDownloadException($path, $myrep = 0) {
    if ($path{strlen($path) - 1} != "/") {
      $path .= "/";
    }

    if (empty($myrep)) {
      $this->disallowedExceptions[] = $path;
    } else {
      $repo =& $this->findRepository($myrep);
      $repo->addDisallowedDownloadException($path);
    }
  }

  function findException($path, $exceptions) {
    foreach ($exceptions As $key => $exc) {
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
      $url = $_SERVER["SCRIPT_NAME"];
      if (preg_match('|\.php$|i', $url))  {
        // remove the .php extension
        $url = substr($url, 0, -4);
      }

      if ($path && $path{0} != "/") {
        $path = "/".$path;
      }

      if ($op == 'index') {
        $url .= '/';
      } else if (is_object($rep)) {
        $url .= "/".$rep->getDisplayName().str_replace('%2F', '/', rawurlencode($path));

        if ($op != "dir" && $op != "file") {
          $params['op'] = $op;
        }
      }

    } else {
      switch ($op) {
        case "index":
          $url = ".";
          break;

        case "dir":
          $url = "listing.php";
          break;

        case "revision":
          $url = "revision.php";
          break;

        case "file":
          $url = "filedetails.php";
          break;

        case "log":
          $url = "log.php";
          break;

        case "diff":
          $url = "diff.php";
          break;

        case "blame":
          $url = "blame.php";
          break;

        case "form":
          $url = "form.php";
          break;

        case "rss":
          $url = "rss.php";
          break;

        case "dl":
          $url = "dl.php";
          break;

        case "comp":
          $url = "comp.php";
          break;
      }

      if ($rep !== -1 && $op != 'index') {
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

  function setPath(&$var, $path, $name, $params = '') {
    if ($path == '') {
      // Search in system search path. No check for existence possible
      $var = $name;
    } else {
      $lastchar = substr($path, -1, 1);
      $isDir = ($lastchar == DIRECTORY_SEPARATOR || $lastchar == '/' || $lastchar == '\\');

      if (!$isDir) $path .= DIRECTORY_SEPARATOR;

      if (($this->serverIsWindows && !file_exists($path.$name.'.exe')) || (!$this->serverIsWindows && !file_exists($path.$name))) {
        echo "Unable to find '$name' tool at location '$path$name'";
        exit;
      }

      // On a windows machine we need to put spaces around the entire command
      // to allow for spaces in the path
      if ($this->serverIsWindows) {
        $var = '"'.$path.$name.'"';
      } else {
        $var = $path.$name;
      }
    }

    // Append parameters
    if ($params != '') $var .= ' '.$params;
  }

  function setConfigPath($path) {
    $this->_configPath = $path;
    $this->updateSVNCommands();
  }

  // setSVNCommandPath
  //
  // Define the location of the svn and svnlook commands

  function setSVNCommandPath($path) {
    $this->_commandPath = $path;
    $this->updateSVNCommands();
  }

  function updateSVNCommands() {
    $this->setPath($this->svn, $this->_commandPath, "svn", "--non-interactive --config-dir ".$this->_configPath);
    $this->setPath($this->svn_noparams, $this->_commandPath, "svn", " --config-dir ".$this->_configPath);
    $this->setPath($this->svnlook, $this->_commandPath, "svnlook");
  }

  function getSvnCommand() {
    return $this->svn;
  }

  function getCleanSvnCommand() {
    return $this->svn_noparams;
  }

  function getSvnlookCommand() {
    return $this->svnlook;
  }

  // setDiffPath
  //
  // Define the location of the diff command

  function setDiffPath($path) {
    $this->setPath($this->diff, $path, "diff");
  }

  function getDiffCommand() {
    return $this->diff;
  }

  // setEnscriptPath
  //
  // Define the location of the enscript command

  function setEnscriptPath($path) {
    $this->setPath($this->enscript, $path, "enscript");
  }

  function getEnscriptCommand() {
    return $this->enscript;
  }

  // setSedPath
  //
  // Define the location of the sed command

  function setSedPath($path) {
    $this->setPath($this->sed, $path, "sed");
  }

  function getSedCommand() {
    return $this->sed;
  }

  // setTarPath
  //
  // Define the location of the tar command

  function setTarPath($path) {
    $this->setPath($this->tar, $path, "tar");
  }

  function getTarCommand() {
    return $this->tar;
  }

  // setGzipPath
  //
  // Define the location of the GZip command

  function setGzipPath($path) {
    $this->setPath($this->gzip, $path, "gzip");
  }

  function getGzipCommand() {
    return $this->gzip;
  }

  // setZipPath
  //
  // Define the location of the zip command
  function setZipPath($path) {
    $this->setPath($this->zip, $path, "zip");
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

  function setTemplatePath($path, $myrep = 0) {
    if (empty($myrep)) {
      $lastchar = substr($path, -1, 1);
      if (!($lastchar == DIRECTORY_SEPARATOR || $lastchar == '/' || $lastchar == '\\')) {
        $path .= DIRECTORY_SEPARATOR;
      }

      $this->templatePath = $path;
    } else {
      $repo =& $this->findRepository($myrep);
      $repo->setTemplatePath($path);
    }
  }

  function getTemplatePath() {
    return $this->templatePath;
  }

  // }}}

  // {{{ parentPath
  //
  // Automatically set up the repositories based on a parent path

  function parentPath($path, $group = NULL, $pattern = false, $skipAlreadyAdded = true) {
    $this->_parentPaths[] = new ParentPath($path, $group, $pattern, $skipAlreadyAdded);
  }

  function addExcludedPath($path) {
    $url = 'file:///'.$path;
    $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);
    if ($url{strlen($url) - 1} == '/') {
      $url = substr($url, 0, -1);
    }
    $this->_excluded[] = $url;
  }

  // }}}

  // {{{ Encoding functions

  function setInputEncoding($systemEnc) {
    $this->inputEnc = $systemEnc;

    if (!isset($this->contentEnc)) {
      $this->contentEnc = $systemEnc;
    }
  }

  function getInputEncoding() {
    return $this->inputEnc;
  }

  function setContentEncoding($contentEnc, $myrep = 0) {
    if (empty($myrep)) {
      $this->contentEnc = $contentEnc;
    } else {
      $repo =& $this->findRepository($myrep);
      $repo->setContentEncoding($contentEnc);
    }
  }

  function getContentEncoding() {
    return $this->contentEnc;
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

  function useBugtraqProperties($myrep = 0) {
    if (empty($myrep)) {
      $this->bugtraq = true;
    } else {
      $repo =& $this->findRepository($myrep);
      $repo->useBugtraqProperties();
    }
  }

  function getBugtraq() {
    return $this->bugtraq;
  }

  function useAuthenticationFile($file, $myrep = 0) {
    if (empty($myrep)) {
      if (is_readable($file)) {
        $this->auth = new Authentication($file);
      } else {
        echo "Unable to read authentication file '$file'";
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

  function showLastModInListing() {
    return $this->showLastMod;
  }

  function setShowLastModInListing($show) {
    $this->showLastMod = $show;
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
  // This function sorts the repositories by group name.  The contents of the
  // group are left in there original order, which will either be sorted if the
  // group was added using the parentPath function, or defined for the order in
  // which the repositories were included in the user's config file.
  //
  // Note that as of PHP 4.0.6 the usort command no longer preserves the order
  // of items that are considered equal (in our case, part of the same group).
  // The mergesort function preserves this order.

  function sortByGroup() {
    if (!empty($this->_repositories))
    mergesort($this->_repositories, "cmpGroups");
  }

  // }}}
}
