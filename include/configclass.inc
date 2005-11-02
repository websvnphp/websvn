<?php

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004 Tim Armes
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
// configclass.inc4
//
// General class for handling configuration options

require_once("include/command.inc");
require_once("include/auth.inc");
require_once("include/version.inc");

// Auxillary function used to sort repositories by name

function cmpReps($a, $b)
{
   return strcasecmp($a->name, $b->name);
}

// A Repository configuration class

Class Repository
{
   var $name;
   var $svnName;
   var $path;
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
      
   function Repository($name, $svnName, $path, $username = "", $password = "")
   {
      $this->name = $name;
      $this->svnName = $svnName;
      $this->path = $path;
      $this->username = $username;
      $this->password = $password;
   }
   
   function svnParams()
   {
      if (!empty($this->username))
         return " --username ".$this->username." --password ".$this->password." ";
      else
         return " ";
   }

   // Local configuration accessors
      
   // RSS Feed
   
   function hideRSS()
   {
      $this->rss = false;
   }

   function showRSS()
   {
      $this->rss = true;
   }

   function getHideRSS()
   {
      global $config;

      if (isset($this->rss))
         return $this->rss;
         
      return $config->rss;
   }
   
   // Download
   
   function allowDownload()
   {
      $this->allowDownload = true;
   }

   function disallowDownload()
   {
      $this->allowDownload = false;
   }
   
   function getAllowDownload()
   {
      global $config;

      if (isset($this->allowDownload))
         return $this->allowDownload;
         
      return $config->allowDownload;
   }

   function setMinDownloadLevel($level)
   {
      $this->minDownloadLevel = $level;
   }

   function getMinDownloadLevel()
   {
      global $config;

      if (isset($this->minDownloadLevel))
         return $this->minDownloadLevel;
         
      return $config->minDownloadLevel;
   }
   
   function addAllowedDownloadException($path)
   {
      if ($path{strlen($path) - 1} != "/")
         $path .= "/";
         
      $this->allowedExceptions[] = $path;
   }
   
   function addDisallowedDownloadException($path)
   {
      if ($path{strlen($path) - 1} != "/")
         $path .= "/";
         
      $this->disallowedExceptions[] = $path;
   }

   function isDownloadAllowed($path)
   {
      global $config;
      
      // Check global download option
      if (!$this->getAllowDownload())
         return false;
      
      // Check with access module
      if (!$this->hasUnrestrictedReadAccess($path))
         return false;
         
      $subs = explode("/", $path);
      $level = count($subs) - 2;
      if ($level >= $this->getMinDownloadLevel())
      {
         // Level OK, search for disallowed exceptions
         
         if ($config->findException($path, $this->disallowedExceptions))
            return false;
            
         if ($config->findException($path, $config->disallowedExceptions))
            return false;
            
         return true;
      }
      else
      {
         // Level not OK, search for disallowed exceptions

         if ($config->findException($path, $this->allowedExceptions))
            return true;
            
         if ($config->findException($path, $config->allowedExceptions))
            return true;
            
         return false;
      }
   }


   // Tab expansion
   
   function expandTabsBy($sp)
   {
      $this->spaces = $sp;
   }
   
   function getExpandTabsBy()
   {
      global $config;

      if (isset($this->spaces))
         return $this->spaces;
         
      return $config->spaces;
   }

   // Mime Type Handing
   
   function ignoreSvnMimeTypes()
   {
      $this->ignoreSvnMimeTypes = true;
   }

   function useSvnMimeTypes()
   {
      $this->ignoreSvnMimeTypes = false;
   }

   function getIgnoreSvnMimeTypes()
   {
      global $config;

      if (isset($this->ignoreSvnMimeTypes))
         return $this->ignoreSvnMimeTypes;
         
      return $config->ignoreSvnMimeTypes;
   }

   function ignoreWebSVNContentTypes()
   {
      $this->ignoreWebSVNContentTypes = true;
   }

   function useWebSVNContentTypes()
   {
      $this->ignoreWebSVNContentTypes = false;
   }

   function getIgnoreWebSVNContentTypes()
   {
      global $config;

      if (isset($this->ignoreWebSVNContentTypes))
         return $this->ignoreWebSVNContentTypes;
         
      return $config->ignoreWebSVNContentTypes;
   }
   
   // Issue Tracking
   
   function useBugtraqProperties()
   {
      $this->bugtraq = true;
   }

   function ignoreBugtraqProperties()
   {
      $this->bugtraq = false;
   }

   function getBugtraq()
   {
      global $config;

      if (isset($this->bugtraq))
         return $this->bugtraq;
         
      return $config->bugtraq;
   }
   
   // Encodings
   
   function setContentEncoding($contentEnc)
   {
      $this->contentEnc = $contentEnc;
   }

   function getContentEncoding()
   {
      global $config;

      if (isset($this->contentEnc))
         return $this->contentEnc;
         
      return $config->contentEnc;
   }
   
   function useAuthenticationFile($file)
   {
      if (is_readable($file))
         $this->auth = new Authentication($file);
      else
      {
         echo "Unable to read authentication file '$file'";
         exit;
      }
   }

   function hasReadAccess($path, $checkSubFolders = false)
   {
      global $config;

      if (isset($this->auth))
         $a = $this->auth;
      else
         $a = $config->auth;
         
      if (!empty($a))
      {
         return $a->hasReadAccess($this->svnName, $path, $checkSubFolders);
      }
      
      // No auth file - free access...
      return true;
   }

   function hasUnrestrictedReadAccess($path)
   {
      global $config;

      if (isset($this->auth))
         $a = $this->auth;
      else
         $a = $config->auth;
         
      if (!empty($a))
      {
         return $a->hasUnrestrictedReadAccess($this->svnName, $path);
      }
      
      // No auth file - free access...
      return true;
   }
}

// The general configuration class

Class Config
{
   // Tool path locations

   var $svnlook = "svnlook";
   var $svn = "svn --non-interactive --config-dir /tmp";
   var $svn_noparams = "svn"; 
   var $diff = "diff";
   var $enscript ="enscript";
   var $sed = "sed";
   var $gzip = "gzip";
   var $tar = "tar";
   
   // Other configuration items
   
   var $treeView = true;
   var $serverIsWindows = false;
   var $cacheResults = false;
   var $multiViews = false;
   var $useEnscript = false;
   var $allowDownload = false;
   var $minDownloadLevel = 0;
   var $allowedExceptions = array();
   var $disallowedExceptions = array();
   var $rss = true;
   var $spaces = 8;
   var $bugtraq = false;
   var $auth = "";
     
   var $templatePath = "./templates/Standard/";

   var $ignoreSvnMimeTypes = false;
   var $ignoreWebSVNContentTypes = false;

   var $subversionMajorVersion = "";
   var $subversionMinorVersion = "";

   // Default character encodings
   var $inputEnc = "";        // Encoding of output returned from command line
   var $contentEnc = "";      // Encoding of repository content
   var $outputEnc = "UTF-8";  // Encoding of web page.  Now forced to UTF-8

   var $quote = "'";

   var $_repositories;
   
   function Config()
   {
   }
   
   function addRepository($name, $url, $username = "", $password = "")
   {
      $url = str_replace(DIRECTORY_SEPARATOR, "/", $url);
      
      if ($url{strlen($url) - 1} == "/")
         $url = substr($url, 0, -1);
         
      $svnName = substr($url, strrpos($url, "/") + 1); 
      $this->_repositories[] = new Repository($name, $svnName, $url, $username, $password);
   }

   function getRepository($rep)
   {
      return $this->_repositories[$rep];
   }

   function getRepositories()
   {
      return $this->_repositories;
   }

   function findRepository($name)
   {
      foreach ($this->_repositories as $index => $rep)
      {
         if (strcasecmp($rep->name, $name) == 0)
            return $rep;
      }
      
      print "ERROR: Unable to find repository '$name'";
      exit;
   }

   // setServerIsWindows
   //
   // The server is running on Windows
   
   function setServerIsWindows()
   {
      $this->serverIsWindows = true;
      
      // Try to set the input encoding intelligently
      
      $cp = 0;
      if ($cp = @shell_exec("CHCP"))
      {
         $cp = trim(substr($cp, strpos($cp, ":") + 1));
         settype($sp, "integer");
      }
      
      // Use the most sensible default value if that failed
      if ($cp == 0) $cp = 850;
         
      // We assume, as a default, that the encoding of the repository contents is
      // in iso-8859-1, to be compatible with compilers and the like.
      $this->setInputEncoding("CP$cp", "iso-8859-1");
      
      // On Windows machines, use double quotes around command line parameters
      
      $this->quote = '"';
   }

   // setCachingOn
   //
   // Set result caching on
   
   function setCachingOn()
   {
      $this->cacheResults = true;
   }

   // useMultiViews
   //
   // Use MultiViews to access the repository
   
   function useMultiViews()
   {
      $this->multiViews = true;
   }

   // useEnscript
   //
   // Use Enscript to colourise listings
   
   function useEnscript()
   {
      $this->useEnscript = true;
   }

   // offerRSS
   //
   // Use Enscript to colourise listings
   
   function hideRSS($myrep = 0)
   {
      if (empty($myrep))
         $this->rss = false;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->hideRSS();
      }
   }

   // allowDownload
   //
   // Allow download of tarballs
   
   function allowDownload($myrep = 0)
   {
      global $vars;
      
      if (empty($myrep))
         $this->allowDownload = true;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->allowDownload();
      }
   }

   function setMinDownloadLevel($level, $myrep = 0)
   {
      if (empty($myrep))
         $this->minDownloadLevel = $level;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->setMinDownloadLevel($level);
      }
   }

   function addAllowedDownloadException($path, $myrep = 0)
   {
      if ($path{strlen($path) - 1} != "/")
         $path .= "/";
         
      if (empty($myrep))
         $this->allowedExceptions[] = $path;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->addAllowedDownloadException($path);
      }
   }
   
   function addDisallowedDownloadException($path, $myrep = 0)
   {
      if ($path{strlen($path) - 1} != "/")
         $path .= "/";
         
      if (empty($myrep))
         $this->disallowedExceptions[] = $path;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->addDisallowedDownloadException($path);
      }
   }
   
   function findException($path, $exceptions)
   {
      foreach ($exceptions As $key => $exc)
      {
         if (strncmp($exc, $path, strlen($exc)) == 0)
            return true;
      }
      
      return false;
   }
   
   // getURL
   //
   // Get the URL to a path name based on the current config
   
   function getURL($rep, $path, $op)
   {  
      $base = $_SERVER["SCRIPT_NAME"];
      
      if ($this->multiViews)
      {
         // Remove the .php
         if (eregi(".php$", $base)) 
         {
            // Remove the .php
            $base = substr($base, 0, -4);
         }      
         
         if ($path && $path{0} != "/") $path = "/".$path;
               
         $url =  $base;
         
         if ($rep != -1)
         {
            $url .= "/".$rep->name.$path;
            
            if ($op != "dir")
               $url .= "?op=$op&amp;";
            else
               $url .= "?";
         }
            
         return $url;
      }
      else
      {
         switch ($op)
         {
            case "dir":
               $fname = "listing.php";
               break;
               
            case "file":
               $fname = "filedetails.php";
               break;

            case "log":
               $fname = "log.php";
               break;

            case "diff":
               $fname = "diff.php";
               break;

            case "blame":
               $fname = "blame.php";
               break;

            case "form":
               $fname = "form.php";
               break;

            case "rss":
               $fname = "rss.php";
               break;

            case "dl":
               $fname = "dl.php";
               break;

            case "comp":
               $fname = "comp.php";
               break;
         }

         if ($rep == -1)
            return $fname."?path=".urlencode($path)."&amp;";
         else
            return $fname."?repname=".urlencode($rep->name)."&amp;path=".urlencode($path)."&amp;";
      }
   }

   // setPath
   //
   // Set the location of the given path
   
   function setPath(&$var, $path, $name, $params = "")
   {
      $lastchar = $path{strlen($path) - 1};
      $isDir = ($lastchar == DIRECTORY_SEPARATOR ||
                $lastchar == "/" ||
                $lastchar == "\\");
     
      if (!$isDir)
      {
         $path .= DIRECTORY_SEPARATOR;
      }

      // On a windows machine we need to put spaces around the entire command
      // to allow for spaces in the path
      if ($this->serverIsWindows)
         $var = "\"$path$name\"";
      else         
         $var = "$path$name";
         
      $var .= " ".$params;
   }

   // setSVNCommandPath
   //
   // Define the location of the svn and svnlook commands
   
   function setSVNCommandPath($path)
   {
      $this->setPath($this->svn, $path, "svn", "--non-interactive --config-dir /tmp");
      $this->setPath($this->svn_noparams, $path, "svn");
      $this->setPath($this->svnlook, $path, "svnlook");
   }
   
   // setDiffPath
   //
   // Define the location of the diff command
   
   function setDiffPath($path)
   {
      $this->setPath($this->diff, $path, "diff");
   }

   // setEnscriptPath
   //
   // Define the location of the enscript command
   
   function setEnscriptPath($path)
   {
      $this->setPath($this->enscript, $path, "enscript");
   }

   // setSedPath
   //
   // Define the location of the sed command
   
   function setSedPath($path)
   {
      $this->setPath($this->sed, $path, "sed");
   }
   
   // setTarPath
   //
   // Define the location of the tar command
   
   function setTarPath($path)
   {
      $this->setPath($this->tar, $path, "tar");
   }
   
   // setGzipPath
   //
   // Define the location of the GZip command
   
   function setGzipPath($path)
   {
      $this->setPath($this->gzip, $path, "gzip");
   }
   
   function setTemplatePath($path)
   {
      $lastchar = $path{strlen($path) - 1};
      $isDir = ($lastchar == DIRECTORY_SEPARATOR ||
                $lastchar == "/" ||
                $lastchar == "\\");
     
      if (!$isDir)
      {
         $path .= DIRECTORY_SEPARATOR;
      }

      // On a windows machine we need to put spaces around the entire command
      // to allow for spaces in the path
      $this->templatePath = $path;
   }

   // parentPath
   //
   // Automatically set up the repositories based on a parent path
   
   function parentPath($path)
   {
      if ($handle = opendir($path))
      {
         // For each file...
         while (false !== ($file = readdir($handle)))
         { 
            // That's also a non hidden directory
            if (is_dir($path.DIRECTORY_SEPARATOR.$file) && $file{0} != ".")
            {
               // And that contains a db directory (in an attempt to not include
               // non svn repositories.
               
               if (is_dir($path.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR."db"))
               {
                  // We add the repository to the list
                  $this->addRepository($file, "file:///".$path.DIRECTORY_SEPARATOR.$file);
               }
            }
         }
         closedir($handle); 
      }

      // Sort the repositories into alphabetical order
      
      if (!empty($this->_repositories))
         usort($this->_repositories, "cmpReps");
   }
   
   // Encoding functions
   
   function setInputEncoding($systemEnc)
   {
      $this->inputEnc = $systemEnc;
      
      if (!isset($this->contentEnc))
         $this->contentEnc = $systemEnc;
   }
   
   function setContentEncoding($contentEnc, $myrep = 0)
   {      
      if (empty($myrep))
         $this->contentEnc = $contentEnc;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->setContentEncoding($contentEnc);
      }
   }
   
   // Tab expansion functions
   
   function expandTabsBy($sp, $myrep = 0)
   {
      if (empty($myrep))
         $this->spaces = $sp;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->expandTabsBy($sp);
      }
   }
   
   // Misc settings
   
   function ignoreSvnMimeTypes()
   {
      $this->ignoreSvnMimeTypes = true;
   }

   function ignoreWebSVNContentTypes()
   {
      $this->ignoreWebSVNContentTypes = true;
   }

   function useBugtraqProperties($myrep = 0)
   {
      if (empty($myrep))
         $this->bugtraq = true;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->useBugtraqProperties();
      }
   }
   
   function useAuthenticationFile($file, $myrep = 0)
   {
      if (empty($myrep))
      {
         if (is_readable($file))
            $this->auth = new Authentication($file);
         else
         {
            echo "Unable to read authentication file '$file'";
            exit;
         }
      }
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->useAuthenticationFile($file);
      }
   }
   
   function useTreeView()
   {
      $this->treeView = true;
   }
   
   function useFlatView()
   {
      $this->treeView = false;
   }

   // setSubversionMajorVersion
   //
   // Set subversion major version
   
   function setSubversionMajorVersion($subversionMajorVersion)
   {
      $this->subversionMajorVersion = $subversionMajorVersion;
   }

   // setSubversionMinorVersion
   //
   // Set subversion minor version
   
   function setSubversionMinorVersion($subversionMinorVersion)
   {
      $this->subversionMinorVersion = $subversionMinorVersion;
   }
}
?>
