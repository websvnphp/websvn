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
// diff.php
//
// Show the differences between 2 revisions of a file.
//

require("include/setup.inc");
require("include/svnlook.inc");
require("include/template.inc");

$context = 5;

// hardspace
//
// Replace the spaces at the front of a line with hard spaces

$spacearray = array (
0 => "",
1 => "&nbsp;",
2 => "&nbsp;&nbsp;",
3 => "&nbsp;&nbsp;&nbsp;",
4 => "&nbsp;&nbsp;&nbsp;&nbsp;",
5 => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
6 => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
7 => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
8 => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
9 => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",
10 => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
);

function hardspace($s)
{
  global $spacearray;

  $len = strlen($s);
  $s = ltrim($s);
  $numspaces = $len - strlen($s);
  $spaces = "";
  while ($numspaces >= 10)
  {
     $spaces .= $spacearray[10];
     $numspaces -= 10;
  }
  $spaces .= $spacearray[$numspaces];

  return $spaces.$s;
}

$rep = (int)@$_REQUEST["rep"];
$path = escapeshellcmd(@$_REQUEST["path"]);
$rev = (int)@$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;
$all = (@$_REQUEST["all"] == 1)?1:0;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);
$youngest = $history[0]["rev"];

if (empty($rev))
   $rev = $youngest;

$history = $svnrep->getHistory($path, $rev);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$prevrev = @$history[1]["rev"];

$vars["repname"] = $repname;
$vars["rev"] = $rev;
$vars["path"] = $ppath;
$vars["prevrev"] = $prevrev;

$vars["rev1"] = $history[0]["rev"];
$vars["rev2"] = $prevrev;

$listing = array();

if ($prevrev)
{
   $url = $config->getURL($rep, $path, "diff");
   
   if (!$all)
   {
      $vars["showalllink"] = "<a href=\"${url}rev=$rev&sc=$showchanged&all=1\">${lang["SHOWALL"]}</a>";
      $vars["showcompactlink"] = "";
   }
   else
   {
      $vars["showcompactlink"] = "<a href=\"${url}rev=$rev&sc=$showchanged&all=0\">${lang["SHOWCOMPACT"]}</a>";
      $vars["showalllink"] = "";
   }

   // Get the contents of the two files
   $newtname = tempnam("temp", "");
   $new = $svnrep->getFileContents($path, $newtname, $history[0]["rev"]);

   $oldtname = tempnam("temp", "");
   $old = $svnrep->getFileContents($path, $oldtname, $history[1]["rev"]);
   
   $file1cache = array();

   if (!$all)
   {
      // Open a pipe to the diff command with $context lines of context
      if ($diff = popen($config->diff." --ignore-space-change -U $context $oldtname $newtname", "r"))
      {
         // Ignore the 3 header lines
  		   $line = fgets($diff);
  		   $line = fgets($diff);

         // Get the first real line
  		   $line = fgets($diff);
         
         $index = 0;
         $listing = array();
         
   		while (!feof($diff))
   		{  
   		   // Get the first line of this range
   		   sscanf($line, "@@ -%d", $oline);
   		   
   		   $line = substr($line, strpos($line, "+"));
   		   sscanf($line, "+%d", $nline);
   		   
   		   // Output the line numbers
   		   $listing[$index]["rev1lineno"] = "$oline";
   		   $listing[$index]["rev2lineno"] = "$nline";
   		   $index++;
   		   
            $fin = false;
            while (!feof($diff) && !$fin)
            {          
      		   $listing[$index]["rev1lineno"] = 0;
      		   $listing[$index]["rev2lineno"] = 0;
  
   		      $line = fgets($diff);
               if (!strncmp($line, "@@", 2))
   		      {
   		         $fin = true;
   		      }
   		      else
   		      {
                  $mod = $line{0};
                  
                  $text = hardspace(transChars(rtrim(substr($line, 1)), ($config->useEnscript)?false:true));
                  
                  switch ($mod)
                  {
                     case "-":
                        $listing[$index]["rev1diffclass"] = "diffdeleted";
                        $listing[$index]["rev2diffclass"] = "diff";
                        
                        $listing[$index]["rev1line"] = $text;
                        $listing[$index]["rev2line"] = "&nbsp;";
                        break;  

                     case "+":
                        $listing[$index]["rev1diffclass"] = "diff";
                        $listing[$index]["rev2diffclass"] = "diffadded";
                        
                        $listing[$index]["rev1line"] = "&nbsp;";
                        $listing[$index]["rev2line"] = $text;
                        break;
                        
                     default:
                        $listing[$index]["rev1diffclass"] = "diff";
                        $listing[$index]["rev2diffclass"] = "diff";
                        
                        $listing[$index]["rev1line"] = $text;
                        $listing[$index]["rev2line"] = $text;
                        break;                         		
                  }
   		      }
   		      
   		      $index++;
   		   }
   		}   
   		
   		pclose($diff);   
      }		   
   }
   else
   {
      $index = 0;
      $listing = array();

      // Get the diff  output
      if ($diff = popen($config->diff." -y -t -W 600 -w $oldtname $newtname", "r"))
      {
         while (!feof($diff))
         {
            $output = fgets($diff);         
          
            // Get each file's line
            if (!empty($output))
            {
               // Since we've asked for a 600 column output, the mod indicator is on the 300th or 301th column
               // (I've no idea why it changes).
               
               $mod = "";
               $len = strlen($output);
               if ($len >= 300)
               {
                  $mod = $output{299};
                  if ($mod == " " && $len >= 301) $mod = $output{300};
                  if ($mod == " " && $len >= 302) $mod = $output{301};
               }
      
               $oldline = hardspace(transChars(rtrim(substr($output, 0, 299)), ($config->useEnscript)?false:true));
               $newline = hardspace(transChars(rtrim(substr($output, 302)), ($config->useEnscript)?false:true));
            
               if ($oldline == "") $oldline = "&nbsp;";
               if ($newline == "") $newline = "&nbsp;";
               
               $listing[$index]["rev1diffclass"] = "diff";
               $listing[$index]["rev2diffclass"] = "diff";
               
               if ($mod == "<") $listing[$index]["rev1diffclass"] = "diffdeleted";
               else if ($mod == ">") $listing[$index]["rev2diffclass"] ="diffadded";
               else if ($mod == "|") $listing[$index]["rev1diffclass"] = $listing[$index]["rev2diffclass"] ="diffchanged";
               
               $listing[$index]["rev1line"] = $oldline;
               $listing[$index]["rev2line"] = $newline;
            }
            
            $index++;
         }      
      }
   }
   
   // Remove our temporary files   
   unlink($oldtname);
   unlink($newtname);
}
else
{
   $vars["noprev"] = 1;
}

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."diff.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);
   
?>