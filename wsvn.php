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

// --- CONFIGURE THESE VARIABLES ---

// Location of websvn directory via HTTP
//
// e.g.  For http://servername/websvn use /websvn
//
// Note that wsvn.php need not be in the /websvn directory (and normally isn't).
$locwebsvnhttp = "/websvn";  

// Physical location of websvn directory
$locwebsvnreal = "d:/websvn";

chdir($locwebsvnreal);

// --- DON'T CHANGE BELOW HERE ---

ini_set("include_path", $locwebsvnreal);

require_once("include/setup.inc");
require_once("include/svnlook.inc");

if (!isset($_REQUEST["sc"]))
   $_REQUEST["sc"] = 1;

if ($config->multiViews)
{
   // If this is a form handling request, deal with it
   if (@$_REQUEST["op"] == "form")
   {
      include("$locwebsvnreal/form.php");
      exit;
   }

   $path = @$_SERVER["PATH_INFO"];

   // Remove initial slash
   $path = substr($path, 1);
   if (empty($path))
   {
      include("$locwebsvnreal/index.php");
      exit;
   }
   
   // Get the repository name
   $pos = strpos($path, "/");

   if ($pos === false)
   {
      $name = substr($path, 0);
      $path = "/";
   }
   else
   {
      $name = substr($path, 0, $pos);
      $path = substr($path, $pos);
   }
      
   $rep = $config->findRepository($name);
   createProjectSelectionForm();
   $vars["allowdownload"] = $rep->getAllowDownload();

   // find the operation type
   $op = @$_REQUEST["op"];
   switch ($op)
   {
      case "dir":
         $file = "listing.php";
         break;
         
      case "file":
         $file = "filedetails.php";
         break;

      case "log":
         $file = "log.php";
         break;

      case "diff":
         $file = "diff.php";
         break;

      case "blame":
         $file = "blame.php";
         break;

      case "rss":
         $file = "rss.php";
         break;
      
      case "dl":
         $file = "dl.php";
         break;

      case "comp":
         $file = "comp.php";
         break;

      default:
         $file = "listing.php";
   }
   
   // Now include the file that handles it
   include("$locwebsvnreal/$file");
}
else
{
   print "<p>MultiViews must be configured in config.inc in order to use this file";
   exit;
}
