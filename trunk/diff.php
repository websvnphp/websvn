<?php
// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004-2006 Tim Armes
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

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");
require_once("include/template.php");

$context = 5;

$vars["action"] = $lang["DIFF"];
$all = (@$_REQUEST["all"] == 1)?1:0;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

$svnrep = new SVNRepository($rep);

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, "", "", true);
$youngest = $history->entries[0]->rev;

if (empty($rev))
   $rev = $youngest;

$history = $svnrep->getLog($path, $rev);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$prevrev = @$history->entries[1]->rev;

$vars["repname"] = htmlentities($rep->getDisplayName(), ENT_QUOTES, 'UTF-8');
$vars["rev"] = $rev;
$vars["path"] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');
$vars["prevrev"] = $prevrev;

$vars["rev1"] = $history->entries[0]->rev;
$vars["rev2"] = $prevrev;

createDirLinks($rep, $ppath, $rev, $showchanged);

$listing = array();

$url = $config->getURL($rep, $path, "file");
if ($rev != $youngest)
   $vars["goyoungestlink"] = "<a href=\"${url}sc=1\">${lang["GOYOUNGEST"]}</a>";
else
   $vars["goyoungestlink"] = "";

$vars['indexurl'] = $config->getURL($rep, '', 'index').'sc='.$showchanged;

$url = $config->getURL($rep, $path, "file");
$vars["filedetaillink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged&amp;isdir=0\">${lang["FILEDETAIL"]}</a>";

$url = $config->getURL($rep, $path, "log");
$vars["fileviewloglink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged&amp;isdir=0\">${lang["VIEWLOG"]}</a>";

$url = $config->getURL($rep, $path, "diff");
$vars["prevdifflink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged\">${lang["DIFFPREV"]}</a>";

$url = $config->getURL($rep, $path, "blame");
$vars["blamelink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged\">${lang["BLAME"]}</a>";

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
   $new = $svnrep->getFileContents($history->entries[0]->path, $newtname, $history->entries[0]->rev, "", true);

   $oldtname = tempnam("temp", "");
   $old = $svnrep->getFileContents($history->entries[1]->path, $oldtname, $history->entries[1]->rev, "", true);
   
   $ent = true;
   $extension = strrchr(basename($path), ".");
   if (($extension && isset($extEnscript[$extension]) && ('php' == $extEnscript[$extension])) || ($config->useEnscript))
      $ent = false;

   if ($all)
      $context = 1;  // Setting the context to 0 makes diff generate the wrong line numbers!

   // Open a pipe to the diff command with $context lines of context   
   
   $cmd = quoteCommand($config->diff." -w -U $context $oldtname $newtname");
   
   if ($all)
   {
      $ofile = fopen($oldtname, "r");
      $nfile = fopen($newtname, "r");
   }
   
   $descriptorspec = array (
      0 => array('pipe', 'r'),
      1 => array('pipe', 'w'),
      2 => array('pipe', 'w')
   );

   $resource = proc_open($cmd, $descriptorspec, $pipes);
   $error = "";
   
   if (is_resource($resource))
   {
      // We don't need to write
      fclose($pipes[0]);
      
      $diff = $pipes[1];

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
                  
                  $line = rtrim($nl);
                  if ($ent) $line = replaceEntities($line, $rep);
                  
                  $listing[$index]["rev1line"] = hardspace($line);
                  
                  $curoline++;
               }
               else
                  $listing[$index]["rev1line"] = "&nbsp;";
                  
               if ($curnline < $nline)
               {
                  $nl = fgets($nfile);
                  
                  $line = rtrim($nl);
                  if ($ent) $line = replaceEntities($line, $rep);
                  
                  $listing[$index]["rev2line"] = hardspace($line);
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
            $listing[$index]["rev1lineno"] = $oline;
            $listing[$index]["rev2lineno"] = $nline;
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
               
               $line = rtrim(substr($line, 1));
               if ($ent) $line = replaceEntities($line, $rep);
               
               if ($line == '') $line = '&nbsp;';
               $listing[$index]["rev1line"] = hardspace($line);
               
               $text = hardspace($line);
               
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
                     if (!empty($listing[$index-1]) && empty($listing[$index-1]["rev1lineno"]) && @$listing[$index-1]["rev1diffclass"] == "diffdeleted" && @$listing[$index-1]["rev2diffclass"] == "diff")
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
            
            $line = rtrim(fgets($ofile)); 
            if ($ent) $line = replaceEntities($line, $rep);
            
            if (!feof($ofile))
               $listing[$index]["rev1line"] = hardspace($line);
            else
               $listing[$index]["rev1line"] = "&nbsp;";
            
            $line = rtrim(fgets($nfile));
            if ($ent) $line = replaceEntities(rtrim(fgets($nfile)), $rep);
            
            if (!feof($nfile))
               $listing[$index]["rev2line"] = hardspace($line);
            else
               $listing[$index]["rev2line"] = "&nbsp;";
            
            $listing[$index]["rev1lineno"] = 0;
            $listing[$index]["rev2lineno"] = 0;
            
            $index++;
         }
      }
      
      fclose($pipes[1]);
      
      while (!feof($pipes[2]))
      {
         $error .= fgets($pipes[2]);
      }

      $error = toOutputEncoding(trim($error));
      
      if (!empty($error))
         $error = "<p>".$lang['BADCMD'].": <code>".$cmd."</code></p><p>".nl2br($error)."</p>";
      
      fclose($pipes[2]);
      
      proc_close($resource);
   }
   else
      $error = "<p>".$lang['BADCMD'].": <code>".$cmd."</code></p>";
   
   
   if (!empty($error))
   {
      echo $error;
      
      if (is_resource($resource))
      {
         fclose($pipes[0]);
         fclose($pipes[1]);
         fclose($pipes[2]);
         
         proc_close($resource);
      }
      exit;
   }		   
   
   if ($all)
   {
      fclose($ofile);
      fclose($nfile);
   }

   // Remove our temporary files
   @unlink($oldtname);
   @unlink($newtname);
}
else
{
   $vars["noprev"] = 1;
   $url = $config->getURL($rep, $path, "file");
   $vars["filedetaillink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged&amp\">${lang["SHOWENTIREFILE"]}.</a>";
}

$vars["version"] = $version;

if (!$rep->hasReadAccess($path, false))
   $vars["noaccess"] = true;

parseTemplate($rep->getTemplatePath()."header.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."diff.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."footer.tmpl", $vars, $listing);

?>
