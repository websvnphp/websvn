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
// comp.php
//
// Compare two paths using "svn diff"
//

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");
require_once("include/template.php");

function checkRevision($rev) {
  if (is_numeric($rev) && ((int)$rev > 0)) {
    return $rev;
  }

  $rev = strtoupper($rev);

  switch($rev) {
    case "HEAD":
      // fall through
    case "PREV":
      // fall through
    case "COMMITTED":
      return $rev;
  }

  return "HEAD";
}

$svnrep = new SVNRepository($rep);

// Retrieve the request information
$path1 = @$_REQUEST["compare"][0];
$path2 = @$_REQUEST["compare"][1];
$rev1 = (int)@$_REQUEST["compare_rev"][0];
$rev2 = (int)@$_REQUEST["compare_rev"][1];
$manualorder = (@$_REQUEST["manualorder"] == 1);
$ignoreWhitespace = (@$_REQUEST["ignorews"] == 1);

// Some page links put the revision with the path...
if (strpos($path1, "@")) list($path1, $rev1) = explode("@", $path1);
// Something went wrong. The path is missing.
else if (strpos($path1, "@") === 0) {
  $rev1 = substr($path1, 1);
  $path1 = '/';
}
if (strpos($path2, "@")) list($path2, $rev2) = explode("@", $path2);
else if (strpos($path2, "@") === 0) {
  $rev2 = substr($path2, 1);
  $path2 = '/';
}

$rev1 = checkRevision($rev1);
$rev2 = checkRevision($rev2);

// Choose a sensible comparison order unless told not to

if (!$manualorder && is_numeric($rev1) && is_numeric($rev2) && $rev1 > $rev2) {
  $temppath = $path1;
  $temprev = $rev1;

  $path1 = $path2;
  $rev1 = $rev2;

  $path2 = $temppath;
  $rev2 = $temprev;
}

$vars['indexurl'] = $config->getURL($rep, '', 'index');
$vars['repurl'] = $config->getURL($rep, '', 'dir');

$url = $config->getURL($rep, "/", "comp");
$vars["revlink"] = '<a href="'.$url.'compare%5B%5D='.urlencode($path2).'@'.$rev2.'&amp;compare%5B%5D='.urlencode($path1).'@'.$rev1.'&amp;manualorder=1&amp;ignorews='.($ignoreWhitespace ? '1' : '0').'">'.$lang['REVCOMP'].'</a>';
if (!$ignoreWhitespace) {
  $vars['ignorewhitespacelink'] = '<a href="'.$url.'compare%5B%5D='.urlencode($path1).'@'.$rev1.'&amp;compare%5B%5D='.urlencode($path2).'@'.$rev2.'&amp;manualorder='.($manualorder ? '1' : '0').'&amp;ignorews=1">'.$lang['IGNOREWHITESPACE'].'</a>';
  $vars['regardwhitespacelink'] = '';
} else {
  $vars['regardwhitespacelink'] = '<a href="'.$url.'compare%5B%5D='.urlencode($path1).'@'.$rev1.'&amp;compare%5B%5D='.urlencode($path2).'@'.$rev2.'&amp;manualorder='.($manualorder ? '1' : '0').'&amp;ignorews=0">'.$lang['REGARDWHITESPACE'].'</a>';
  $vars['ignorewhitespacelink'] = '';
}

if ($rev1 == 0) $rev1 = "HEAD";
if ($rev2 == 0) $rev2 = "HEAD";

$vars["repname"] = $rep->getDisplayName();
$vars["action"] = $lang["PATHCOMPARISON"];
$vars["compare_form"] = "<form action=\"$url\" method=\"post\">";
$vars["compare_path1input"] = "<input type=\"text\" size=\"40\" name=\"compare[0]\" value=\"".htmlentities($path1, ENT_QUOTES, 'UTF-8')."\" />";
$vars["compare_rev1input"] = "<input type=\"text\" size=\"5\" name=\"compare_rev[0]\" value=\"$rev1\" />";
$vars["compare_path2input"] = "<input type=\"text\" size=\"40\" name=\"compare[1]\" value=\"".htmlentities($path2, ENT_QUOTES, 'UTF-8')."\" />";
$vars["compare_rev2input"] = "<input type=\"text\" size=\"5\" name=\"compare_rev[1]\" value=\"$rev2\" />";
$vars["compare_submit"] = "<input name=\"comparesubmit\" type=\"submit\" value=\"${lang["COMPAREPATHS"]}\" />";
$vars["compare_hidden"] = "<input type=\"hidden\" name=\"op\" value=\"comp\" /><input type=\"hidden\" name=\"manualorder\" value=\"1\" />";
$vars["compare_endform"] = "</form>";

// safe paths are a hack for fixing XSS sploit
$vars["path1"] = htmlentities($path1, ENT_QUOTES, 'UTF-8');
$vars['safepath1'] = htmlentities($path1, ENT_QUOTES, 'UTF-8');
$vars["path2"] = htmlentities($path2, ENT_QUOTES, 'UTF-8');
$vars['safepath2'] = htmlentities($path2, ENT_QUOTES, 'UTF-8');

$vars["rev1"] = $rev1;
$vars["rev2"] = $rev2;

$noinput = empty($path1) || empty($path2);
$listing = array();

// Generate the diff listing

$relativePath1 = $path1;
$relativePath2 = $path2;

$svnpath1 = encodepath($svnrep->getSvnpath(str_replace(DIRECTORY_SEPARATOR, '/', $path1)));
$svnpath2 = encodepath($svnrep->getSvnpath(str_replace(DIRECTORY_SEPARATOR, '/', $path2)));

$debug = false;

if (!$noinput) {
  $cmd = $config->svn." diff ".($ignoreWhitespace ? '-x -w ' : '').$rep->svnParams().quote($svnpath1."@".$rev1)." ".quote($svnpath2."@".$rev2);
  if ($debug) echo "$cmd\n";
}

function clearVars() {
  global $listing, $index;

  $listing[$index]["newpath"] = null;
  $listing[$index]["endpath"] = null;
  $listing[$index]["info"] = null;
  $listing[$index]["diffclass"] = null;
  $listing[$index]["difflines"] = null;
  $listing[$index]["enddifflines"] = null;
  $listing[$index]["properties"] = null;
}

$vars["success"] = false;

if (!$noinput) {
  if ($diff = popenCommand($cmd, "r")) {
    $index = 0;
    $indiff = false;
    $indiffproper = false;
    $getLine = true;
    $node = null;
    $bufferedLine = false;

    $vars["success"] = true;

    while (!feof($diff)) {
      if ($getLine) {
        if ($bufferedLine === false) {
          $bufferedLine = rtrim(fgets($diff), "\r\n");
        }
        $newlineR = strpos($bufferedLine, "\r");
        $newlineN = strpos($bufferedLine, "\n");
        if ($newlineR === false && $newlineN === false) {
          $line = $bufferedLine;
          $bufferedLine = false;
        } else {
          $newline = ($newlineR < $newlineN ? $newlineR : $newlineN);
          $line = substr($bufferedLine, 0, $newline);
          $bufferedLine = substr($bufferedLine, $newline + 1);
        }
      }

      clearVars();
      $getLine = true;
      if ($debug) print "Line = '$line'<br />";
      if ($indiff) {
        // If we're in a diff proper, just set up the line
        if ($indiffproper) {
          if (strlen($line) > 0 && ($line[0] == " " || $line[0] == "+" || $line[0] == "-")) {
            $subline = replaceEntities(substr($line, 1), $rep);
            if ($subline == '') $subline = "&nbsp;";
            $subline = hardspace($subline);
            $listing[$index]["line"] = $subline;

            switch ($line[0]) {
              case " ":
                $listing[$index]["diffclass"] = "diff";
                if ($debug) print "Including as diff: $subline<br />";
                break;

              case "+":
                $listing[$index]["diffclass"] = "diffadded";
                if ($debug) print "Including as added: $subline<br />";
                break;

              case "-":
                $listing[$index]["diffclass"] = "diffdeleted";
                if ($debug) print "Including as removed: $subline<br />";
                break;
            }

            $index++;

            continue;

          } else if ($line == '\ No newline at end of file') {
            continue;

          } else {
            $indiffproper = false;
            $listing[$index++]["enddifflines"] = true;
            $getLine = false;
            if ($debug) print "Ending lines<br />";
            continue;
          }
        }

        // Check for the start of a new diff area
        if (!strncmp($line, "@@", 2)) {
          $pos = strpos($line, "+");
          $posline = substr($line, $pos);
          $sline = 0;
          $eline = 0;
          sscanf($posline, "+%d,%d", $sline, $eline);
          if ($debug) print "sline = '$sline', eline = '$eline'<br />";
          // Check that this isn't a file deletion
          if ($sline == 0 && $eline == 0) {
            $line = fgets($diff);
            if ($debug) print "Ignoring: $line<br />" ;
            while ($line[0] == " " || $line[0] == "+" || $line[0] == "-") {
              $line = fgets($diff);
              if ($debug) print "Ignoring: $line<br />" ;
            }

            $getLine = false;
            if ($debug) print "Unignoring previous - marking as deleted<b>";
            $listing[$index++]["info"] = $lang["FILEDELETED"];

          } else {
            $listing[$index]["difflines"] = $line;
            $sline = 0;
            $slen = 0;
            $eline = 0;
            $elen = 0;
            sscanf($line, "@@ -%d,%d +%d,%d @@", $sline, $slen, $eline, $elen);
            $listing[$index]["rev1line"] = $sline;
            $listing[$index]["rev1len"] = $slen;
            $listing[$index]["rev2line"] = $eline;
            $listing[$index]["rev2len"] = $elen;

            $indiffproper = true;

            $index++;
          }

          continue;

        } else {
          $indiff = false;
          if ($debug) print "Ending diff";
        }
      }

      // Check for a new node entry
      if (strncmp(trim($line), "Index: ", 7) == 0) {
        // End the current node
        if ($node) {
          $listing[$index++]["endpath"] = true;
          clearVars();
        }

        $node = trim($line);
        $node = substr($node, 7);
        if ($node == '' || $node{0} != '/') $node = '/'.$node;

        if (substr($path2, -strlen($node)) === $node) {
          $absnode = $path2;
        } else {
          $absnode = $path2;
          if (substr($absnode, -1) == '/') $absnode = substr($absnode, 0, -1);
          $absnode .= $node;
        }

        $listing[$index]["newpath"] = $absnode;

        $listing[$index]["fileurl"] = $config->getURL($rep, $absnode, "file").'rev='.$rev2;

        if ($debug) echo "Creating node $node<br />";

        // Skip past the line of ='s
        $line = fgets($diff);
        if ($debug) print "Skipping: $line<br />" ;

        // Check for a file addition
        $line = fgets($diff);
        if ($debug) print "Examining: $line<br />" ;
        if (strpos($line, "(revision 0)")) {
          $listing[$index]["info"] = $lang["FILEADDED"];
        }

        if (strncmp(trim($line), "Cannot display:", 15) == 0) {
          $index++;
          clearVars();
          $listing[$index++]["info"] = $line;
          continue;
        }

        // Skip second file info
        $line = fgets($diff);
        if ($debug) print "Skipping: $line<br />" ;

        $indiff = true;
        $index++;

        continue;
      }

      if (strncmp(trim($line), "Property changes on: ", 21) == 0) {
        $propnode = trim($line);
        $propnode = substr($propnode, 21);

        if ($debug) print "Properties on $propnode (cur node $ $node)";
        if ($propnode != $node) {
          if ($node) {
            $listing[$index++]["endpath"] = true;
            clearVars();
          }

          $node = $propnode;

          $listing[$index++]["newpath"] = $node;
          clearVars();
        }

        $listing[$index++]["properties"] = true;
        clearVars();
        if ($debug) echo "Creating node $node<br />";

        // Skip the row of underscores
        $line = fgets($diff);
        if ($debug) print "Skipping: $line<br />" ;

        while ($line = trim(fgets($diff))) {
          $listing[$index++]["info"] = $line;
          clearVars();
        }

        continue;
      }

      // Check for error messages
      if (strncmp(trim($line), "svn: ", 5) == 0) {
        $listing[$index++]["info"] = urldecode($line);
        $vars["success"] = false;
        continue;
      }

      $listing[$index++]["info"] = $line;
    }

    if ($node) {
      clearVars();
      $listing[$index++]["endpath"] = true;
    }

    if ($debug) print_r($listing);

    pclose($diff);
  }
}

$vars["version"] = $version;

if (!$rep->hasUnrestrictedReadAccess($relativePath1) || !$rep->hasUnrestrictedReadAccess($relativePath2, false)) {
  $vars["noaccess"] = true;
}

parseTemplate($rep->getTemplatePath()."header.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."compare.tmpl", $vars, $listing);
parseTemplate($rep->getTemplatePath()."footer.tmpl", $vars, $listing);
