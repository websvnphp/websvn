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
// config.inc
//
// Configuration parameters

Class Config
{
   var $adminCommandPath;
   var $diffCommandPath;
   var $serverIsWindows = false;

   var $_repositories;
   
   function Config()
   {
      $this->adminCommandPath = "";
      $this->diffCommandPath = "";
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
}

$config = new Config;

// For each repository, copy the line below, uncomment it and replace the name and path
//
// $config->addRepository("NameToDisplay", "Path/to/repository (e.g. c:\svn\proj)");

// Configure these line if your commands aren't on your path.  You need a directory separator at the end
//
// $config->adminCommandPath = "Path/to/svnlook/";
// $config->diffCommandPath = "Path/to/diff/command/";

// Uncomment the next line if your running a windows server
// $config->serverIsWindows = true;

// Un comment this if you don't have the right to use it.  Be warned that you may need it however!
set_time_limit(0);

// Uncomment ONLY the language file which you want
include("languages/english.inc");

?>
