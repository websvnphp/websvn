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
// These binding currently use the svn command line to achieve their goal.  Once a proper
// SWIG binding has been produced for PHP, there'll be an option to use that instead.

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
    global $curLog, $debugxml, $curTag;

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

      case "MSG":
         $curLog->curEntry->msg = trim($curLog->curEntry->msg);
         if ($debugxml) print "Completed msg = '".$curLog->curEntry->msg."'\n";
         break;
    }
    
    $curTag = "";
}

function characterData($parser, $data)
{
   global $curLog, $curTag, $lang, $debugxml;

   switch ($curTag)
   {
      case "AUTHOR":
         if ($debugxml) print "Author: $data\n";
         if (empty($data)) return;
         $curLog->curEntry->author .= htmlentities($data, ENT_COMPAT, "UTF-8");
         break;

      case "DATE":
         if ($debugxml) print "Date: $data\n";
         $data = trim($data);
         if (empty($data)) return;
         
         sscanf($data, "%d-%d-%dT%d:%d:%d.", $y, $mo, $d, $h, $m, $s);
         
         $mo = substr("00".$mo, -2);
         $d =  substr("00".$d, -2);
         $h =  substr("00".$h, -2);
         $m =  substr("00".$m, -2);
         $s =  substr("00".$s, -2);
         
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
         $curLog->curEntry->msg .= htmlentities($data, ENT_COMPAT, "UTF-8");
         break;
         
      case "PATH":
         if ($debugxml) print "Path name: '$data'\n";
         $data = trim($data);
         if (empty($data)) return;

         $curLog->curEntry->curMod->path .= $data;
         
         if ($data == $curLog->path && $curLog->curEntry->curMod->action == "A")
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

   $uri = str_replace(DIRECTORY_SEPARATOR, "/", $uri);

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
   var $repConfig;
   
   function SVNRepository($repConfig)
   {
      $this->repConfig = $repConfig;
   }
   
   function dirContents($path, $rev = 0)
   {
      global $config, $locwebsvnreal;
      
      $revstr = _revStr($rev);
      
      $tree = array();
           
      if ($rev == 0)
      {
         $headlog = $this->getLog("/", "", "", true, 1);
         $rev = $headlog->entries[0]->rev;
      }
      
      $path = encodepath($this->repConfig->path.$path);
      $output = runCommand($config->svn." list $revstr ".$this->repConfig->svnParams().quote($path), true);
      
      foreach ($output as $entry)
      {
         if ($entry != "")
            $tree[] = $entry;
      }
      
      // Sort the entries into alphabetical order with the directories at the top of the list
      usort($tree, "_dirSort");
      
      return $tree;
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
         $path = encodepath($this->repConfig->path.$path);
         passthru($config->svn." cat $revstr ".$this->repConfig->svnParams().rawurlencode($path)." $pipe");
         return;
      }
      
      // Get the file contents info
      
      $ext = strrchr($path, ".");
      $l = @$extEnscript[$ext];
        
      if ($l == "php")
      {         
         // Output the file to the filename
         $path = encodepath($this->repConfig->path.$path);
         $cmd = quoteCommand($config->svn." cat $revstr ".$this->repConfig->svnParams().quote($path)." > $filename", false);
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
         
            $path = encodepath($this->repConfig->path.$path);
            $cmd = quoteCommand($config->svn." cat $revstr ".$this->repConfig->svnParams().quote($path)." | ".
                                $config->enscript." --language=html ".
								        ($l ? "--color --pretty-print=$l" : "")." -o - | ".
                                $config->sed." -n ".$config->quote."1,/^<PRE.$/!{/^<\\/PRE.$/,/^<PRE.$/!p;}".$config->quote." > $filename", false);
            @exec($cmd);
         }
         else
         {
            $path = encodepath(str_replace(DIRECTORY_SEPARATOR, "/", $this->repConfig->path.$path));
            $cmd = quoteCommand($config->svn." cat $revstr ".$this->repConfig->svnParams().quote($path)." > $filename", false);
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
         $path = encodepath($this->repConfig->path.$path);
         $cmd = quoteCommand($config->svn." cat $revstr ".$this->repConfig->svnParams().quote($path)." > $tmp", false);
         @exec($cmd);
         highlight_file($tmp);
         unlink($tmp);
      }
      else
      {  
         if ($config->useEnscript)
         {
            $path = encodepath($this->repConfig->path.$path);
            $cmd = quoteCommand($config->svn." cat $revstr ".$this->repConfig->svnParams().quote($path)." | ".
                                $config->enscript." --language=html ".
 								        ($l ? "--color --pretty-print=$l" : "")." -o - | ".
                                $config->sed." -n ".$config->quote."/^<PRE.$/,/^<\\/PRE.$/p".$config->quote." 2>&1", false);
                                  
            if (!($result = popen($cmd, "r")))
               return;
         }
         else
         {
            $path = encodepath($this->repConfig->path.$path);
            $cmd = quoteCommand($config->svn." cat $revstr ".$this->repConfig->svnParams().quote($path)." 2>&1", false);
            
            if (!($result = popen($cmd, "r")))
               return;
              
            $pre = true;
         }
          
         if ($pre)
            echo "<PRE>";
            
   		while (!feof($result))
   		{
   		   $line = fgets($result, 1024);
   		   if ($pre) $line = replaceEntities($line, $this->repConfig);
   		   
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
      
      $path = encodepath($this->repConfig->path.$path);
      $cmd = quoteCommand($config->svn." blame $revstr ".$this->repConfig->svnParams().quote($path)." > $filename", false);
      
      @exec($cmd);
  }

   function getProperty($path, $property, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      $path = encodepath($this->repConfig->path.$path);
      $ret = runCommand($config->svn." propget $property $revstr ".$this->repConfig->svnParams().quote($path), true);
      
      // Remove the surplus newline
      if (count($ret))
         unset($ret[count($ret) - 1]);
      
      return implode("\n", $ret);
   }

   // exportDirectory
   //
   // Exports the directory to the given location
   
   function exportDirectory($path, $filename, $rev = 0)
   {
      global $config;
      
      $revstr = _revStr($rev);
      
      $path = encodepath($this->repConfig->path.$path);
      $cmd = quoteCommand($config->svn." export $revstr ".$this->repConfig->svnParams().quote($path)." ".quote($filename), false);

      @exec($cmd);
   }
  
   function getLog($path, $brev = "", $erev = 1, $quiet = false, $limit = 2)
   {
      global $config, $curLog, $vars, $lang;
      
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

      if (($config->subversionMajorVersion > 1 || $config->subversionMinorVersion >=2) && $limit != 0)
         $revStr .= " --limit $limit";
         
      // Get the log info
      $path = encodepath($this->repConfig->path.$path);
      $info = "--verbose";
      if ($quiet)
         $info = "--quiet";
         
      $cmd = quoteCommand($config->svn." log --xml $info $revStr ".$this->repConfig->svnParams().quote($path), false);

      if ($handle = popen($cmd, "r"))
      {
         $firstline = true;
   		while (!feof($handle))
   		{
   		   $line = fgets($handle);
   		   if (!xml_parse($xml_parser, $line, feof($handle)))
            {
               if (xml_get_error_code($xml_parser) != 5)
               {               
                  die(sprintf("XML error: %s (%d) at line %d column %d byte %d<br>cmd: $cmd<nr>",
                              xml_error_string(xml_get_error_code($xml_parser)),
                              xml_get_error_code($xml_parser),
                              xml_get_current_line_number($xml_parser),
                              xml_get_current_column_number($xml_parser),
                              xml_get_current_byte_index($xml_parser)));
               }
               else
               {
                  $vars["error"] = $lang["UNKNOWNREVISION"];
                  return 0;
               }
            }
   		}
		
		   pclose($handle);
      }
            
      xml_parser_free($xml_parser);
      return $curLog;
   }     
}

function initSvnVersion(&$major, &$minor)
{
   global $config;
   
   $ret = runCommand($config->svn_noparams." --version", false);

   if (preg_match("~([0-9]?)\.([0-9]?)\.([0-9]?)~",$ret[0],$matches))
   {
      $major = $matches[1];
      $minor = $matches[2];
   }
   
   $config->setSubversionMajorVersion($major);
   $config->setSubversionMinorVersion($minor);
}

?>
