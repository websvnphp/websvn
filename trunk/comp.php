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
// comp.php
//
// Compare two paths using "svn diff"
//

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);

// Retrieve the request information
$path1 = @$_REQUEST["compare"][0];
$path2 = @$_REQUEST["compare"][1];

// Sanity check
if (empty($path1) || empty($path2))
   exit;

list($path1, $rev1) = explode("@", $path1);
list($path2, $rev2) = explode("@", $path2);

// Choose a sensible comparison order unless told not to
if (!@$_REQUEST["manualorder"])
{
   if ($rev1 > $rev2)
   {
      $temppath = $path1;
      $temprev = $rev1;
      
      $path1 = $path2;
      $rev1 = $rev2;
      
      $path2 = $temppath;
      $rev2 = $temprev;
   }
}

$url = $config->getURL($rep, "", "comp");
$vars["revlink"] = "<a href=\"${url}compare%5B%5D=".urlencode($path2)."@$rev2&amp;compare%5B%5D=".urlencode($path1)."@$rev1&manualorder=1\">${lang["REVCOMP"]}</a>";


if ($rev1 == 0) $rev1 = "HEAD";
if ($rev2 == 0) $rev2 = "HEAD";

$vars["repname"] = $repname;
$vars["path1"] = $path1;
$vars["path2"] = $path2;

$vars["rev1"] = $rev1;
$vars["rev2"] = $rev2;

$listing = array();

// Generate the diff listing
$path1 = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $svnrep->repPath.$path1));
$path2 = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $svnrep->repPath.$path2));

$debug = false;

$cmd = quoteCommand($config->svn." diff -r$rev1:$rev2 ".quote("file:///".$path1)." ".quote("file:///".$path2), false);
if ($debug) echo $cmd;

function clearVars()
{
   global $listing, $index;
   
   $listing[$index]["newpath"] = null;
   $listing[$index]["endpath"] = null;
   $listing[$index]["info"] = null;
   $listing[$index]["diffclass"] = null;
   $listing[$index]["difflines"] = null;
   $listing[$index]["enddifflines"] = null;
   $listing[$index]["properties"] = null;
}	   

if ($diff = popen($cmd, "r"))
{
   $index = 0;
   $indiff = false;
   $indiffproper = false;
   $getLine = true;
   $node = null;
      
	while (!feof($diff))
	{
	   if ($getLine)
	      $line = fgets($diff);
      
      clearVars();	   
	   $getLine = true;
      if ($debug) print "$line<br>" ;	         
	   if ($indiff)
	   {
   	   // If we're in a diff proper, just set up the line
   	   if ($indiffproper)
   	   {
      	   if ($line[0] == " " || $line[0] == "+" || $line[0] == "-")
      	   {
      	      switch ($line[0])
      	      {
      	         case " ":
      	            $listing[$index]["diffclass"] = "diff";
      	            $subline = trim(substr($line, 1));
      	            if (empty($subline)) $subline = "&nbsp;";
      	            $listing[$index++]["line"] = $subline;
                     if ($debug) print "Including as diff: $subline<br>";
      	            break;
      	   
      	         case "+":
      	            $listing[$index]["diffclass"] = "diffadded";
      	            $subline = trim(substr($line, 1));
      	            if (empty($subline)) $subline = "&nbsp;";
      	            $listing[$index++]["line"] = $subline;
                     if ($debug) print "Including as added: $subline<br>";
      	            break;
      
      	         case "-":
      	            $listing[$index]["diffclass"] = "diffdeleted";
      	            $subline = trim(substr($line, 1));
      	            if (empty($subline)) $subline = "&nbsp;";
      	            $listing[$index++]["line"] = $subline;
                     if ($debug) print "Including as removed: $subline<br>";
      	            break;
      	      }
      	      
      	      continue;
      	   }
      	   else
      	   {
      	      $indiffproper = false;
      	      $listing[$index++]["enddifflines"] = true;
      	      $getLine = false;
               if ($debug) print "Ending lines<br>";
      	      continue;
      	   }
   	   }
   	   
   	   // Check for the start of a new diff area
   	   if (!strncmp($line, "@@", 2))
         {
            $pos = strpos($line, "+");
            $posline = substr($line, $pos); 
      	   sscanf($posline, "+%d,%d", $sline, $eline);
                     if ($debug) print "sline = '$sline', eline = '$eline'<br>";      	   
      	   // Check that this isn't a file deletion
      	   if ($sline == 0 && $eline == 0)
      	   {
      	      $line = fgets($diff);
               if ($debug) print "Ignoring: $line<br>" ;	         
      	      while ($line[0] == " " || $line[0] == "+" || $line[0] == "-")
               {
      	         $line = fgets($diff);
                  if ($debug) print "Ignoring: $line<br>" ;	         
               }      	
                        
      	      $getLine = false;
               if ($debug) print "Unignoring previous - marking as deleted<b>";
               $listing[$index++]["info"] = "File Deleted";
      	   }
      	   else
      	   {
      	      $listing[$index++]["difflines"] = $line;
      	      $indiffproper = true;
      	   }
      	   
      	   continue;
      	}
      	else
      	{
      	   $indiff = false;
            if ($debug) print "Ending diff";
      	}
      }
      
	   // Check for a new node entry
      if (strncmp(trim($line), "Index: ", 7) == 0)
      {
         // End the current node
         if ($node)
         {
            $listing[$index++]["endpath"] = true;
            clearVars();
         }
            
         $node = trim($line);
         $node = substr($node, 7);
   
         $listing[$index]["newpath"] = $node;
         if ($debug) echo "Creating node $node<br>";
         
         // Skip past the line of ='s
         $line = fgets($diff);
         if ($debug) print "Skipping: $line<br>" ;	         
        
         // Check for a file addition
         $line = fgets($diff);
         if ($debug) print "Examining: $line<br>" ;	         
         if (strpos($line, "(revision 0)"))
            $listing[$index]["info"] = "New file";
         
         if (strncmp(trim($line), "Cannot display:", 15) == 0)
         {
            $index++;
            clearVars();
            $listing[$index++]["info"] = $line;
            continue;
         }
         
         // Skip second file info
         $line = fgets($diff);
         if ($debug) print "Skipping: $line<br>" ;	         
         
         $indiff = true;
         $index++;
         
         continue;
      }
   
      if (strncmp(trim($line), "Property changes on: ", 21) == 0)
      {
         $propnode = trim($line);
         $propnode = substr($propnode, 21);
         
         if ($debug) print "Properties on $propnode (cur node $ $node)";
         if ($propnode != $node)
         {
            if ($node)
            {
               $listing[$index++]["endpath"] = true;
               clearVars();
            }
               
            $node = $propnode;

            $listing[$index++]["newpath"] = $node;
            clearVars();
         }
         
         $listing[$index++]["properties"] = true;
         clearVars();
         if ($debug) echo "Creating node $node<br>";

         // Skip the row of underscores
         $line = fgets($diff);
         if ($debug) print "Skipping: $line<br>" ;	     
         
         while ($line = trim(fgets($diff)))
         {
            $listing[$index++]["info"] = $line;
            clearVars();
         }
         
         continue;
      }
   }
      
   if ($node)
   { 
      clearVars();
      $listing[$index++]["endpath"] = true;
   }
      
   if ($debug) print_r($listing);
}		   


$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."compare.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);
   
?>