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

require("config.inc");
require("svnlook.inc");

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
   
   // Get the contents of the two files
   $new = $svnrep->getFileContents($path, $history[0]["rev"]);
   $old = $svnrep->getFileContents($path, $history[1]["rev"]);

   // Save these into temp files so that we can operate on them
   $oldtname = tempnam("temp", "");
   $fp = fopen($oldtname, "w");
   fwrite($fp, $old);
   fclose($fp);

   $newtname = tempnam("temp", "");
   $fp = fopen($newtname, "w");
   fwrite($fp, $new);
   fclose($fp);
   
   // Get the diff  output
   $output = runCommand($config->diffCommandPath."diff -y -t -W 600 -w $oldtname $newtname");
      
   // Remove our temporary files   
   unlink($oldtname);
   unlink($newtname);

   if (!$all)
   {
      echo "<p><center><a href=\"diff.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&all=1\">${lang["SHOWALL"]}</a></center>";
   }
   else
   {
      echo "<p><center><a href=\"diff.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged&all=0\">${lang["SHOWCOMPACT"]}</a></center>";
   }
   
   echo "<p>";
   
   // Split this data up into an array
   $output = explode("\n", $output);

   echo "<table class=\"diff\" width=\"100%\"><tr><td style=\"padding-bottom: 5px\" width=\"50%\"><b>${lang["REV"]} ".$history[1]["rev"]."</b></td>".
        "<td rowspan=100000 width=5></td>".
        "<td style=\"padding-bottom: 5px\" width=\"50%\"><b>${lang["REV"]} ".$history[0]["rev"]."</b></td></tr>";
   
   // Output the differences with 5 lines of context
   $outputline = array ();
   
   foreach ($output as $lineno => $line)
   {
      // Since we've asked for a 600 column output, the mod indicator is on the 300th or 301th column
      // (I've no idea why it changes).
      
      $mod = "";
      $len = strlen($line);
      if ($len >= 300)
      {
         $mod = $line{299};
         if ($mod == " " && $len >= 301) $mod = $line{300};
      }
      
      if ($all)
      {
         $outputline[$lineno] = $mod;
      }
      else if ($mod != " " && $mod != "")
      {
         for ($l = $lineno - $context; $l < $lineno + $context; $l++)
         {
            if (empty($outputline[$l]))
               $outputline[$l] = "=";
         }
         $outputline[$lineno] = $mod;
      }
   }

   $curline = 1;
   foreach ($outputline as $line => $mod)
   {
      if ($curline != $line)
      {
         echo "<tr><td colspan=3 style=\"padding: 3px 0 3px 0\" align=\"center\"><b>${lang["LINE"]} ".($line + 1)."...</b></td></tr>";
         $curline = $line;
      }
 
      // Get each file's line
      if (!empty($output[$line]))
      {
         $oldline = hardspace(htmlspecialchars(rtrim(substr($output[$line], 0, 299))));
         $newline = hardspace(htmlspecialchars(rtrim(substr($output[$line], 301))));
      
         if ($oldline == "") $oldline = "&nbsp;";
         if ($newline == "") $newline = "&nbsp;";
         
         $lclass = $rclass = "class=\"diff\"";
         if ($mod == "<") $lclass = "class=\"diffdeleted\"";
         else if ($mod == ">") $rclass = "class=\"diffadded\"";
         else if ($mod == "|") $lclass = $rclass = "class=\"diffchanged\"";
         
         echo "<tr><td $lclass>$oldline</td><td $rclass>$newline</td></tr>\n";
      }
      
      $curline++;
   }
   
   echo "</table>";
}
else
   echo "No previous revision";
   
include("templates/footer.php");

?>