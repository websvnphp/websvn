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

function cmpReps($a, $b)
{
   // First, sort by group
   $g = strcasecmp($a->group, $b->group);
   if ($g)
      return $g;
   
   // Same group? Sort by name
   return strcasecmp($a->name, $b->name);
}

// }}}

// {{{ cmpGroups($a, $b)

function cmpGroups($a, $b)
{
   $g = strcasecmp($a->group, $b->group);
   if ($g)
      return $g;
   
   return 0;
}

// }}}

// {{{ mergesort(&$array, [$cmp_function])

function mergesort(&$array, $cmp_function = 'strcmp')
{
   // Arrays of size < 2 require no action
   
   if (count($array) < 2)
      return;
   
   // Split the array in half
   $halfway = count($array) / 2;
   $array1 = array_slice($array, 0, $halfway);
   $array2 = array_slice($array, $halfway);
   
   // Recurse to sort the two halves
   mergesort($array1, $cmp_function);
   mergesort($array2, $cmp_function);
   
   // If all of $array1 is <= all of $array2, just append them.
   if (call_user_func($cmp_function, end($array1), $array2[0]) < 1)
   {
      $array = array_merge($array1, $array2);
      return;
   }
   
   // Merge the two sorted arrays into a single sorted array
   $array = array();
   $ptr1 = $ptr2 = 0;
   while ($ptr1 < count($array1) && $ptr2 < count($array2))
   {
      if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1)
      {
         $array[] = $array1[$ptr1++];
      }
      else
      {
         $array[] = $array2[$ptr2++];
      }
   }
   
   // Merge the remainder
   while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
   while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
   
   return;
}

// }}}

// A Repository configuration class

Class Repository
{
   // {{{ Properties

   var $name;
   var $svnName;
   var $path;
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

   function Repository($name, $svnName, $path, $group = NULL, $username = NULL, $password = NULL)
   {
      $this->name = $name;
      $this->svnName = $svnName;
      $this->path = $path;
      $this->group = $group;
      $this->username = $username;
      $this->password = $password;
   }

   // }}}

   // {{{ getDisplayName()

   function getDisplayName()
   {
      if(!empty($this->group))
         return $this->group.".".$this->name;
      else
         return $this->name;
   }

   // }}}

   // {{{ svnParams

   function svnParams()
   {
      if (!empty($this->username))
         return " --username ".$this->username." --password ".$this->password." ";
      else
         return " ";
   }

   // }}}

   // Local configuration accessors
      
   // {{{ RSS Feed
   
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
         
      return $config->getHideRSS(); 
   }

   // }}}
   
   // {{{ Download
   
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
         
      return $config->getAllowDownload();
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
         
      return $config->getMinDownloadLevel();
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

   // }}}

   // {{{ Templates

   function setTemplatePath($path)
   {
      $lastchar = substr($path, -1, 1);
      if (!($lastchar == DIRECTORY_SEPARATOR ||
            $lastchar == '/' ||
            $lastchar == '\\'))
         $path .= DIRECTORY_SEPARATOR;

      $this->templatePath = $path;
   }

   function getTemplatePath()
   {
      global $config;
      if (!empty($this->templatePath))
         return $this->templatePath;
      else
         return $config->getTemplatePath();
   }

   // }}}

   // {{{ Tab expansion
   
   function expandTabsBy($sp)
   {
      $this->spaces = $sp;
   }
   
   function getExpandTabsBy()
   {
      global $config;

      if (isset($this->spaces))
         return $this->spaces;
         
      return $config->getExpandTabsBy();
   }

   // }}}

   // {{{ MIME-Type Handing
   
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
         
      return $config->getIgnoreSvnMimeTypes();
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
         
      return $config->getIgnoreWebSVNContentTypes();
   }

   // }}}
   
   // {{{ Issue Tracking
   
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
         
      return $config->getBugtraq();
   }

   // }}}
   
   // {{{ Encodings
   
   function setContentEncoding($contentEnc)
   {
      $this->contentEnc = $contentEnc;
   }

   function getContentEncoding()
   {
      global $config;

      if (isset($this->contentEnc))
         return $this->contentEnc;
         
      return $config->getContentEncoding();
   }

   // }}}

   // {{{ Authentication
   
   function useAuthenticationFile($file)
   {
      if (is_readable($file))
         $this->auth =& new Authentication($file);
      else
         die('Unable to read authentication file "'.$file.'"');
   }

   function hasReadAccess($path, $checkSubFolders = false)
   {
      global $config;

      $a = null;
      if (isset($this->auth))
         $a =& $this->auth;
      else
         $a =& $config->getAuth();
         
      if (!empty($a))
         return $a->hasReadAccess($this->svnName, $path, $checkSubFolders);
      
      // No auth file - free access...
      return true;
   }

   function hasUnrestrictedReadAccess($path)
   {
      global $config;

      $a = null;
      if (isset($this->auth))
         $a =& $this->auth;
      else
         $a =& $config->getAuth();
         
      if (!empty($a))
         return $a->hasUnrestrictedReadAccess($this->svnName, $path);
      
      // No auth file - free access...
      return true;
   }

   // }}}

}

// The general configuration class

Class Config
{
   // {{{ Properties

   // Tool path locations

   var $svnlook = "svnlook";
   var $svn = "svn --non-interactive --config-dir /tmp";
   var $svn_noparams = "svn --config-dir /tmp"; 
   var $diff = "diff";
   var $enscript ="enscript -q";
   var $sed = "sed";
   var $gzip = "gzip";
   var $tar = "tar";
   
   // Other configuration items
   
   var $treeView = true;
   var $flatIndex = true;
   var $openTree = false;
   var $serverIsWindows = false;
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
   
   var $defaultLanguage = 'en';

   var $quote = "'";
   var $pathSeparator = ":";

   var $_repositories = array();

   // }}}

   // {{{ __construct()

   function Config()
   {
   }

   // }}}

   // {{{ Repository configuration
   
   function addRepository($name, $url, $group = NULL, $username = NULL, $password = NULL)
   {
      $url = str_replace(DIRECTORY_SEPARATOR, "/", $url);
      
      if ($url{strlen($url) - 1} == "/")
         $url = substr($url, 0, -1);
         
      $svnName = substr($url, strrpos($url, "/") + 1); 
      $this->_repositories[] = new Repository($name, $svnName, $url, $group, $username, $password);
   }

   function getRepositories()
   {
      return $this->_repositories;
   }

   function &findRepository($name)
   {
      foreach ($this->_repositories as $index => $rep)
      {
         if (strcmp($rep->getDisplayName(), $name) == 0)
         {
            $repref =& $this->_repositories[$index];
            return $repref;
         }
      }
      
      print "ERROR: Unable to find repository '".htmlentities($name, ENT_QUOTES, 'UTF-8')."'";
      exit;
   }

   // }}}

   // {{{ setServerIsWindows
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
   
   function useMultiViews()
   {
      $this->multiViews = true;
   }

   function getUseMultiViews()
   {
      return $this->multiViews;
   }

   // }}}

   // {{{ Enscript

   // useEnscript
   //
   // Use Enscript to colourise listings
   
   function useEnscript()
   {
      $this->useEnscript = true;
   }

   function getUseEnscript()
   {
      return $this->useEnscript;
   }

   // }}}

   // {{{ RSS

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

   function getHideRSS()
   {
      return $this->rss;
   }

   // }}}

   // {{{ Downloads

   // allowDownload
   //
   // Allow download of tarballs
   
   function allowDownload($myrep = 0)
   {
      if (empty($myrep))
         $this->allowDownload = true;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->allowDownload();
      }
   }

   function disallowDownload($myrep = 0)
   {
      if (empty($myrep))
         $this->allowDownload = false;
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->disallowDownload();
      }
   }

   function getAllowDownload()
   {
      return $this->allowDownload;
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

   function getMinDownloadLevel()
   {
      return $this->minDownloadLevel;
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
   
   // }}}

   // {{{ getURL
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
         
         if ($op == 'index')
         {
         	$url .= '/?';
         }
         else if (is_object($rep))
         {
            $url .= "/".$rep->getDisplayName().str_replace('%2F', '/', rawurlencode($path));
            
            if ($op != "dir" && $op != "file")
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
            case "index":
               $fname = ".";
               break;
               
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

         if ($op == 'index')
            return $fname.'?';
         else if ($rep === -1)
            return $fname."?path=".urlencode($path)."&amp;";
         else
            return $fname."?repname=".urlencode($rep->getDisplayName())."&amp;path=".urlencode($path)."&amp;";
      }
   }

   // }}}

   // {{{ Paths and Commands

   // setPath
   //
   // Set the location of the given path
   
   function setPath(&$var, $path, $name, $params = '')
   {
      if ($path == '')
      {
         // Search in system search path. No check for existence possible
         $var = $name;
      }
      else
      {
         $lastchar = substr($path, -1, 1);
         $isDir = ($lastchar == DIRECTORY_SEPARATOR ||
                   $lastchar == '/' ||
                   $lastchar == '\\');
         
         if (!$isDir) $path .= DIRECTORY_SEPARATOR;
         
         if (($this->serverIsWindows  && !file_exists($path.$name.'.exe')) ||
             (!$this->serverIsWindows && !file_exists($path.$name)))
         {
            echo "Unable to find '$name' tool at location '$path$name'";
            exit;
         }
         
         // On a windows machine we need to put spaces around the entire command
         // to allow for spaces in the path
         if ($this->serverIsWindows)
            $var = '"'.$path.$name.'"';
         else         
            $var = $path.$name;
      }
      
      // Append parameters
      if ($params != '') $var .= ' '.$params;
   }

   // setSVNCommandPath
   //
   // Define the location of the svn and svnlook commands
   
   function setSVNCommandPath($path)
   {
      $this->setPath($this->svn, $path, "svn", "--non-interactive --config-dir /tmp");
      $this->setPath($this->svn_noparams, $path, "svn", " --config-dir /tmp");
      $this->setPath($this->svnlook, $path, "svnlook");
   }
   
   function getSvnCommand()
   {
      return $this->svn;
   }

   function getCleanSvnCommand()
   {
      return $this->svn_noparams;
   }

   function getSvnlookCommand()
   {
      return $this->svnlook;
   }
   
   // setDiffPath
   //
   // Define the location of the diff command
   
   function setDiffPath($path)
   {
      $this->setPath($this->diff, $path, "diff");
   }

   function getDiffCommand()
   {
      return $this->diff;
   }

   // setEnscriptPath
   //
   // Define the location of the enscript command
   
   function setEnscriptPath($path)
   {
      $this->setPath($this->enscript, $path, "enscript");
   }

   function getEnscriptCommand()
   {
      return $this->enscript;
   }

   // setSedPath
   //
   // Define the location of the sed command
   
   function setSedPath($path)
   {
      $this->setPath($this->sed, $path, "sed");
   }
   
   function getSedCommand()
   {
      return $this->sed;
   }

   // setTarPath
   //
   // Define the location of the tar command
   
   function setTarPath($path)
   {
      $this->setPath($this->tar, $path, "tar");
   }
   
   function getTarCommand()
   {
      return $this->tar;
   }

   // setGzipPath
   //
   // Define the location of the GZip command
   
   function setGzipPath($path)
   {
      $this->setPath($this->gzip, $path, "gzip");
   }
   
   function getGzipCommand()
   {
      return $this->gzip;
   }

   // Templates
        
   function setTemplatePath($path, $myrep = 0)
   {
      if (empty($myrep))
      {
          $lastchar = substr($path, -1, 1);
          if (!($lastchar == DIRECTORY_SEPARATOR ||
                $lastchar == '/' ||
                $lastchar == '\\'))
         $path .= DIRECTORY_SEPARATOR;

         $this->templatePath = $path;
      }
      else
      {
         $repo =& $this->findRepository($myrep);
         $repo->setTemplatePath($path);
      }
   }

   function getTemplatePath()
   {
      return $this->templatePath;
   }

   // }}}

   // {{{ parentPath
   //
   // Automatically set up the repositories based on a parent path
   
   function parentPath($path, $group = NULL, $pattern = false, $skipAlreadyAdded = true)
   {
      if ($handle = @opendir($path))
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
                  // And matches the pattern if specified
                  if ($pattern === false || preg_match($pattern, $file)) {
                     $name = 'file:///'.$path.DIRECTORY_SEPARATOR.$file;
                     $add = true;
                     // And has not already been added if specified
                     if ($skipAlreadyAdded) {
                        $url = str_replace(DIRECTORY_SEPARATOR, '/', $name);
                        if ($url{strlen($url) - 1} == '/') $url = substr($url, 0, -1);
                        $url = substr($url, strrpos($url, '/') + 1);
                        foreach ($this->getRepositories() as $rep) {
                           if ($rep->svnName == $url) {
                              $add = false;
                              break;
                           }
                        }
                     }
                     if ($add) {
                        // We add the repository to the list
                        $this->addRepository($file, $name, $group);
                     }
                  }
               }
            }
         }
         closedir($handle); 
      }

      // Sort the repositories into alphabetical order
      
      if (!empty($this->_repositories))
         usort($this->_repositories, "cmpReps");
   }

   // }}}
   
   // {{{ Encoding functions
   
   function setInputEncoding($systemEnc)
   {
      $this->inputEnc = $systemEnc;
      
      if (!isset($this->contentEnc))
         $this->contentEnc = $systemEnc;
   }
   
   function getInputEncoding()
   {
      return $this->inputEnc;
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
   
   function getContentEncoding()
   {
      return $this->contentEnc;
   }

   // }}}
   
   function setDefaultLanguage($language)
   {
      $this->defaultLanguage = $language;
   }
   
   function getDefaultLanguage()
   {
      return $this->defaultLanguage;
   }
   
   // {{{ Tab expansion functions
   
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
   
   function getExpandTabsBy()
   {
      return $this->spaces;
   }

   // }}}

   // {{{ Misc settings
   
   function ignoreSvnMimeTypes()
   {
      $this->ignoreSvnMimeTypes = true;
   }

   function getIgnoreSvnMimeTypes()
   {
      return $this->ignoreSvnMimeTypes;
   }

   function ignoreWebSVNContentTypes()
   {
      $this->ignoreWebSVNContentTypes = true;
   }

   function getIgnoreWebSVNContentTypes()
   {
      return $this->ignoreWebSVNContentTypes;
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
   
   function getBugtraq()
   {
      return $this->bugtraq;
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
   
   function &getAuth()
   {
       return $this->auth;
   }

   function useTreeView()
   {
      $this->treeView = true;
   }
   
   function getUseTreeView()
   {
      return $this->treeView;
   }

   function useFlatView()
   {
      $this->treeView = false;
   }

   function useTreeIndex($open)
   {
      $this->flatIndex = false;
      $this->openTree = $open;
   }

   function getUseFlatIndex()
   {
      return $this->flatIndex;
   }

   function getOpenTree()
   {
      return $this->openTree;
   }

   // setSubversionMajorVersion
   //
   // Set subversion major version
   
   function setSubversionMajorVersion($subversionMajorVersion)
   {
      $this->subversionMajorVersion = $subversionMajorVersion;
   }

   function getSubversionMajorVersion()
   {
      return $this->subversionMajorVersion;
   }

   // setSubversionMinorVersion
   //
   // Set subversion minor version
   
   function setSubversionMinorVersion($subversionMinorVersion)
   {
      $this->subversionMinorVersion = $subversionMinorVersion;
   }
   
   function getSubversionMinorVersion()
   {
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
   
   function sortByGroup()
   {
      if (!empty($this->_repositories))
      mergesort($this->_repositories, "cmpGroups");
   }

   // }}}
}
?>
