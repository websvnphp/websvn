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
// log.php
//
// Show the logs for the given path

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");
require_once("include/template.php");
require_once("include/bugtraq.php");

$page = (int)@$_REQUEST["page"];
$all = (@$_REQUEST["all"] == 1)?1:0;
$isDir = (@$_REQUEST["isdir"] == 1)?1:0;
$dosearch = (@$_REQUEST["logsearch"] == 1)?1:0;
$search = trim(@$_REQUEST["search"]);
$words = preg_split('#\s+#', $search);
$fromRev = (int)@$_REQUEST["fr"];
$startrev = strtoupper(trim(@$_REQUEST["sr"]));
$endrev = strtoupper(trim(@$_REQUEST["er"]));
$max = isset($_REQUEST['max']) ? (int)$_REQUEST['max'] : false;

// Max number of results to find at a time
$numSearchResults = 15;

if ($search == "") {
  $dosearch = false;
}

// removeAccents
//
// Remove all the accents from a string.  This function doesn't seem
// ideal, but expecting everyone to install 'unac' seems a little
// excessive as well...

function removeAccents($string) {
  $string = htmlentities($string, ENT_QUOTES, 'ISO-8859-1');
  $string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron);/','$1',$string);
  $string = preg_replace('/&([A-Z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron);/','$1',$string);

  return $string;
}

// Normalise the search words
foreach ($words as $index => $word) {
  $words[$index] = strtolower(removeAccents($word));

  // Remove empty string introduced by multiple spaces
  if (empty($words[$index])) unset($words[$index]);
}

if (empty($page)) $page = 1;

// If searching, display all the results
if ($dosearch) $all = true;

$maxperpage = 20;

// Make sure that we have a repository
if (!isset($rep)) {
  echo $lang["NOREP"];
  exit;
}

$svnrep = new SVNRepository($rep);

$passrev = $rev;

if ($startrev != "HEAD" && $startrev != "BASE" && $startrev != "PREV" && $startrev != "COMMITTED") {
  $startrev = (int)$startrev;
}
if ($endrev != "HEAD" && $endrev != "BASE" && $endrev != "PREV" && $endrev != "COMMITTED") {
  $endrev = (int)$endrev;
}
if (empty($startrev)) {
  $startrev = $rev;
}
if (empty($endrev)) {
  $endrev = 1;
}

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, $startrev, $endrev, true);
if (is_string($history)) {
  echo $history;
  exit;
}
$youngest = isset($history->entries[0]) ? $history->entries[0]->rev : 0;

if (empty($rev)) {
  $rev = $youngest;
}

// make sure path is prefixed by a /
$ppath = $path;
if ($path == "" || $path{0} != "/") {
  $ppath = "/".$path;
}

$vars["action"] = $lang["LOG"];
$vars["repname"] = htmlentities($rep->getDisplayName(), ENT_QUOTES, 'UTF-8');
$vars["rev"] = $rev;
$vars["path"] = htmlentities($ppath, ENT_QUOTES, 'UTF-8');

createDirLinks($rep, $ppath, $passrev);

$vars['indexurl'] = $config->getURL($rep, '', 'index');
$vars['repurl'] = $config->getURL($rep, '', 'dir');

if (!$isDir) {
  $url = $config->getURL($rep, $path, "file");
  $vars["filedetaillink"] = "<a href=\"${url}rev=$rev&amp;isdir=0\">${lang["FILEDETAIL"]}</a>";

  $url = $config->getURL($rep, $path, "diff");
  $vars["prevdifflink"] = "<a href=\"${url}rev=$rev\">${lang["DIFFPREV"]}</a>";

  $url = $config->getURL($rep, $path, "blame");
  $vars["blamelink"] = "<a href=\"${url}rev=$passrev\">${lang["BLAME"]}</a>";
}

$logurl = $config->getURL($rep, $path, "log");

if ($rev != $youngest) {
  $vars["goyoungestlink"] = "<a href=\"${logurl}isdir=$isDir\">${lang["GOYOUNGEST"]}</a>";
} else {
  $vars["goyoungestlink"] = "";
}

// We get the bugtraq variable just once based on the HEAD
$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

if ($max === false) {
  $max = $dosearch ? 0 : 30;
} else {
  if ($max < 0) $max = 30;
}

$history = $svnrep->getLog($path, $startrev, $endrev, true, $max);
if (is_string($history)) {
  echo $history;
  exit;
}
$vars["logsearch_moreresultslink"] = "";
$vars["pagelinks"] = "";
$vars["showalllink"] = "";
$listing = array();

if (!empty($history)) {
  // Get the number of separate revisions
  $revisions = count($history->entries);

  if ($all) {
    $firstrevindex = 0;
    $lastrevindex = $revisions - 1;
    $pages = 1;
  } else {
    // Calculate the number of pages
    $pages = floor($revisions / $maxperpage);
    if (($revisions % $maxperpage) > 0) $pages++;

    if ($page > $pages) $page = $pages;

    // Word out where to start and stop
    $firstrevindex = ($page - 1) * $maxperpage;
    $lastrevindex = $firstrevindex + $maxperpage - 1;
    if ($lastrevindex > $revisions - 1) $lastrevindex = $revisions - 1;
  }

  $brev = isset($history->entries[$firstrevindex ]) ? $history->entries[$firstrevindex ]->rev : false;
  $erev = isset($history->entries[$lastrevindex]) ? $history->entries[$lastrevindex]->rev : false;

  $entries = array();
  if ($brev && $erev) {
    $history = $svnrep->getLog($path, $brev, $erev, false, 0);
    if (is_string($history)) {
      echo $history;
      exit;
    }
    $entries = $history->entries;
  }

  $row = 0;
  $index = 0;
  $listing = array();
  $found = false;

  foreach ($entries as $r) {
    // Assume a good match
    $match = true;
    $thisrev = $r->rev;

    // Check the log for the search words, if searching
    if ($dosearch) {
      if ((empty($fromRev) || $fromRev > $thisrev)) {
        // Turn all the HTML entities into real characters.

        // Make sure that each word in the search in also in the log
        foreach ($words as $word) {
          if (strpos(strtolower(removeAccents($r->msg)), $word) === false && strpos(strtolower(removeAccents($r->author)), $word) === false) {
            $match = false;
            break;
          }
        }

        if ($match) {
          $numSearchResults--;
          $found = true;
        }
      } else {
        $match = false;
      }
    }

    if ($match)
    {
      // Add the trailing slash if we need to (svnlook history doesn't return trailing slashes!)
      $rpath = $r->path;

      if (empty($rpath)) {
        $rpath = "/";
      } else if ($isDir && $rpath{strlen($rpath) - 1} != "/") {
        $rpath .= "/";
      }

      // Find the parent path (or the whole path if it's already a directory)
      $pos = strrpos($rpath, "/");
      $parent = substr($rpath, 0, $pos + 1);

      $url = $config->getURL($rep, $parent, "revision");
      $listing[$index]["revlink"] = "<a href=\"${url}rev=$thisrev\">$thisrev</a>";

      if ($isDir) {
        $listing[$index]["compare_box"] = "<input type=\"checkbox\" name=\"compare[]\" value=\"$parent@$thisrev\" onclick=\"checkCB(this)\" />";
        $url = $config->getURL($rep, $rpath, "dir");
        $listing[$index]["revpathlink"] = "<a href=\"${url}rev=$thisrev\">$rpath</a>";
      } else {
        $listing[$index]["compare_box"] = "<input type=\"checkbox\" name=\"compare[]\" value=\"$rpath@$thisrev\" onclick=\"checkCB(this)\" />";
        $url = $config->getURL($rep, $rpath, "file");
        $listing[$index]["revpathlink"] = "<a href=\"${url}rev=$thisrev\">$rpath</a>";
      }

      $listing[$index]["revauthor"] = $r->author;
      $listing[$index]["date"] = $r->date;
      $listing[$index]["revage"] = $r->age;
      $listing[$index]["revlog"] = nl2br($bugtraq->replaceIDs(create_anchors($r->msg)));
      $listing[$index]["rowparity"] = $row;

      $row = 1 - $row;
      $index++;
    }

    // If we've reached the search limit, stop here...
    if (!$numSearchResults) {
      $url = $config->getURL($rep, $path, "log");
      $vars["logsearch_moreresultslink"] = "<a href=\"${url}rev=$rev&amp;isdir=$isDir&amp;logsearch=1&amp;search=$search&amp;fr=$thisrev\">${lang["MORERESULTS"]}</a>";
      break;
    }
  }

  $vars["logsearch_resultsfound"] = true;

  if ($dosearch && !$found) {
    if ($fromRev == 0) {
      $vars["logsearch_nomatches"] = true;
      $vars["logsearch_resultsfound"] = false;
    } else {
      $vars["logsearch_nomorematches"] = true;
    }
  } else if ($dosearch && $numSearchResults > 0) {
    $vars["logsearch_nomorematches"] = true;
  }

  // Work out the paging options

  if ($pages > 1) {
    $prev = $page - 1;
    $next = $page + 1;

    if ($page > 1) $vars["pagelinks"] .= "<a href=\"${logurl}rev=$rev&amp;sr=$startrev&amp;er=$endrev&amp;max=$max&amp;page=$prev\"><&nbsp;${lang["PREV"]}</a> ";
    for ($p = 1; $p <= $pages; $p++) {
      if ($p != $page) {
        $vars["pagelinks"].= "<a href=\"${logurl}rev=$rev&amp;sr=$startrev&amp;er=$endrev&amp;isdir=$isDir&amp;max=$max&amp;page=$p\">$p</a> ";
      } else {
        $vars["pagelinks"] .= "<b>$p </b>";
      }
    }
    if ($page < $pages) $vars["pagelinks"] .=" <a href=\"${logurl}rev=$rev&amp;sr=$startrev&amp;er=$endrev&amp;isdir=$isDir&amp;max=$max&amp;page=$next\">${lang["NEXT"]}&nbsp;></a>";

    $vars["showalllink"] = "<a href=\"${logurl}rev=$rev&amp;sr=$startrev&amp;er=$endrev&amp;isdir=$isDir&amp;all=1&amp;max=$max\">${lang["SHOWALL"]}</a>";
  }
}

// Create the project change combo box

$url = $config->getURL($rep, $path, "log");
// XXX: forms don't have the name attribute, but _everything_ has the id attribute,
//      so what you're trying to do (if anything?) should be done via that ~J
$vars["logsearch_form"] = "<form action=\"$url\" method=\"post\">";

$vars["logsearch_startbox"] = "<input name=\"sr\" size=\"5\" value=\"$startrev\" />";
$vars["logsearch_endbox"  ] = "<input name=\"er\" size=\"5\" value=\"$endrev\" />";
$vars["logsearch_maxbox"  ] = "<input name=\"max\" size=\"5\" value=\"".($max==0?"":$max)."\" />";
$vars["logsearch_inputbox"] = "<input name=\"search\" value=\"".htmlentities($search, ENT_QUOTES, 'UTF-8')."\" />";
$vars["logsearch_showall"] = '<input type="checkbox" name="all" value="1"'.($all ? ' checked="checked"' : '').' />';

$vars["logsearch_submit"] = "<input type=\"submit\" value=\"${lang["GO"]}\" />";
$vars["logsearch_hidden"] = "<input type=\"hidden\" name=\"logsearch\" value=\"1\" />".
                             "<input type=\"hidden\" name=\"op\" value=\"log\" />".
                             "<input type=\"hidden\" name=\"rev\" value=\"$rev\" />".
                             "<input type=\"hidden\" name=\"isdir\" value=\"$isDir\" />";
$vars["logsearch_endform"] = "</form>";

if ($page !== 1 || $all || $dosearch || $fromRev || $startrev !== $rev || $endrev !== 1 || $max !== 30) {
  $url = $config->getURL($rep, $path, "log");
  $vars["logsearch_clearloglink"] = "<a href=\"${url}rev=$rev&amp;isdir=$isDir\">${lang["CLEARLOG"]}</a>";
}

$url = $config->getURL($rep, "/", "comp");
$vars["compare_form"] = "<form action=\"$url\" method=\"post\">";
$vars["compare_submit"] = "<input name=\"comparesubmit\" type=\"submit\" value=\"${lang["COMPAREREVS"]}\" />";
$vars["compare_hidden"] = "<input type=\"hidden\" name=\"op\" value=\"comp\" />";
$vars["compare_endform"] = "</form>";

$vars['showageinsteadofdate'] = $config->showAgeInsteadOfDate;

$vars["version"] = $version;

if (!$rep->hasReadAccess($path, false)) {
  $vars["noaccess"] = true;
}

parseTemplate($rep->getTemplatePath()."header.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."log.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."footer.tmpl", $vars, $listing);
