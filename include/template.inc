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
// templates.inc
//
// Templating system to allow advanced page customisation

$ignore = false;

// Stack of previous test results
$ignorestack = array();

// Number of test levels currently ignored
$ignorelevel = 0;

// parseCommand
//
// Parse a special command

function parseCommand($line, $vars, $handle)
{ 
   global $ignore, $ignorestack, $ignorelevel;
   
   // Check for test conditions      
   if (strncmp(trim($line), "[websvn-test:", 13) == 0)
   {
      if (!$ignore)
      {
         $line = trim($line);
         $var = substr($line, 13, -1);
         if (empty($vars[$var]))
         {
            array_push($ignorestack, $ignore);
            $ignore = true;
         }
      }
      else
      {
         $ignorelevel++;
      }
      
      return true;
   }
   
   if (strncmp(trim($line), "[websvn-else]", 13) == 0)
   {
      if ($ignorelevel == 0)
      {
         $ignore = !$ignore;
      }
         
      return true;
   }

   if (strncmp(trim($line), "[websvn-endtest]", 16) == 0)
   {
      if ($ignorelevel > 0)
      {
         $ignorelevel--;
      }
      else
      {
         $ignore = array_pop($ignorestack);
      }
      
      return true;
   }
   
   if (strncmp(trim($line), "[websvn-getlisting]", 19) == 0)
   {
      global $path, $rev, $svnrep;
   
      if (!$ignore)
         $svnrep->listFileContents($path, $rev);
      
      return true;
   }
   
   if (strncmp(trim($line), "[websvn-defineicons]", 19) == 0)
   {
      global $icons;
      
      if (!isset($icons))
         $icons = array();
      
      // Read all the lines until we reach the end of the definition, storing
      // each one...
      
      if (!$ignore)
      {
         while (!feof($handle))
         {
            $line = trim(fgets($handle));
             
            if (strncmp($line, "[websvn-enddefineicons]", 22) == 0)
            {
               return true; 
            }
               
            $eqsign = strpos($line, "=");
            
            $match = substr($line, 0, $eqsign);
            $def = substr($line, $eqsign + 1);
            
            $icons[$match] = $def;
         }
      }
      
      return true;
   }

   if (strncmp(trim($line), "[websvn-icon]", 13) == 0)
   {
      global $icons, $vars;
   
      if (!$ignore)
      {
         // The current filetype should be defined my $vars["filetype"]
      
         if (!empty($icons[$vars["filetype"]]))
         {
            echo parseTags($icons[$vars["filetype"]], $vars);
         }
         else if (!empty($icons["*"]))
         {
            echo parseTags($icons["*"], $vars);
         }
      }
      
      return true;
   }
      
   return false;
}

// parseTemplate
//
// Parse the given template, replacing the variables with the values passed

function parseTemplate($template, $vars, $listing)
{
   global $ignore, $vars;
   
   if (!is_file($template))
   {
     print "No template file found ($template)";
     exit;
   }

   $handle = fopen($template, "r");
   $inListing = false;
   $ignore = false;
   $listLines = array();
   
   while (!feof($handle))
   {
      $line = fgets($handle);
         
      // Check for the end of the file list
      if ($inListing)
      {
         if (strcmp(trim($line), "[websvn-endlisting]") == 0)
         {
            $inListing = false;

            // For each item in the list
            foreach ($listing as $listvars)
            {
               // Copy the value for this list item into the $vars array
               foreach ($listvars as $id => $value)
               {
                  $vars[$id] = $value;
               }
          
               // Output the list item
               foreach ($listLines as $line)
               {
                  if (!parseCommand($line, $vars, $handle))
                  {
                     if (!$ignore)
                        print parseTags($line, $vars);
                  }
               }
            }
         }
         else
         {
            if ($ignore == false)
               $listLines[] = $line;
         }
      }
      else if (parseCommand($line, $vars, $handle))
      {
         continue;
      }
      else
      {
         // Check for the start of the file list
         if (strncmp(trim($line), "[websvn-startlisting]", 21) == 0)
         {
            $inListing = true;
         }
         else
         {
            if ($ignore == false)
               print parseTags($line, $vars);
         }
      }
   }
   
   fclose($handle);
}

// parseTags
//
// Replace all occurences of [websvn:varname] wit the give variable

function parseTags($line, $vars)
{
   global $lang;
   
   // Replace the websvn variables
   while (ereg("\[websvn:([a-zA-Z0-9_]+)\]", $line, $matches))
   {
      // Make sure that the variable exists
      if (!isset($vars[$matches[1]]))
      {
         $vars[$matches[1]] = "?${matches[1]}?";
      }
      
      $line = str_replace($matches[0], $vars[$matches[1]], $line);
   }
   
   // Replace the language strings
   while (ereg("\[lang:([a-zA-Z0-9_]+)\]", $line, $matches))
   {
      // Make sure that the variable exists
      if (!isset($lang[$matches[1]]))
      {
         $lang[$matches[1]] = "?${matches[1]}?";
      }
      
      $line = str_replace($matches[0], $lang[$matches[1]], $line);
   }

   // Return the results
   return $line;
}
?>