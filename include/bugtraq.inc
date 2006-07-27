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
// bugtraq.inc
//
// Functions for accessing the bugtraq properties and replacing issue IDs
// with URLs.
//
// For more information about bugtraq, see 
// http://svn.collab.net/repos/tortoisesvn/trunk/doc/issuetrackers.txt

class Bugtraq
{
   var $msgstring;
   var $urlstring;
   
   var $firstPart;
   var $firstPartLen;
   var $lastPart;
   var $lastPartLen;
   
   var $propsfound = false;
   
   function Bugtraq($rep, $svnrep, $path)
   {
      global $config;
            
      if ($rep->getBugtraq())
      {
         $pos = strrpos($path, "/");
         $parent = substr($path, 0, $pos + 1);
      
         while ((empty($this->msgstring) || empty($this->urlstring)) && (strpos($parent, "/") !== false))
         {
            if (empty($this->msgstring)) $this->msgstring = $svnrep->getProperty($parent, 'bugtraq:message');
            if (empty($this->urlstring)) $this->urlstring = $svnrep->getProperty($parent, 'bugtraq:url');
                  
            $parent = substr($parent, 0, -1);        // Remove the trailing slash
            $pos = strrpos($parent, "/");            // Find the last trailing slash
            $parent = substr($parent, 0, $pos + 1);  // Find the previous parent directory      
         }
         
         if (!empty($this->msgstring) && !empty($this->urlstring))
         {
            $this->msgstring = trim($this->msgstring);
            $this->urlstring = trim($this->urlstring);
            
            if (($bugidpos = strpos($this->msgstring, "%BUGID%")) !== false && strpos($this->urlstring, "%BUGID%") !== false)
            {
               // Get the textual parts of the message string for comparison purposes
               $this->firstPart = substr($this->msgstring, 0, $bugidpos);
               $this->firstPartLen = strlen($this->firstPart);
               $this->lastPart = substr($this->msgstring, $bugidpos + 7);
               $this->lastPartLen = strlen($this->lastPart);
            }
            
            $this->propsfound = true;
         }
      }
   }

   function replaceIDs($message)
   {
      if ($this->propsfound)
      {
         $logmsg  = "";
         $message = rtrim($message);
         
         // Just compare the last line            
         if ($offset = strrpos($message, "\n"))
         {
            $logmsg = substr($message, 0, $offset + 1);
            $lastLine = substr($message, $offset + 1);
         }
         else
            $lastLine = $message;
            
         // Make sure that our line really is an issue tracker message
         if (((strncmp($lastLine, $this->firstPart, $this->firstPartLen) == 0) || (($offset !== false) && ($logmsg == ""))) &&
             strcmp(substr($lastLine, -$this->lastPartLen, $this->lastPartLen), $this->lastPart) == 0)
         {
            // Get the issues list
            if ($this->lastPartLen > 0)
               $issues = substr($lastLine, $this->firstPartLen, -$this->lastPartLen);
            else
               $issues = substr($lastLine, $this->firstPartLen);
            
            // Add each reference to the first part of the line
            $line = $this->firstPart;
            while ($pos = strpos($issues, ","))
            {
               $issue  = trim(substr($issues, 0, $pos));
               $issues = substr($issues, $pos + 1);
               
               $line .= "<a href=\"".str_replace("%BUGID%", $issue, $this->urlstring)."\">$issue</a>, ";
            }
            $line .= "<a href=\"".str_replace("%BUGID%", $issues, $this->urlstring)."\">".trim($issues)."</a>".$this->lastPart;
            
            return $logmsg.$line;
         }
      }
      
      return $message;
   }
}
?>
