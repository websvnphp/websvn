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
// blame.php
//
// Show the blame information of a file.
//

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");

$vars["action"] = $lang["BLAME"];

$svnrep = new SVNRepository($rep->path);

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);
$youngest = $history[0]["rev"];

if (empty($rev))
   $rev = $youngest;

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

// Find the parent path (or the whole path if it's already a directory)
$pos = strrpos($ppath, "/");
$parent = substr($ppath, 0, $pos + 1);

$vars["repname"] = $rep->name;
$vars["rev"] = $rev;
$vars["path"] = $ppath;

createDirLinks($rep, $ppath, $rev, $showchanged);

$listing = array();

// Get the contents of the file
$tfname = tempnam("temp", "");
$svnrep->getFileContents($path, $tfname, $rev);

$filecache = array();

if ($file = fopen($tfname, "r"))      
{
   // Get the blame info
   $tbname = tempnam("temp", "");
   $svnrep->getBlameDetails($path, $tbname, $rev); 
   
   $ent = true;
   if ((strrchr($path, ".") == '.php') || ($config->useEnscript))
      $ent = false;

   if ($blame = fopen($tbname, "r"))      
   {
      // Create an array of version/author/line
      
      $index = 0;
      
      while (!feof($blame) && !feof($file))
      {
         $blameline = fgets($blame);
         
         if ($blameline != "")
         {
            list($revision, $author) = sscanf($blameline, "%d %s");
            
            $listing[$index]["lineno"] = $index + 1;
            
            $url = $config->getURL($rep, $parent, "dir");
            $listing[$index]["revision"] = "<a href=\"${url}rev=$revision&amp;sc=1\">$revision</a>";

            $listing[$index]["author"] = $author;
            
            if ($ent)
               $line = replaceEntities(rtrim(fgets($file)));
            else
               $line = rtrim(fgets($file));

            $listing[$index]["line"] = hardspace($line);
            
            if (trim($listing[$index]["line"]) == "")
               $listing[$index]["line"] = "&nbsp;";
               
            $index++;
         }
      }
      
      fclose($blame);
   }
   
   fclose($file);
}

unlink($tfname);  
unlink($tbname);  

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."blame.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);
   
?>