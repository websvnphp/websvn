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
// form.php
//
// Handling of WebSVN forms

require_once("include/setup.inc");
require_once("include/utils.inc");

// Generic redirect handling

function redirect($loc)
{
   $url= getFullURL($loc);
   
   header("Location: $url");
   echo "<html><script language=\"JavaScript\" type=\"text/JavaScript\">window.location = \"$url\"</script></html>";
}

// Handle project selection

if (@$_REQUEST["selectproj"])
{   
   $basedir = dirname($_SERVER["PHP_SELF"]);
   if ($basedir != "" && $basedir != DIRECTORY_SEPARATOR && $basedir != "\\" && $basedir != "/" )
      $basedir .= "/";
   else
      $basedir = "/";
      
   $url = $config->getURL($rep, "/", "dir");
   $url = html_entity_decode($url);
   
   if ($config->multiViews)
      redirect($url."sc=$showchanged");
   else
      redirect($basedir.$url."sc=$showchanged");   
}


?>