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
      
      $tree = array();
      
      // Run the file list command
      if ($files = popen($config->svnlook." tree $revstr ".$this->repPath." $path", "r"))
      {      
   		while (!feof($files))
   		{
   		   $entry = fgets($files);
   		   
   		   // If the line is a top-level entry, add it to the array
   		   if (strlen($entry) > 1 && $entry{0} == " " && $entry{1} != " ")
   		   {
   		      // Clear off whitespace, new lines etc.
   		      $entry = transChars(trim($entry));
   		      
   		      // Add the entry to the tree
   		      $tree[] = $entry;
   		   }
   		
   		}
   		
   		pclose($files);
                     
         // Sort the entries into alphabetical order with the directories at the top of the list
         usort($tree, "_dirSort");
      }
      else // An error occured
      {
         $output = transChars(shell_exec($config->svnlook." tree $revstr ".$this->repPath." $path 2>&1"));
         if (!empty($output)) print "<p>".nl2br($output);
         exit;
      }
      
      return $tree;
   }
   
   function getLogDetails($path, $rev = 0)
   {
      global $config, $lang;
      
      $revstr = _revStr($rev);
      
      // Get the log info
      $output = runCommand($config->svnlook." info $revstr ".$this->repPath." $path");
                  
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
   
   // getFileContents
   //
   // Dump the content of a file to the given filename
   
   function getFileContents($path, $filename, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get the log info
      exec($config->svnlook." cat $revstr ".$this->repPath." $path > $filename");
  }

   // listFileContents
   //
   // Print the contents of a file without filling up Apache's memory
   
   function listFileContents($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get the log info
      if (!($result = popen($config->svnlook." cat $revstr ".$this->repPath." $path 2>&1", "r")))
         return;
      
		while (!feof($result))
		{
			print(transChars(fgets($result, 1000)));
		}
		
		pclose($result);
   }

   function getHistory($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      // Get the history info
      $output = runCommand($config->svnlook." history $revstr ".$this->repPath." $path");
            
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
