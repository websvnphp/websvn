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

require("include/config.inc");
require("include/svnlook.inc");

include("templates/header.php");

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

$rep = @$_REQUEST["rep"];
$path = @$_REQUEST["path"];
$rev = @$_REQUEST["rev"];
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
$log = $svnrep->getLogDetails($path, $rev);
$history = $svnrep->getHistory($path, $rev);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$prevrev = @$history[1]["rev"];
if ($prevrev)
{
   echo "<h1>$repname - $ppath - ${lang["DIFFREVS"]} ".$history[0]["rev"]." ${lang["AND"]} ".$history[1]["rev"]."</h1>";
   echo "<p>";
   
   if (!$all)
   {
      echo "<p><center><a href=\"diff.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&all=1\">${lang["SHOWALL"]}</a></center>";
   }
   else
   {
      echo "<p><center><a href=\"diff.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&all=0\">${lang["SHOWCOMPACT"]}</a></center>";
   }

   echo "<p>";
   
   echo "<table class=\"diff\" width=\"100%\"><tr><td style=\"padding-bottom: 5px\" width=\"50%\"><b>${lang["REV"]} ".$history[1]["rev"]."</b></td>".
        "<td rowspan=100000 width=5></td>".
        "<td style=\"padding-bottom: 5px\" width=\"50%\"><b>${lang["REV"]} ".$history[0]["rev"]."</b></td></tr>";

   // Get the contents of the two files
   $newtname = tempnam("temp", "");
   $new = $svnrep->getFileContents($path, $newtname, $history[0]["rev"]);

   $oldtname = tempnam("temp", "");
   $old = $svnrep->getFileContents($path, $oldtname, $history[1]["rev"]);
   
   $file1cache = array();

   if (!$all)
   {
      // Open a pipe to the diff command with $context lines of context
      if ($diff = popen($config->diff." -U $context $oldtname $newtname", "r"))
      {
         // Ignore the 3 header lines
  		   $line = fgets($diff);
  		   $line = fgets($diff);

         // Get the first real line
  		   $line = fgets($diff);
         
   		while (!feof($diff))
   		{  
   		   // Get the first line of this range
   		   sscanf($line, "@@ -%d", $oline);
   		   
   		   $line = substr($line, strpos($line, "+"));
   		   sscanf($line, "+%d", $nline);
   		   
   		   // Output the line numbers
            echo "<tr><td style=\"padding: 3px 0 3px 0\" align=\"center\"><b>${lang["LINE"]} $oline...</b></td><td style=\"padding: 3px 0 3px 0\" align=\"center\"><b>${lang["LINE"]} $nline...</b></td></tr>";

            $fin = false;
            while (!feof($diff) && !$fin)
            {          
  
   		      $line = fgets($diff);
               if (!strncmp($line, "@@", 2))
   		      {
   		         $fin = true;
   		      }
   		      else
   		      {
                  $mod = $line{0};
                  $lclass = $rclass = "class=\"diff\"";
                  
                  $text = hardspace(htmlspecialchars(rtrim(substr($line, 2))));
                  
                  switch ($mod)
                  {
                     case "-":
                        $lclass = "class=\"diffdeleted\"";
                        echo "<tr><td $lclass>$text</td><td $rclass>&nbsp;</td>";
                        break;  

                     case "+":
                        $rclass = "class=\"diffadded\"";
                        echo "<tr><td $lclass>&nbsp;</td><td $rclass>$text</td>";
                        break;
                        
                     default:
                        echo "<tr><td $lclass>$text</td><td $rclass>$text</td>";
                        break;                         		
                  }
   		      }
   		   }
   		}   
   		
   		pclose($diff);   
      }		   
   }
   else
   {
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
      
               $oldline = hardspace(htmlspecialchars(rtrim(substr($output, 0, 299))));
               $newline = hardspace(htmlspecialchars(rtrim(substr($output, 302))));
            
               if ($oldline == "") $oldline = "&nbsp;";
               if ($newline == "") $newline = "&nbsp;";
               
               $lclass = $rclass = "class=\"diff\"";
               if ($mod == "<") $lclass = "class=\"diffdeleted\"";
               else if ($mod == ">") $rclass = "class=\"diffadded\"";
               else if ($mod == "|") $lclass = $rclass = "class=\"diffchanged\"";
               
               echo "<tr><td $lclass>$oldline</td><td $rclass>$newline</td></tr>\n";
            }
         }      
      }
   }
   
   // Remove our temporary files   
   unlink($oldtname);
   unlink($newtname);
   
   echo "</table>";
}
else
   echo "No previous revision";
   
include("templates/footer.php");

?>