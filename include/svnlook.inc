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

if (!@$SVNLOOK)
{
$SVNLOOK = true;

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
      global $config, $locwebsvnreal;
      
      $revstr = _revStr($rev);
      
      $tree = array();
      
      // Try to find a cached version of this revision of the repository
      
      if ($rev == 0)
      {
         $head = runCommand($config->svnlook." youngest ".$this->repPath);
         settype($head[0], "integer");
         $rev = $head[0];
      }
      
      $cachedname = strtr($this->repPath, ":/\\", "___");
      $cachedname = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachedname."_$rev";
      
      if ($config->cacheResults && !file_exists($cachedname))
      {
         // Cache the directory layout
         
         exec($config->svnlook." tree $revstr ".$this->repPath." > $cachedname");
      }
      
      if ($config->cacheResults && file_exists($cachedname))
      {
         if ($files = fopen($cachedname, "r"))
         {
            $state = 0; // Work out sub path
            $finished = false;
            $spc = "";
            if ($path == "" || $path{0} != "/")
            {
               $path = "/".$path;
            }
                  
            while (!feof($files) && !$finished)
            {
               switch ($state)
               {
                  case 0: // Find the first string to search for
                     
                     // If there are no more subdirectories left, move on
                     if ($path == "")
                     {
                        $state = 2;
                        break;
                     }
                     
                     $pos = strpos($path, "/");
                     $searchpath = $spc.substr($path, 0, $pos + 1);
                     $path = substr($path, $pos + 1);
                     $spc .= " ";
                     $state = 1;
                     
                     break;
                     
                  case 1: // Search for the path
                     $line = fgets($files);
                     if (strcmp(rtrim($line), $searchpath) == 0)
                     {
                        $state = 0;
                     }
                     break;
                     
                  case 2: // Count the number of spaces at the beginning of the path name
                     $spaces = 0;
                     while ($line{$spaces} == " ")
                        $spaces++;
                     $spaces++;
                     $state = 3;
                     break;
                     
                  case 3: // Find all the lines with $spaces before them
                     $line = fgets($files);
                     $ok = true;
                     if (strlen($line) > $spaces)
                     {
                        for ($n = 0; $n < $spaces; $n++)
                        {
                           if ($line{$n} != " ")
                           {
                              $ok = false;
                              break;
                           }
                        }
                     }
                     else
                     {
                        $ok = false;
                     }
                     
                     // If there was a non-space, then we've finished our search
                     if (!$ok)
                     {
                        $finished = true;
                        break;
                     }
                     
                     // If the aren't any more spaces afterwards, add this to the tree
                     if ($line{$spaces} != " ")
                     {
                        $tree[] = trim($line);
                     }
                     break;
               }
            }

            fclose($files);
            
            // Sort the entries into alphabetical order with the directories at the top of the list
            usort($tree, "_dirSort");
            
            return $tree;
         }
      }
      else // Still no cache file.  We'll do it the old fashion way
      {
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
   }
   
   function getLogDetails($path, $rev = 0)
   {
      global $config, $lang, $locwebsvnreal;
      
      $revstr = _revStr($rev);
      $path = quote($path);
      
      // Try to find a cached version of this revision of the repository
      
      if ($rev == 0)
      {
         $head = runCommand($config->svnlook." youngest ".$this->repPath);
         settype($head[0], "integer");
         $rev = $head[0];
      }
      
      $cachedname = strtr($this->repPath, ":/\\", "___");
      $cachedname = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachedname."_log_$rev";
      
      if ($config->cacheResults && !file_exists($cachedname))
      {
         // Cache the directory layout
         
         exec($config->svnlook." info $revstr ".$this->repPath." $path > $cachedname");
      }
      
      // Get the log info
      if ($config->cacheResults && file_exists($cachedname))
      {
         $output = transArray(file($cachedname));
      }
      else
      {   
         $output = runCommand($config->svnlook." info $revstr ".$this->repPath." $path");
      }
                     
      // Create a nice array to return
      $log["author"] = $output[0];
      
      $date = $output[1];
      $log["date"] = $date;

      sscanf($date, "%d-%d-%d %d:%d:%d", $y, $mo, $d, $h, $m, $s);
      
      // Get this time (as GMT) in seconds
      $committime = mktime($h ,$m, $s, $mo, $d, $y, 0);
      
      // Get the number of seconds since the commit
      $agesecs = time() - $committime;
      if ($agesecs < 0) $agesecs = 0;
      
      // Now create a nice human readable age based on these figures
      $years = floor($agesecs / (60*60*24*356));
      $agesecs -= $years * (60*60*24*356);
      if ($years > 0)
      {
         if ($years == 1)
            $log["age"] = "1&nbsp;${lang["YEAR"]}";
         else
            $log["age"] = "$years&nbsp;${lang["YEARS"]}";         
      }
      else
      {
         $months = floor($agesecs / (60*60*24*356/12));
         $agesecs -= $months * (60*60*24*356/12);
         if ($months > 0)
         {
            if ($months == 1)
               $log["age"] = "1&nbsp;${lang["MONTH"]}";
            else
               $log["age"] = "$months&nbsp;${lang["MONTHS"]}";
         }
         else
         {
            $weeks = floor($agesecs / (60*60*24*7));
            $agesecs -= $weeks * (60*60*24*7);
            if ($weeks > 0)
            {
               if ($weeks == 1)
                  $log["age"] = "1&nbsp;${lang["WEEK"]}";
               else
                  $log["age"] = "$weeks&nbsp;${lang["WEEKS"]}";
            }
            else
            {              
               $days = floor($agesecs / (60*60*24));
               $agesecs -= $days * (60*60*24);
               if ($days > 0)
               {
                  if ($days == 1)
                     $log["age"] = "1&nbsp;${lang["DAY"]}";
                  else
                      $log["age"] = "$days&nbsp;${lang["DAYS"]}";
               }
               else
               { 
                  $hours = floor($agesecs / (60*60));
                  $agesecs -= $hours * (60*60*24);
                  if ($hours > 0)
                  {
                     if ($hours == 1)
                        $log["age"] = "1&nbsp;${lang["HOUR"]}";
                     else
                        $log["age"] = "$hours&nbsp;${lang["HOURS"]}";
                  }
                  else
                  { 
                     $minutes = floor($agesecs / 60);
                     if ($minutes == 1)
                        $log["age"] = "1&nbsp;${lang["MINUTE"]}";
                     else
                        $log["age"] = "$minutes&nbsp;${lang["MINUTES"]}";
                  }
               }
            }
         }
      }
      
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
         settype($head[0], "integer");
         $log["rev"] = $head[0];
      }
       
      return $log;
   }
   
   function getChangedFiles($rev = 0)
   {
      global $config, $locwebsvnreal;
      
      $revstr = _revStr($rev);
         
      // Try to find a cached version of this revision of the repository
      
      if ($rev == 0)
      {
         $head = runCommand($config->svnlook." youngest ".$this->repPath);
         settype($head[0], "integer");
         $rev = $head[0];
      }
      
      $cachedname = strtr($this->repPath, ":/\\", "___");
      $cachedname = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachedname."_mods_$rev";
      
      if ($config->cacheResults && !file_exists($cachedname))
      {
         // Cache the directory layout
         
         exec($config->svnlook." changed $revstr ".$this->repPath." > $cachedname");
      }
      
      if ($config->cacheResults && file_exists($cachedname))
      {
         $output = transArray(file($cachedname));
      }
      else
      {
        $output = runCommand($config->svnlook." changed $revstr ".$this->repPath);
      }
           
      // Now create a nicer array from that
      $changes = array("added" => array(), "updated" => array(), "deleted" => array());
      
      foreach ($output as $changed)
      {
         $mod = $changed{0};
         $name = substr($changed, 4);
         
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
      global $config, $extensions;
      
      $revstr = _revStr($rev);
      $path = quote($path);
      
      // Get the file contents info
      
      $ext = strrchr($path, ".");
      $l = @$extensions[$ext];
        
      if ($config->useEnscript && !empty($l))
      {
         exec($config->svnlook." cat $revstr ".$this->repPath." $path | ".
              $config->enscript." --color --language=html --pretty-print=$l -o - | ".
              $config->sed." -n '/^<PRE.$/,/^<\\/PRE.$/p' > $filename");
      }
      else
      {
         exec($config->svnlook." cat $revstr ".$this->repPath." $path > $filename");
      }
  }

   // listFileContents
   //
   // Print the contents of a file without filling up Apache's memory
   
   function listFileContents($path, $br, $rev = 0)
   {
      global $config, $extensions;
      
      $revstr = _revStr($rev);
      $pre = false;
      
      // Get the file contents info
      
      $ext = strrchr($path, ".");
      $l = @$extensions[$ext];
      
      $path = quote($path);
        
      if ($config->useEnscript && !empty($l))
      {
         if (!($result = popen($config->svnlook." cat $revstr ".$this->repPath." $path | ".
                               $config->enscript." --color --language=html --pretty-print=$l -o - | ".
                               $config->sed." -n '/^<PRE.$/,/^<\\/PRE.$/p' 2>&1", "r")))
            return;
      }
      else
      {
         if (!($result = popen($config->svnlook." cat $revstr ".$this->repPath." $path 2>&1", "r")))
            return;
           
         $pre = true;
      }
       
      if ($pre)
         echo "<PRE>";
         
		while (!feof($result))
		{
			print(transChars(fgets($result, 1024), $pre));
			if ($br) echo "<br>";
		}

      if ($pre)
         echo "</PRE>";
		
		pclose($result);
   }

   function getHistory($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      $path = quote($path);
      
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

}
?>
