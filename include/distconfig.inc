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
// config.inc
//
// Configuration parameters

if (!@$CONFIG)
{
$CONFIG = true;

// --- DON'T CHANGE THIS PART ---

// Include the configuration class
require("include/configclass.inc");

// Define the language array
$lang = array();

// Include a default language file.
require("languages/english.inc");

// Set up svnlocwebsvn
if (empty($locwebsvnhttp))
   $locwebsvnhttp = ".";

if (empty($locwebsvnreal))
   $locwebsvnreal = ".";

$vars["locwebsvnhttp"] = $locwebsvnhttp;

// Create the config
$config = new Config;

// --- FOLLOW THE INSTRUCTIONS BELOW TO CONFIGURE YOUR SETUP ---

// Define the configuration

// PLATFORM CONFIGURATION

// Uncomment the next line if your running a windows server
//
// $config->setServerIsWindows();

// Configure these lines if your commands aren't on your path.   Spaces are allowed on Windows machines.
//
// $config->setSVNCommandPath("Path/to/svnlook/ e.g. c:\\program files\\subversion\\bin");
// $config->setDiffPath("Path/to/diff/command/");
// $config->setEnscriptPath("Path/to/diff/command/");
// $config->setSedPath("Path/to/diff/command/");

// REPOSITORY SETUP

// There are 2 methods for defining the repositiories available on the system.  Either you list
// them by hand, in which case you can give each one the name of your choice, or you use the
// parent path function, in which case the name of the directory is used as the repository name.
//
// To configure the repositories by hand, copy the line below, uncomment it and replace the name and path
//
// $config->addRepository("NameToDisplay", "Path/to/repository (e.g. c:\\svn\\proj)");
//
// To use the parent path method, uncomment the newt line and and replace the path with your one.  You
// can call the function several times if you have several parent paths.
//
// $config->parentPath("Path/to/parent (e.g. c:\\svn)");

// LOOK AND FEEL
//
// Uncomment ONLY the display file that you want.  

$config->setTemplatePath("$locwebsvnreal/templates/Standard/");
//$config->setTemplatePath("$locwebsvnreal/templates/BlueGrey/");

// LANGUAGE SETUP

// Uncomment ONLY the language file which you want.  If you want English then don't do anything here.
//
// include("languages/german.inc");

// MISCELLANOUS

// Uncomment this line if you want to use MultiView to access the repository by, for example:
//
// http://servername/wsvn/repname/path/in/repository
//
// Note: The websvn directory will need to have Multiviews turned on in Apache, and you'll need to configure
//       wsvn.php

//$config->useMultiViews();

// Uncomment this if you don't have the right to use it.  Be warned that you may need it however!
set_time_limit(0);

// Comment this line to turn off caching of repo information.  This will slow down your browsing.
$config->setCachingOn();

// COLOURISATION

// Uncomment this line if you want to use Enscript to colourise your file listings
//
// You'll need Enscript version 1.6 or higher AND Sed installed to use this feature. 
// Set the path above.
$config->useEnscript();

// List of extensions recognised by enscript.  Remove or change the extension => language
// bindings as needed.

$extensions = array
(
  ".ada"     => "ada",
  ".adb"     => "ada",
  ".ads"     => "ada",
  ".awk"     => "awk",
  ".c"       => "c",
  ".c++"     => "cpp",
  ".cc"      => "cpp",
  ".cpp"     => "cpp",
  ".csh"     => "csh",
  ".cxx"     => "cpp",
  ".diff"    => "diffu",
  ".dpr"     => "delphi",
  ".el"      => "elisp",
  ".eps"     => "postscript",
  ".f"       => "fortran",
  ".for"     => "fortran",
  ".gs"      => "haskell",
  ".h"       => "c",
  ".hpp"     => "cpp",
  ".hs"      => "haskell",
  ".htm"     => "html",
  ".html"    => "html",
  ".idl"     => "idl",
  ".java"    => "java",
  ".js"      => "javascript",
  ".lgs"     => "haskell",
  ".lhs"     => "haskell",
  ".m"       => "objc",
  ".m4"      => "m4",
  ".man"     => "nroff",
  ".nr"      => "nroff",
  ".p"       => "pascal",
  ".pas"     => "delphi",
  ".patch"   => "diffu",
  ".pkg"     => "sql", 
  ".pl"      => "perl",
  ".pm"      => "perl",
  ".pp"      => "pascal",
  ".ps"      => "postscript",
  ".s"       => "asm",
  ".scheme"  => "scheme",
  ".scm"     => "scheme",
  ".scr"     => "synopsys",
  ".sh"      => "sh",
  ".shtml"   => "html",
  ".sql"     => "sql",
  ".st"      => "states",
  ".syn"     => "synopsys",
  ".synth"   => "synopsys",
  ".tcl"     => "tcl",
  ".tex"     => "tex",
  ".texi"    => "tex",
  ".texinfo" => "tex",
  ".v"       => "verilog",
  ".vba"     => "vba",
  ".vh"      => "verilog",
  ".vhd"     => "vhdl",
  ".vhdl"    => "vhdl",
  ".py"      => "python"
);

}
?>
