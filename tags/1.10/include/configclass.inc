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

Class Config
{
   var $svnlook;
   var $diff;
   var $serverIsWindows = false;
   var $cacheResults = false;

   var $_repositories;
   
   function Config()
   {
      $this->svnlook = "svnlook";
      $this->diff = "diff";
   }
   
   function addRepository($name, $path)
   {
      $this->_repositories[] = array("name" => $name, "path" => $path);
   }

   function getRepository($rep)
   {
      return array($this->_repositories[$rep]["name"], $this->_repositories[$rep]["path"]);
   }

   function getRepositories()
   {
      return $this->_repositories;
   }

   // setServerIsWindows
   //
   // The server is running on Windows
   
   function setServerIsWindows()
   {
      $this->serverIsWindows = true;
   }

   // setServerIsWindows
   //
   // The server is running on Windows
   
   function setCachingOn()
   {
      $this->cacheResults = true;
   }

   // setSVNCommandPath
   //
   // Define the location of the svnlook command
   
   function setSVNCommandPath($path)
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
         $this->svnlook = "\"${path}svnlook\"";
      else         
         $this->svnlook = "${path}svnlook";
   }
   
   // setDiffPath
   //
   // Define the location of the diff command
   
   function setDiffPath($path)
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
         $this->diff = "\"${path}diff\"";
      else         
         $this->diff = "${path}diff";
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
   }
}
?>
