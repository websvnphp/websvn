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
// filedetails.php
//
// Simply lists the contents of a file

require("include/config.inc");
require("include/svnlook.inc");
require("include/template.inc");

$rep = @$_REQUEST["rep"];
$path = @$_REQUEST["path"];
$rev = @$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);
$log = $svnrep->getLogDetails($path, $rev);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

$vars["repname"] = $repname;
$vars["rev"] = $log["rev"];
$vars["path"] = $ppath;
$vars["prevdifflink"] = "<a href=\"diff.php?rep=$rep&path=$path&rev=$rev&sc=$showchanged\">${lang["DIFFPREV"]}</a>";

$listing = array ();

$vars["version"] = $version;
parseTemplate("templates/header.tmpl", $vars, $listing);
parseTemplate("templates/file.tmpl", $vars, $listing);
parseTemplate("templates/footer.tmpl", $vars, $listing);

?>