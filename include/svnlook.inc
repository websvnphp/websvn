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

require_once("include/utils.inc");

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
   
   return strnatcasecmp($e1, $e2);
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
         
         $cmd = quoteCommand($config->svnlook." tree $revstr ".$this->repPath." > $cachedname", false);
         @exec($cmd);
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
         
         $cmd = $config->svnlook." tree $revstr ".$this->repPath." $path";
         $c = quoteCommand($cmd, false);
         
         if ($files = popen($c, "r"))
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
            $c = quoteCommand($cmd, true);
               
            $output = transChars(shell_exec($c));
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
         
         $cmd = quoteCommand($config->svnlook." info $revstr ".$this->repPath." $path > $cachedname", false);
         @exec($cmd);
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
      $log["author"] = ($output[0] ? $output[0] : "&nbsp;");
      
      $date = $output[1];
      $log["date"] = $date;

      sscanf($date, "%d-%d-%d %d:%d:%d %d", $y, $mo, $d, $h, $m, $s, $offset);
      
      // Remove the offset to get the time in GMT
      $h -= $offset / 100;
      
      // Get the commit time as seconds.  Note that the mktime function assumes that the time being
      // passed to it is a local time - it will therefore subtract/add the required number of hours
      // to make it GMT.  Since we already have the time in GMT we first add the localtime offset to
      // get the commit time in local time.  The offset calculated above already handles the summer
      // time case, so we tell mktime not to take this into account.
      
      $lt = gettimeofday();
      $minwest = $lt["minuteswest"];
      $committime = mktime($h ,$m - $minwest, $s, $mo, $d, $y, 0);
      $log["committime"] = $committime;
      
      // Get the current time (as GMT)
      $t = localtime(time(), 1);
      $curtime = mktime($t["tm_hour"],  $t["tm_min"], $t["tm_sec"], $t["tm_mon"] + 1, $t["tm_mday"], $t["tm_year"] + 1900, $t["tm_isdst"]);
      
      // Get the number of seconds since the commit
      $agesecs = $curtime - $committime;
      if ($agesecs < 0) $agesecs = 0;
      
      // Now create a nice human readable age based on these figures
      $years = floor($agesecs / (60*60*24*365));
      if ($years > 1)
      {
         $log["age"] = "$years&nbsp;${lang["YEARS"]}";         
      }
      else
      {
         $months = floor($agesecs / (60*60*24*365/12));
         if ($months > 1)
         {
            $log["age"] = "$months&nbsp;${lang["MONTHS"]}";
         }
         else
         {
            $weeks = floor($agesecs / (60*60*24*7));
            if ($weeks > 1)
            {
               $log["age"] = "$weeks&nbsp;${lang["WEEKS"]}";
            }
            else
            {              
               $days = floor($agesecs / (60*60*24));
               if ($days > 1)
               {
                  $log["age"] = "$days&nbsp;${lang["DAYS"]}";
               }
               else
               { 
                  $hours = floor($agesecs / (60*60));
                  if ($hours > 1)
                  {
                     $log["age"] = "$hours&nbsp;${lang["HOURS"]}";
                  }
                  else
                  { 
                     $minutes = floor($agesecs / 60);
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
         
         $cmd = quoteCommand($config->svnlook." changed $revstr ".$this->repPath." > $cachedname", false);
         @exec($cmd);
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
      
      natcasesort($changes["added"]);
      natcasesort($changes["deleted"]);
      natcasesort($changes["updated"]);
    
      return $changes;              
   }
   
   // getFileContents
   //
   // Dump the content of a file to the given filename
   
   function getFileContents($path, $filename, $rev = 0, $pipe = "")
   {
      global $config, $extEnscript;
      
      $revstr = _revStr($rev);
      
      // If there's no filename, we'll just deliver the contents as it is to the user
      if ($filename == "")
      {
         passthru($config->svnlook." cat $revstr ".$this->repPath." $path $pipe");
         return;
      }
      
      // Get the file contents info
      
      $ext = strrchr($path, ".");
      $l = @$extEnscript[$ext];
        
      $path = quote($path);

      if ($l == "php")
      {         
         // Output the file to the filename
         $cmd = quoteCommand($config->svnlook." cat $revstr ".$this->repPath." $path > $filename", false);
         @exec($cmd);
         
         // Get the file as a string (memory hogging, but we have no other options)
         $content = highlight_file($filename, true);
         
         // Destroy the previous version, and replace it with the highlighted version
         $f = fopen($filename, "w");
         if ($f)
         {
            // The highlight file function doesn't deal with line endings very nicely at all.  We'll have to do it
            // by hand.
            $content = explode("<br />", $content);
            
            foreach ($content as $line)
            {
               fputs($f, rtrim($line)."\n");
            }
            
            fclose($f);
         }         
      }
      else
      {
         if ($config->useEnscript)
         {
            // Get the files, feed it through enscript, then remove the enscript headers using sed
            //
            // Note that the sec command returns only the part of the file between <PRE> and </PRE>.
            // It's complicated because it's designed not to return those lines themselves.
         
            $cmd = quoteCommand($config->svnlook." cat $revstr ".$this->repPath." $path | ".
                                $config->enscript." --language=html ".
								        ($l ? "--color --pretty-print=$l" : "")." -o - | ".
                                $config->sed." -n '1,/^<PRE.$/!{/^<\\/PRE.$/,/^<PRE.$/!p}' > $filename", false);
            @exec($cmd);
         }
         else
         {
            $cmd = quoteCommand($config->svnlook." cat $revstr ".$this->repPath." $path > $filename", false);
            @exec($cmd);
         }
      }
  }

   // listFileContents
   //
   // Print the contents of a file without filling up Apache's memory
   
   function listFileContents($path, $rev = 0)
   {
      global $config, $extEnscript;
      
      $revstr = _revStr($rev);
      $pre = false;
      
      // Get the file contents info
      
      $ext = strrchr($path, ".");
      $l = @$extEnscript[$ext];
      
      $path = quote($path);
      
      // Deal with php highlighting internally
      if ($l == "php")
      {
         $tmp = tempnam("temp", "wsvn");
         
         // Output the file to a temporary file
         $cmd = quoteCommand($config->svnlook." cat $revstr ".$this->repPath." $path > $tmp", false);
         @exec($cmd);
         highlight_file($tmp);
         unlink($tmp);
      }
      else
      {  
         if ($config->useEnscript)
         {
            $cmd = quoteCommand($config->svnlook." cat $revstr ".$this->repPath." $path | ".
                                $config->enscript." --language=html ".
 								        ($l ? "--color --pretty-print=$l" : "")." -o - | ".
                                $config->sed." -n '/^<PRE.$/,/^<\\/PRE.$/p' 2>&1", false);
                                  
            if (!($result = popen($cmd, "r")))
               return;
         }
         else
         {
            $cmd = quoteCommand($config->svnlook." cat $revstr ".$this->repPath." $path 2>&1", false);
            
            if (!($result = popen($cmd, "r")))
               return;
              
            $pre = true;
         }
          
         if ($pre)
            echo "<PRE>";
            
   		while (!feof($result))
   		{
   			print hardspace(transChars(fgets($result, 1024), $pre));
   		}
    
         if ($pre)
            echo "</PRE>";
   		
   		pclose($result);
      }
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
         if ($record != "")
         {
            list($rev, $path) = split(" ", $record, 2);
            if ($rev)
            {
               $path = trim($path);
               $history[] = array("rev" => $rev, "path" => $path);
            }
         }
      }
         
      return $history;
   }
}
?>
