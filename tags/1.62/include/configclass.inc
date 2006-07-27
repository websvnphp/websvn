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
// configclass.inc
//
// General class for handling configuration options

require("include/command.inc");
require("include/version.inc");

// Auxillary function used to sort repositories by name

function cmpReps($a, $b)
{
   return strcasecmp($a->name, $b->name);
}

// A Repository configuration class

Class Repository
{
   var $name;
   var $path;
   
   // Local configuration options must start off unset
   
   var $allowDownload;
   var $rss;
   var $spaces;
   var $ignoreSvnMimeTypes;
   var $ignoreWebSVNContentTypes;
   var $bugtraq;
   
   function Repository($name, $path)
   {
      $this->name = $name;
      $this->path = $path;
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
}

// The general configuration class

Class Config
{
   // Tool path locations

   var $svnlook = "svnlook";
   var $svn = "svn --non-interactive";
   var $diff = "diff";
   var $enscript ="enscript";
   var $sed = "sed";
   var $gzip = "gzip";
   var $tar = "tar";
   
   // Other configuration items
   
   var $serverIsWindows = false;
   var $cacheResults = false;
   var $multiViews = false;
   var $useEnscript = false;
   var $allowDownload = false;
   var $rss = true;
   var $spaces = 8;
   var $bugtraq = false;
  
   var $templatePath = "./templates/Standard/";

   var $ignoreSvnMimeTypes = false;
   var $ignoreWebSVNContentTypes = false;

   // Default character encodings
   var $inputEnc = "";
   var $outputEnc;

   var $quote = "'";

   var $_repositories;
   
   function Config()
   {
      $this->setOutputEncoding("ISO-8859-1");
   }
   
   function addRepository($name, $path)
   {
      if (($path{strlen($path) - 1} == DIRECTORY_SEPARATOR) ||
          ($path{strlen($path) - 1} == "/"))
         $path = substr($path, 0, -1);
         
      $this->_repositories[] = new Repository($name, $path);
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
      return null;
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
         
      $this->setInputEncoding("CP$cp");
      
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
   
   function hideRSS()
   {
      $this->rss = false;
   }

   // allowDownload
   //
   // Allow download of tarballs
   
   function allowDownload()
   {
      global $vars;
      
      $this->allowDownload = true;
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
         $base = substr($base, 0, -4);
         
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
            return $fname."?repname=".$rep->name."&amp;path=".urlencode($path)."&amp;";
      }
   }

   // setPath
   //
   // Set the location of the given path
   
   function setPath(&$var, $path, $name)
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
   }

   // setSVNCommandPath
   //
   // Define the location of the svn and svnlook commands
   
   function setSVNCommandPath($path)
   {
      $this->setPath($this->svn, $path, "svn");
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
                  $this->addRepository($file, $path.DIRECTORY_SEPARATOR.$file);
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
   
   function setInputEncoding($enc)
   {
      $this->inputEnc = $enc;
   }

   function setOutputEncoding($enc)
   {
      global $vars;
      
      if ($enc != "")
      {
         $this->outputEnc = $enc;
         $vars["charset"] = $enc;
      }
   }
   
   // Tab expansion functions
   
   function expandTabsBy($sp)
   {
      $this->spaces = $sp;
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

   function useBugtraqProperties()
   {
      $this->bugtraq = true;
   }
}
?>
