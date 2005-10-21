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

//
// --- Classes for retaining log information ---
//

$debugxml = false;

Class SVNMod
{
   var $action = "";
   var $copyfrom = "";
   var $copyrev = "";
   var $path = "";
}

Class SVNLogEntry
{
   var $rev = 1;
   var $author = "";
   var $date = "";
   var $committime;
   var $age = "";
   var $msg = "";
   var $path = "";
   
   var $mods;
   var $curMod;
}

Class SVNLog
{
   var $entries; // Array of entries
   var $curEntry; // Current entry
   
   var $path = ""; // Temporary variable used to trace path history
   
   // findEntry 
   //
   // Return the entry for a given revision
   
   function findEntry($rev)
   {
      foreach ($this->entries as $index => $entry)
      {
         if ($entry->rev == $rev)
            return $index;

      }
   }
}

//
// --- XML parsing functions---
//

$curLog = 0;
$curTag = "";

function startElement($parser, $name, $attrs)
{
    global $curLog, $curTag, $debugxml;

    switch ($name)
    {
       case "LOGENTRY":
         if ($debugxml) print "Creating new log entry\n";
         $curLog->curEntry = new SVNLogEntry;
         $curLog->curEntry->mods = array();
         
         $curLog->curEntry->path = $curLog->path;
         
         if (sizeof($attrs))
         {
            while (list($k, $v) = each($attrs))
            {
               switch ($k)
               {
                  case "REVISION":
                     if ($debugxml) print "Revision $v\n";
                     $curLog->curEntry->rev = $v;
                     break;
               }
            }
         }
         break;
      
      case "PATH":
         if ($debugxml) print "Creating new path\n";
         $curLog->curEntry->curMod = new SVNMod;
         
         if (sizeof($attrs))
         {
            while (list($k, $v) = each($attrs))
            {
               switch ($k)
               {
                  case "ACTION":
                     if ($debugxml) print "Action $v\n";
                     $curLog->curEntry->curMod->action = $v;
                     break;

                  case "COPYFROM-PATH":
                     if ($debugxml) print "Copy from: $v\n";
                     $curLog->curEntry->curMod->copyfrom = $v;
                     break;

                  case "COPYFROM-REV":
                     $curLog->curEntry->curMod->copyrev = $v;
                     break;
               }
            }
         }
         
         $curTag = $name;  
         break;
            
      default:
         $curTag = $name;        
         break;
   }
}

function endElement($parser, $name) 
{
    global $curLog, $debugxml;

    switch ($name)
    {
      case "LOGENTRY":
         if ($debugxml) print "Ending new log entry\n";
         $curLog->entries[] = $curLog->curEntry;
         break;

      case "PATH":
         if ($debugxml) print "Ending path\n";
         $curLog->curEntry->mods[] = $curLog->curEntry->curMod;
         break;
    }
    
    $curTag = "";
}

function characterData($parser, $data)
{
   global $curLog, $curTag, $lang, $debugxml;

   $data = trim($data);
   if (empty($data))
      return;
         
   $data = utf8_decode($data);
   
   switch ($curTag)
   {
      case "AUTHOR":
         if ($debugxml) print "Author: $data\n";
         $curLog->curEntry->author .= $data;
         break;

      case "DATE":
         if ($debugxml) print "Date: $data\n";
         
         sscanf($data, "%d-%d-%dT%d:%d:%d.", $y, $mo, $d, $h, $m, $s);
         
         $curLog->curEntry->date = "$y-$mo-$d $h:$m:$s";
                  
         $committime = strtotime($curLog->curEntry->date);
         $curLog->curEntry->committime = $committime;
         $curtime = time();
   
         // Get the number of seconds since the commit
         $agesecs = $curtime - $committime;
         if ($agesecs < 0) $agesecs = 0;
         
         $curLog->curEntry->age = datetimeFormatDuration($agesecs, true, true);
      
         break;

      case "MSG":
         if ($debugxml) print "Msg: '$data'\n";
         $curLog->curEntry->msg .= $data;
         break;
         
      case "PATH":
         if ($debugxml) print "Path name: '$data'\n";
         $curLog->curEntry->curMod->path .= $data;
         
         if ($data == $curLog->path)
         {
            
            if ($debugxml) print "New path for comparison: '".$curLog->curEntry->curMod->copyfrom."'\n";
            $curLog->path = $curLog->curEntry->curMod->copyfrom;
         }
            
         break;
   }
}


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

// Function to encode a URL without encoding the /'s

function encodepath($uri)
{
   global $config;

   $parts = explode('/', $uri);
   for ($i = 0; $i < count($parts); $i++)
   {
      if ( function_exists("mb_detect_encoding") && function_exists("mb_convert_encoding"))
      {
         $parts[$i] = mb_convert_encoding($parts[$i], "UTF-8", mb_detect_encoding($parts[$i]));
      }
      
      $parts[$i] = rawurlencode($parts[$i]);
   }
   
   $uri = implode('/', $parts);
   
   // Quick hack.  Subversion seems to have a bug surrounding the use of %3A instead of :
   
   $uri = str_replace("%3A" ,":", $uri);
   
   // Correct for Window share names
   if ( $config->serverIsWindows==true )
   {
      if ( substr($uri, 0,2)=="//" )
         $uri="\\".substr($uri, 2, strlen($uri));
   }

   return $uri;
}

// The SVNRepository Class

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
         $head = runCommand($config->svnlook." youngest ".quote($this->repPath));
         settype($head[0], "integer");
         $rev = $head[0];
      }
      
      $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
      $output = runCommand($config->svn." list $revstr ".quote("file:///".$path), true);
      
      foreach ($output as $entry)
      {
         if ($entry != "")
            $tree[] = $entry;
      }
      
      // Sort the entries into alphabetical order with the directories at the top of the list
      usort($tree, "_dirSort");
      
      return $tree;
   }
   
   function getLogDetails($path, $rev = 0)
   {
      global $config, $lang, $locwebsvnreal;
      
      if ($rev == 0)
      {
         $head = runCommand($config->svnlook." youngest ".quote($this->repPath));
         settype($head[0], "integer");
         $rev = $head[0];
      }

      $revstr = _revStr($rev);
      $path = quote($path);
      
      // Try to find a cached version of this revision of the repository
            
      $cachedname = strtr($this->repPath, ":/\\", "___");
      $cachedname = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachedname."_log_$rev";
      
      if ($config->cacheResults && !file_exists($cachedname))
      {
         // Cache the directory layout
         
         $cmd = quoteCommand($config->svnlook." info $revstr ".quote($this->repPath)." $path", false);

         if (($handle = popen($cmd, "r")) !== FALSE)
         {
            if (($output = gzopen($cachedname, "w")) !== FALSE)
            {
                  while (!feof($handle))
            		{
            		   $line = fgets($handle);
            		   gzwrite($output, $line);
            		}
            		
            	gzclose($output);
            }
         	pclose($handle);
         }
      }
      
      // Get the log info
      if ($config->cacheResults && file_exists($cachedname))
      {
         $output = transArray(gzfile($cachedname));
      }
      else
      {   
         $output = runCommand($config->svnlook." info $revstr ".quote($this->repPath)." $path");
      }
                     
      // Create a nice array to return
      $log["author"] = ($output[0] ? $output[0] : "&nbsp;");
      
      $date = $output[1];
      $log["date"] = $date;
            
      $log['committime'] = strtotime($date);
      $curtime = time();

      // Get the number of seconds since the commit
      $agesecs = $curtime - $log['committime'];
      if ($agesecs < 0) $agesecs = 0;
      
      $log['age'] = datetimeFormatDuration($agesecs, true, true);
      
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
         $head = runCommand($config->svnlook." youngest ".quote($this->repPath));
         settype($head[0], "integer");
         $log["rev"] = $head[0];
      }
       
      return $log;
   }
   
   function getChangedFiles($rev = 0)
   {
      global $config, $locwebsvnreal;
      
      if ($rev == 0)
      {
         $head = runCommand($config->svnlook." youngest ".quote($this->repPath));
         settype($head[0], "integer");
         $rev = $head[0];
      }
      
      $revstr = _revStr($rev);
         
      // Try to find a cached version of this revision of the repository
      
      $cachedname = strtr($this->repPath, ":/\\", "___");
      $cachedname = $locwebsvnreal.DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR.$cachedname."_mods_$rev";
      
      if ($config->cacheResults && !file_exists($cachedname))
      {
         // Cache the directory layout
         
         $cmd = quoteCommand($config->svnlook." changed $revstr ".quote($this->repPath), false);

         if (($handle = popen($cmd, "r")) !== FALSE)
         {
            if (($output = gzopen($cachedname, "w")) !== FALSE)
            {
                  while (!feof($handle))
            		{
            		   $line = fgets($handle);
            		   gzwrite($output, $line);
            		}
            		
            	gzclose($output);
            }
         	pclose($handle);
         }
      }
      
      if ($config->cacheResults && file_exists($cachedname))
      {
         $output = transArray(gzfile($cachedname));
      }
      else
      {
        $output = runCommand($config->svnlook." changed $revstr ".quote($this->repPath));
      }
           
      // Now create a nicer array from that
      $changes = array("added" => array(), "updated" => array(), "deleted" => array());
      
      foreach ($output as $changed)
      {
         if (trim($changed) != "")
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
         $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
         passthru($config->svn." cat $revstr file:///".rawurlencode($path)." $pipe");
         return;
      }
      
      // Get the file contents info
      
      $ext = strrchr($path, ".");
      $l = @$extEnscript[$ext];
        
      if ($l == "php")
      {         
         // Output the file to the filename
         $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
         $cmd = quoteCommand($config->svn." cat $revstr ".quote("file:///".$path)." > $filename", false);
         @exec($cmd);
         
         // Get the file as a string (memory hogging, but we have no other options)
         $content = highlight_file($filename, true);
         
         // Destroy the previous version, and replace it with the highlighted version
         $f = fopen($filename, "w");
         if ($f)
         {
            // The highlight file function doesn't deal with line endings very nicely at all.  We'll have to do it
            // by hand.
            
            // Remove the first line generated by highlight()
            $pos = strpos($content, "\n");
            $content = substr($content, $pos+1);
            
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
         
            $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
            $cmd = quoteCommand($config->svn." cat $revstr ".quote("file:///".$path)." | ".
                                $config->enscript." --language=html ".
								        ($l ? "--color --pretty-print=$l" : "")." -o - | ".
                                $config->sed." -n ".$config->quote."1,/^<PRE.$/!{/^<\\/PRE.$/,/^<PRE.$/!p;}".$config->quote." > $filename", false);
            @exec($cmd);
         }
         else
         {
            $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
            $cmd = quoteCommand($config->svn." cat $revstr ".quote("file:///".$path)." > $filename", false);
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
      
      // Deal with php highlighting internally
      if ($l == "php")
      {
         $tmp = tempnam("temp", "wsvn");
         
         // Output the file to a temporary file
         $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
         $cmd = quoteCommand($config->svn." cat $revstr ".quote("file:///".$path)." > $tmp", false);
         @exec($cmd);
         highlight_file($tmp);
         unlink($tmp);
      }
      else
      {  
         if ($config->useEnscript)
         {
            $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
            $cmd = quoteCommand($config->svn." cat $revstr ".quote("file:///".$path)." | ".
                                $config->enscript." --language=html ".
 								        ($l ? "--color --pretty-print=$l" : "")." -o - | ".
                                $config->sed." -n ".$config->quote."/^<PRE.$/,/^<\\/PRE.$/p".$config->quote." 2>&1", false);
                                  
            if (!($result = popen($cmd, "r")))
               return;
         }
         else
         {
            $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
            $cmd = quoteCommand($config->svn." cat $revstr ".quote("file:///".$path)." 2>&1", false);
            
            if (!($result = popen($cmd, "r")))
               return;
              
            $pre = true;
         }
          
         if ($pre)
            echo "<PRE>";
            
   		while (!feof($result))
   		{
   		   $line = fgets($result, 1024);
   		   if ($pre) $line = replaceEntities($line);
   		   
   		   print hardspace($line);
   		}
    
         if ($pre)
            echo "</PRE>";
   		
   		pclose($result);
      }
   }

   // getBlameDetails
   //
   // Dump the blame content of a file to the given filename
   
   function getBlameDetails($path, $filename, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
      $cmd = quoteCommand($config->svn." blame $revstr ".quote("file:///".$path)." > $filename", false);
      
      @exec($cmd);
  }

   function getProperty($path, $property, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
      $ret = runCommand($config->svn." propget $property $revstr ".quote("file:///".$path), true);
      
      // Remove the surplus newline
      if (count($ret))
         unset($ret[count($ret) - 1]);
      
      return implode("\n", $ret);
   }

  function getHistory($path, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      $path = quote($path);
      
      // Get the history info
      $output = runCommand($config->svnlook." history $revstr ".quote($this->repPath)." $path");
            
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

   // exportDirectory
   //
   // Exports the directory to the given location
   
   function exportDirectory($path, $filename, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repPath.$path));
      $cmd = quoteCommand($config->svn." export $revstr ".quote("file:///".$path)." $filename", false);

      @exec($cmd);
   }
  
   function getLog($path, $brev = "", $erev = 1, $quiet = false, $limit = 2)
   {
      global $config, $curLog;
      
      $xml_parser = xml_parser_create("UTF-8");
      xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
      xml_set_element_handler($xml_parser, "startElement", "endElement");
      xml_set_character_data_handler($xml_parser, "characterData");

      // Since directories returned by svn log don't have trailing slashes (:-(), we need to remove
      // the trailing slash from the path for comparison purposes
      
      if ($path{strlen($path) - 1} == "/" && $path != "/")
         $path = substr($path, 0, -1);
      
      $curLog = new SVNLog;
      $curLog->entries = array();
      $curLog->path = $path;
      
      $revStr = "";
      
      if ($brev && $erev)
         $revStr = "-r$brev:$erev";
      else if ($brev)
         $revStr = "-r$brev:1";      
      
      if ($limit != 0)
         $revStr .= " --limit $limit";
         
      // Get the log info
      $path = str_replace(array(" ", DIRECTORY_SEPARATOR), array("%20", "/"), $this->repPath.$path);
      $info = "--verbose";
      if ($quiet)
         $info = "--quiet";
      $cmd = quoteCommand($config->svn." log --xml $info $revStr ".quote("file:///".$path), false);
      
      if ($handle = popen($cmd, "r"))
      {
         $firstline = true;
   		while (!feof($handle))
   		{
   		   $line = fgets($handle);
            if (!xml_parse($xml_parser, $line, feof($handle)))
            {
              die(sprintf("XML error: %s at line %d column %d byte %d<br>cmd: $cmd<nr>",
                          xml_error_string(xml_get_error_code($xml_parser)),
                          xml_get_current_line_number($xml_parser),
                          xml_get_current_column_number($xml_parser),
                          xml_get_current_byte_index($xml_parser)));
            }
   		}
		
		   pclose($handle);
      }
            
      xml_parser_free($xml_parser);
      return $curLog;
   }  
}
?>
