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
// index.php
//
// Main page.  Lists all the projects

require_once("include/setup.inc");
require_once("include/svnlook.inc");
require("include/template.inc");

$vars["action"] = $lang["PROJECTS"];
$vars["repname"] = "";
$vars["rev"] = 0;
$vars["path"] = "";

$projects = $config->getRepositories();
$i = 0;
$listing = array ();
foreach ($projects as $project)
{
   $url = $config->getURL($project, "/", "dir");

   $listing[$i]["rowparity"] = $i % 2;
   $listing[$i++]["projlink"] = "<a href=\"${url}sc=0\">".$project->name."</a>";
} 

$vars["version"] = $version;
parseTemplate($config->templatePath."header.tmpl", $vars, $listing);
parseTemplate($config->templatePath."index.tmpl", $vars, $listing);
parseTemplate($config->templatePath."footer.tmpl", $vars, $listing);

?>