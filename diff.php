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

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");

$context = 5;

$vars["action"] = $lang["DIFF"];
$all = (@$_REQUEST["all"] == 1)?1:0;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

$svnrep = new SVNRepository($rep->path);

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

$vars["repname"] = $rep->name;
$vars["rev"] = $rev;
$vars["path"] = $ppath;
$vars["prevrev"] = $prevrev;

$vars["rev1"] = $history[0]["rev"];
$vars["rev2"] = $prevrev;

createDirLinks($rep, $ppath, $rev, $showchanged);

$listing = array();

if ($prevrev)
{
   $url = $config->getURL($rep, $path, "diff");
   
   if (!$all)
   {
      $vars["showalllink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged&amp;all=1\">${lang["SHOWENTIREFILE"]}</a>";
      $vars["showcompactlink"] = "";
   }
   else
   {
      $vars["showcompactlink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged&amp;all=0\">${lang["SHOWCOMPACT"]}</a>";
      $vars["showalllink"] = "";
   }

   // Get the contents of the two files
   $newtname = tempnam("temp", "");
   $new = $svnrep->getFileContents($history[0]["path"], $newtname, $history[0]["rev"]);

   $oldtname = tempnam("temp", "");
   $old = $svnrep->getFileContents($history[1]["path"], $oldtname, $history[1]["rev"]);
   
   $ent = true;
   if ((strrchr($path, ".") == '.php') || ($config->useEnscript))
      $ent = false;

   $file1cache = array();

   if ($all)
      $context = 1;  // Setting the context to 0 makes diff generate the wrong line numbers!

   // Open a pipe to the diff command with $context lines of context   
   
   $cmd = quoteCommand($config->diff." --ignore-all-space -U $context $oldtname $newtname", false);
   
   if ($all)
   {
      $ofile = fopen($oldtname, "r");
      $nfile = fopen($newtname, "r");      
   }

   if ($diff = popen($cmd, "r"))
   {
      // Ignore the 3 header lines
	   $line = fgets($diff);
	   $line = fgets($diff);

      // Get the first real line
	   $line = fgets($diff);
      
      $index = 0;
      $listing = array();
      
      $curoline = 1;
      $curnline = 1;
      
		while (!feof($diff))
		{  
		   // Get the first line of this range
		   sscanf($line, "@@ -%d", $oline);
		   
		   $line = substr($line, strpos($line, "+"));
		   sscanf($line, "+%d", $nline);
		   
		   if ($all)
		   {
		      while ($curoline < $oline || $curnline < $nline)
		      {
		         $listing[$index]["rev1diffclass"] = "diff";
               $listing[$index]["rev2diffclass"] = "diff";
                     
               if ($curoline < $oline)
               {
                  $nl = fgets($ofile);
                  
                  if ($ent)
                     $line = replaceEntities(rtrim($nl));
                  else
                     $line = rtrim($nl);
                     
                  $listing[$index]["rev1line"] = hardspace($line);

                  $curoline++;
               }
               else
                  $listing[$index]["rev1line"] = "&nbsp;";
                  
               if ($curnline < $nline)
               {
                  $nl = fgets($nfile);

                  if ($ent)
                     $line = replaceEntities(rtrim($nl));
                  else
                     $line = rtrim($nl);
                     
                  $listing[$index]["rev1line"] = hardspace($line);
                  $curnline++;
               }
               else
                  $listing[$index]["rev2line"] = "&nbsp;";
                  
      		   $listing[$index]["rev1lineno"] = 0;
      		   $listing[$index]["rev2lineno"] = 0;

		         $index++;
		      }
		   }
		   else
		   {
   		   // Output the line numbers
   		   $listing[$index]["rev1lineno"] = "$oline";
   		   $listing[$index]["rev2lineno"] = "$nline";
   		   $index++;
		   }
		   
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

               if ($ent)
                  $line = replaceEntities(rtrim(substr($line, 1)));
               else
                  $line = rtrim(substr($line, 1));
                  
               $listing[$index]["rev1line"] = hardspace($line);

               $text = hardspace($line);
               if ($text == "") $text = "&nbsp;";
               
               switch ($mod)
               {
                  case "-":
                     $listing[$index]["rev1diffclass"] = "diffdeleted";
                     $listing[$index]["rev2diffclass"] = "diff";
                     
                     $listing[$index]["rev1line"] = $text;
                     $listing[$index]["rev2line"] = "&nbsp;";
                     
                     if ($all)
                     {
                        fgets($ofile);
                        $curoline++;
                     }
                     
                     break;  

                  case "+":
                     
                     // Try to mark "changed" line sensibly
                     if (!empty($listing[$index-1]) && empty($listing[$index-1]["rev1lineno"]) && $listing[$index-1]["rev1diffclass"] == "diffdeleted" && $listing[$index-1]["rev2diffclass"] == "diff")
                     {
                        $i = $index - 1;
                        while (!empty($listing[$i-1]) && empty($listing[$i-1]["rev1lineno"]) && $listing[$i-1]["rev1diffclass"] == "diffdeleted" && $listing[$i-1]["rev2diffclass"] == "diff")
                           $i--;
                           
                        $listing[$i]["rev1diffclass"] = "diffchanged";
                        $listing[$i]["rev2diffclass"] = "diffchanged";
                        $listing[$i]["rev2line"] = $text;
                        
                        if ($all)
                        {
                           fgets($nfile);
                           $curnline++;
                        }

                        // Don't increment the current index count
                        $index--;
                     }
                     else
                     {
                        $listing[$index]["rev1diffclass"] = "diff";
                        $listing[$index]["rev2diffclass"] = "diffadded";
                        
                        $listing[$index]["rev1line"] = "&nbsp;";
                        $listing[$index]["rev2line"] = $text;

                        if ($all)
                        {
                           fgets($nfile);
                           $curnline++;
                        }
                     }
                     break;
                     
                  default:
                     $listing[$index]["rev1diffclass"] = "diff";
                     $listing[$index]["rev2diffclass"] = "diff";
                     
                     $listing[$index]["rev1line"] = $text;
                     $listing[$index]["rev2line"] = $text;
                     
                     if ($all)
                     {
                        fgets($ofile);
                        fgets($nfile);
                        $curoline++;
                        $curnline++;
                     }

                     break;                         		
               }
		      }
		      
		      if (!$fin)
		         $index++;
		   }
		}   
		
		// Output the rest of the files
	   if ($all)
	   {
	      while (!feof($ofile) || !feof($nfile))
	      {
	         $listing[$index]["rev1diffclass"] = "diff";
            $listing[$index]["rev2diffclass"] = "diff";
                  
            if ($ent)
               $line = replaceEntities(rtrim(fgets($ofile)));
            else
               $line = rtrim(fgets($ofile));

            if (!feof($ofile))
               $listing[$index]["rev1line"] = hardspace($line);
            else
               $listing[$index]["rev1line"] = "&nbsp;";
               
            if ($ent)
               $line = replaceEntities(rtrim(fgets($nfile)));
            else
               $line = rtrim(fgets($nfile));

            if (!feof($nfile))
               $listing[$index]["rev2line"] = hardspace($line);
            else
               $listing[$index]["rev2line"] = "&nbsp;";
               
   		   $listing[$index]["rev1lineno"] = 0;
   		   $listing[$index]["rev2lineno"] = 0;

	         $index++;
	      }
	   }
		
		pclose($diff);   
   }		   
   
   if ($all)
   {
      fclose($ofile);
      fclose($nfile);      
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