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

$svnrep = new SVNRepository($rep->path);

function checkRevision($rev)
{
   if (is_numeric($rev) && ((int)$rev > 0))
      return $rev;
      
   $rev = strtoupper($rev);
   
   switch($rev)
   {
      case "HEAD":
      case "PREV":
      case "COMMITTED":
         return $rev;
   }
   
   return "HEAD";   
}

$svnrep = new SVNRepository($rep->path);

// Retrieve the request information
$path1 = @$_REQUEST["compare"][0];
$path2 = @$_REQUEST["compare"][1];
$rev1 = @$_REQUEST["compare_rev"][0];
$rev2 = @$_REQUEST["compare_rev"][1];

// Some page links put the revision with the path...
if (strpos($path1, "@")) list($path1, $rev1) = explode("@", $path1);
if (strpos($path2, "@")) list($path2, $rev2) = explode("@", $path2);

$rev1 = checkRevision($rev1);
$rev2 = checkRevision($rev2);

// Choose a sensible comparison order unless told not to
if (!@$_REQUEST["manualorder"] && is_numeric($rev1) && is_numeric($rev2))
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

$vars["repname"] = $rep->name;
$vars["action"] = $lang["PATHCOMPARISON"];
$vars["compare_form"] = "<form action=\"$url\" method=\"post\" name=\"compareform\">";
$vars["compare_path1input"] = "<input type=\"text\" size=\"40\" name=\"compare[0]\" value=\"$path1\">";
$vars["compare_rev1input"] = "<input type=\"text\" size=\"5\" name=\"compare_rev[0]\" value=\"$rev1\">";
$vars["compare_path2input"] = "<input type=\"text\" size=\"40\" name=\"compare[1]\" value=\"$path2\">";
$vars["compare_rev2input"] = "<input type=\"text\" size=\"5\" name=\"compare_rev[1]\" value=\"$rev2\">";
$vars["compare_submit"] = "<input name=\"comparesubmit\" type=\"submit\" value=\"${lang["COMPAREPATHS"]}\">";
$vars["compare_endform"] = "<input type=\"hidden\" name=\"op\" value=\"comp\"><input type=\"hidden\" name=\"manualorder\" value=\"1\"><input type=\"hidden\" name=\"sc\" value=\"$showchanged\"></form>";   

$vars["path1"] = $path1;
$vars["path2"] = $path2;

$vars["rev1"] = $rev1;
$vars["rev2"] = $rev2;

$noinput = empty($path1) || empty($path2);
$listing = array();

// Generate the diff listing
$path1 = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $svnrep->repPath.$path1));
$path2 = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $svnrep->repPath.$path2));

$debug = false;

if (!$noinput)
{
   $rawcmd = $config->svn." diff -r$rev1:$rev2 ".quote("file:///".$path1)." ".quote("file:///".$path2);
   $cmd = quoteCommand($rawcmd, true);
   if ($debug) echo "$cmd\n";
}

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

$vars["success"] = false;

if (!$noinput)
{
   if ($diff = popen($cmd, "r"))
   {
      $index = 0;
      $indiff = false;
      $indiffproper = false;
      $getLine = true;
      $node = null;
   
      $vars["success"] = true;
   
   	while (!feof($diff))
   	{
   	   if ($getLine)
   	      $line = fgets($diff);
         
         clearVars();	   
   	   $getLine = true;
         if ($debug) print "Line = '$line'<br>" ;	         
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
         	            $subline = hardspace(transChars(rtrim(substr($line, 1)), true)); 
         	            if (empty($subline)) $subline = "&nbsp;";
         	            $listing[$index++]["line"] = $subline;
                        if ($debug) print "Including as diff: $subline<br>";
         	            break;
         	   
         	         case "+":
         	            $listing[$index]["diffclass"] = "diffadded";
         	            $subline = hardspace(transChars(rtrim(substr($line, 1)), true)); 
         	            if (empty($subline)) $subline = "&nbsp;";
         	            $listing[$index++]["line"] = $subline;
                        if ($debug) print "Including as added: $subline<br>";
         	            break;
         
         	         case "-":
         	            $listing[$index]["diffclass"] = "diffdeleted";
         	            $subline = hardspace(transChars(rtrim(substr($line, 1)), true)); 
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
                  $listing[$index++]["info"] = $lang["FILEDELETED"];
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
               $listing[$index]["info"] = $lang["FILEADDED"];
            
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
         
         // Check for error messages
         if (strncmp(trim($line), "svn: ", 5) == 0)
         {
            $listing[$index++]["info"] = urldecode($line);
            $vars["success"] = false;
            continue;
         }
         
         $listing[$index++]["info"] = $line;
         
      }
         
      if ($node)
      { 
         clearVars();
         $listing[$index++]["endpath"] = true;
      }
         
      if ($debug) print_r($listing);
   }		   
}

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."compare.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);
   
?>
