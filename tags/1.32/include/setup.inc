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
// setup.inc
//
// Global setup

if (!@$CONFIG)
{
   $CONFIG = true;
   
   // --- DON'T CHANGE THIS PART ---
   
   // Include the configuration class
   require("include/configclass.inc");
   
   // Define the language array
   $lang = array();
   
   // Include a default language file.
   require("languages/english.inc");
   
   // Set up svnlocwebsvn
   if (empty($locwebsvnhttp))
      $locwebsvnhttp = ".";
   
   if (empty($locwebsvnreal))
      $locwebsvnreal = ".";
   
   $vars["locwebsvnhttp"] = $locwebsvnhttp;
   
   // Create the config
   $config = new Config;
   
   require("config.inc");
}

?>
