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
// log.php
//
// Show the logs for the given path

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require_once("include/utils.inc");
require_once("include/template.inc");
require_once("include/bugtraq.inc");

$page = (int)@$_REQUEST["page"];
$all = (@$_REQUEST["all"] == 1)?1:0;
$isDir = (@$_REQUEST["isdir"] == 1)?1:0;
$dosearch = (@$_REQUEST["logsearch"] == 1)?1:0;
$search = trim(@$_REQUEST["search"]);
$words = explode(" ", $search);

$fromRev = (int)@$_REQUEST["fr"];

// Max number of results to find at a time
$numSearchResults = 10;

if ($search == "")
   $dosearch = false;   

// removeAccents
//
// Remove all the accents from a string.  This function doesn't seem
// ideal, but expecting everyone to install 'unac' seems a little
// excessive as well...

function removeAccents($string)
{ 
   return strtr($string,
                "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
                "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn"); 
} 

// Normalise the search words
foreach ($words as $index => $word)
{
   $words[$index] = strtolower(removeAccents($word));
   
   // Remove empty string introduced by multiple spaces
   if ($words[$index] == "")
      unset($words[$index]);
}

if (empty($page)) $page = 1;

// If searching, display all the results
if ($dosearch) $all = true;

$maxperpage = 20;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

$svnrep = new SVNRepository($rep->path);

$passrev = $rev;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);
$youngest = $history[0]["rev"];

if (empty($rev))
   $rev = $youngest;

if ($path == "" || $path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$vars["action"] = $lang["LOG"];
$vars["repname"] = $rep->name;
$vars["rev"] = $rev;
$vars["path"] = $ppath;

createDirLinks($rep, $ppath, $passrev, $showchanged);

$logurl = $config->getURL($rep, $path, "log");

if ($rev != $youngest)
   $vars["goyoungestlink"] = "<a href=\"${logurl}sc=1\">${lang["GOYOUNGEST"]}</a>";
else
   $vars["goyoungestlink"] = "";

// We get the bugtraq variable just once based on the HEAD
$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

$history = $svnrep->getHistory($path, $rev);

// Get the number of separate revisions
$revisions = count($history);

if ($all)
{
   $firstrevindex = 0;
   $lastrevindex = $revisions - 1;
   $pages = 1;
}
else
{
   // Calculate the number of pages
   $pages = floor($revisions / $maxperpage);
   if (($revisions % $maxperpage) > 0) $pages++;
   
   if ($page > $pages) $page = $pages;
   
   // Word out where to start and stop
   $firstrevindex = ($page - 1) * $maxperpage;
   $lastrevindex = $firstrevindex + $maxperpage - 1;
   if ($lastrevindex > $revisions - 1) $lastrevindex = $revisions - 1;
}

$vars["logsearch_moreresultslink"] = "";

$row = 0;
$index = 0;
$listing = array();
$found = false;

for ($n = $firstrevindex; $n <= $lastrevindex; $n++)
{
   $r = $history[$n];
   
   // Assume a good match
   $match = true;
   
   $log = $svnrep->getLogDetails($path, $r["rev"]);
   
   // Check the log for the search words, if searching
   if ($dosearch)
   {
      if ((empty($fromRev) || $fromRev > $r["rev"]))
      {
         // Turn all the HTML entities into real characters.  
         
         if (version_compare(phpversion(), "4.1.0", "<"))
            $msg = html_entity_decode($log["message"]);
         else
            $msg = html_entity_decode($log["message"], ENT_COMPAT, $config->outputEnc);
                  
         // Make sure that each word in the search in also in the log
         foreach($words as $word)
         {
            if (strpos(strtolower(removeAccents($msg)), $word) === false)
            {
               $match = false;
               break;
            }
         }
         
         if ($match)
         {
            $numSearchResults--;
            $found = true;
         }
      }
      else
         $match = false;
   }
   
   if ($match)
   {
      // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
      $rpath = $r["path"];
      if ($isDir && $rpath{strlen($rpath) - 1} != "/")
         $rpath .= "/";
   
      // Find the parent path (or the whole path if it's already a directory)
      $pos = strrpos($rpath, "/");
      $parent = substr($rpath, 0, $pos + 1);
   
      $url = $config->getURL($rep, $parent, "dir");
      $listing[$index]["compare_box"] = "<input type=\"checkbox\" name=\"compare[]\" value=\"$parent@${r["rev"]}\" onClick=\"checkCB(this)\">";
      $listing[$index]["revlink"] = "<a href=\"${url}rev=${r["rev"]}&amp;sc=1\">${r["rev"]}</a>";
   
      if ($isDir)
      {
         $url = $config->getURL($rep, $rpath, "dir"); 
         $listing[$index]["revpathlink"] = "<a href=\"${url}rev=${r["rev"]}&amp;sc=$showchanged\">$rpath</a>";
      }
      else
      {
         $url = $config->getURL($rep, $rpath, "file"); 
         $listing[$index]["revpathlink"] = "<a href=\"${url}rev=${r["rev"]}&amp;sc=$showchanged\">$rpath</a>";
      }
      
      $listing[$index]["revauthor"] = $log["author"];
      $listing[$index]["revage"] = $log["age"];
      $listing[$index]["revlog"] = nl2br($bugtraq->replaceIDs(create_anchors($log["message"])));
      $listing[$index]["rowparity"] = "$row";
      
      $row = 1 - $row;
      $index++;
   }
   
   // If we've reached the search limit, stop here...
   if (!$numSearchResults)
   {
      $url = $config->getURL($rep, $path, "log");
      $vars["logsearch_moreresultslink"] = "<a href=\"${url}rev=$revamp;&sc=$showchanged&amp;isdir=$isDir&logsearch=1&search=$search&fr=${r["rev"]}\">${lang["MORERESULTS"]}</a>";
      break;
   }
}

$vars["logsearch_resultsfound"] = true;

if ($dosearch && !$found)
{
   if ($fromRev == 0)
   {
      $vars["logsearch_nomatches"] = true;
      $vars["logsearch_resultsfound"] = false;
   }
   else
      $vars["logsearch_nomorematches"] = true;
}
else if ($dosearch && $numSearchResults > 0)
{
   $vars["logsearch_nomorematches"] = true;
}

// Work out the paging options

$vars["pagelinks"] = "";
$vars["showalllink"] = "";

if ($pages > 1)
{
   $prev = $page - 1;
   $next = $page + 1;
   echo "<p><center>";
      
   if ($page > 1) $vars["pagelinks"] .= "<a href=\"${logurl}rev=$rev&amp;sc=$showchanged&amp;page=$prev\"><&nbsp;${lang["PREV"]}</a> ";
   for ($p = 1; $p <= $pages; $p++)
   {
      if ($p != $page)
         $vars["pagelinks"].= "<a href=\"${logurl}rev=$rev&amp;sc=$showchanged&amp;page=$p\">$p</a> "; 
      else
         $vars["pagelinks"] .= "<b>$p </b>";
   }
   if ($page < $pages) $vars["pagelinks"] .=" <a href=\"${logurl}rev=$rev&amp;sc=$showchanged&amp;page=$next\">${lang["NEXT"]}&nbsp;></a>";   
   
   $vars["showalllink"] = "<a href=\"${logurl}rev=$rev&amp;sc=$showchanged&amp;all=1\">${lang["SHOWALL"]}</a>";
   echo "</center>";
}

// Create the project change combo box
 
$url = $config->getURL($rep, $path, "log");
$vars["logsearch_form"] = "<form action=\"$url\" method=\"post\" name=\"logsearchform\">";

$vars["logsearch_inputbox"] = "<input name=\"search\" value=\"$search\">";

$vars["logsearch_submit"] = "<input type=\"submit\" value=\"${lang["GO"]}\">";
$vars["logsearch_endform"] = "<input type=\"hidden\" name=\"logsearch\" value=\"1\">".
                             "<input type=\"hidden\" name=\"op\" value=\"log\">".
                             "<input type=\"hidden\" name=\"rev\" value=\"$rev\">".
                             "<input type=\"hidden\" name=\"sc\" value=\"$showchanged\">".
                             "<input type=\"hidden\" name=\"isdir\" value=\"$isDir\">".
                             "</form>";   

if ($search != "")
{
   $url = $config->getURL($rep, $path, "log");
   $vars["logsearch_clearloglink"] = "<a href=\"${url}rev=$rev&amp;sc=$showchanged&amp;isdir=$isDir\">${lang["CLEARLOG"]}</a>";
}
else
   $vars["logsearch_clearloglink"] = "";

$url = $config->getURL($rep, "", "comp");
$vars["compare_form"] = "<form action=\"$url\" method=\"post\" name=\"compareform\">";
$vars["compare_submit"] = "<input name=\"comparesubmit\" type=\"submit\" value=\"${lang["COMPAREREVS"]}\">";
$vars["compare_endform"] = "<input type=\"hidden\" name=\"op\" value=\"comp\"><input type=\"hidden\" name=\"sc\" value=\"$showchanged\"></form>";   

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."log.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);

?>