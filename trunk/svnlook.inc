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
// svn-look.inc
//
// Svn bindings
//
// These binding currently use svnlook to achieve their goal.  Once a proper SWIG binding has been
// produces, there'll be an option to use those instead

// Function returns true if the give entry in a directory tree is at the top level

function _topLevel($entry)
{
   // To be at top level, there must be one space before the entry
   return (strlen($entry) > 1 && $entry{0} == " " && $entry{1} != " ");
}

// Function to sort two given directory entries.  Directories go at the top

function _dirSort($e1, $e2)
{
   $isDir1 = $e1{strlen($e1) - 1} == "/";
   $isDir2 = $e2{strlen($e2) - 1} == "/";
   
   if ($isDir1 && !$isDir2) return -1;
   if ($isDir2 && !$isDir1) return 1;
   
   return strcmp($e1, $e2);
}

// Return the revision string to pass to a command

function _revStr($rev)
{
   if ($rev > 0)
      return "-r $rev";
   else
      return "";
}

// The SVNRepositor Class

Class SVNRepository
{
   var $repPath;
   
   function SVNRepository($repPath)
   {
      $this->repPath = $repPath;
   }
   
   function dirContents($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get complete tree for given path and revision 
      $tree = runCommand($config->svnlook." tree $revstr ".$this->repPath." $path");
      
      // Split this data up into an array
      $tree = explode("\n", $tree);
      
      // Remove all but the top level entries 
      $tree = array_filter($tree, "_topLevel");
      
      // Sort the entries into alphabetical order with the directories at the top of the list
      usort($tree, "_dirSort");
      
      // Remove all the trailing spaces
      $tree = array_map("trim", $tree);
      
      return $tree;
   }
   
   function getLogDetails($path, $rev = 0)
   {
      global $config, $lang;
      
      $revstr = _revStr($rev);
      
      // Get the log info
      $output = runCommand($config->svnlook." info $revstr ".$this->repPath." $path");
      
      // Split this data up into an array
      $output = explode("\n", $output);
            
      // Create a nice array to return
      $log["author"] = $output[0];
      $log["date"] = $output[1];
      
      $log["message"] = $output[3]; 
      $pos = 4;
      while (isset($output[$pos]))
      {
         $log["message"] .= "\n".$output[$pos];
         $pos++;
      }
            
      // If we're working on the head, get the head revision
      if ($rev > 0)
         $log["rev"] = $rev;
      else
      {
         $head = runCommand($config->svnlook." youngest ".$this->repPath);
         settype($head, "integer");
         $log["rev"] = $head;
      }
       
      return $log;
   }
   
   function getChangedFiles($rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get the log info
      $output = runCommand($config->svnlook." changed $revstr ".$this->repPath);
      
      // Split this data up into an array
      $output = explode("\n", $output);
      
      // Now create a nicer array from that
      $changes = array("added" => array(), "updated" => array(), "deleted" => array());
      
      foreach ($output as $changed)
      {
         list($mod, $name) = sscanf($changed, "%c   %s");
         
         switch ($mod)
         {
            case "A":
               $changes["added"][] = $name;
               break;
               
            case "U":
               $changes["updated"][] = $name;
               break;

            case "D":
               $changes["deleted"][] = $name;
               break;
         }
      }
      
      // Sort each array alphabetically
      
      sort($changes["added"]);
      sort($changes["deleted"]);
      sort($changes["updated"]);
    
      return $changes;              
   }
   
   function getFileContents($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get the log info
      $output = runCommand($config->svnlook." cat $revstr ".$this->repPath." $path");
      
      return $output;
   }

   function getHistory($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get the history info
      $output = runCommand($config->svnlook." history $revstr ".$this->repPath." $path");
      
      // Split this data up into an array
      $output = explode("\n", $output);
      
      // Now create a nicer array from that
      $history = array();
          
      $line = 0;
      foreach ($output as $record)
      {  
         $line++;
         
         // Skip header info
         if ($line < 3)
            continue;  
            
         $record = trim($record);
         list($rev, $path) = sscanf($record, "%d   %s");
         if ($rev)
            $history[] = array("rev" => $rev, "path" => $path);
      }
         
      return $history;
   }
}
?>
