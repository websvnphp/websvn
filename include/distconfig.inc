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

// --- FOLLOW THE INSTRUCTIONS BELOW TO CONFIGURE YOUR SETUP ---

// Define the configuration

// --- PLATFORM CONFIGURATION ---

// Uncomment the next line if your running a windows server
//
// $config->setServerIsWindows();

// Configure these lines if your commands aren't on your path.   Spaces are allowed on Windows machines.
//
// $config->setSVNCommandPath("Path/to/svnlook/ e.g. c:\\program files\\subversion\\bin");
// $config->setDiffPath("Path/to/diff/command/");

// For syntax colouring, if option enabled...
// $config->setEnscriptPath("Path/to/enscript/command/");
// $config->setSedPath("Path/to/sed/command/");

// For delivered GZIP'd filed, if option enabled...
// $config->setGZipPath("Path/to/gzip/command/");

// --- REPOSITORY SETUP ---

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

// --- LOOK AND FEEL ---
//
// Uncomment ONLY the display file that you want.  

$config->setTemplatePath("$locwebsvnreal/templates/Standard/");
// $config->setTemplatePath("$locwebsvnreal/templates/BlueGrey/");

// --- LANGUAGE SETUP ---

// Uncomment ONLY the language file which you want.  If you want English then don't do anything here.
//
// include("languages/german.inc");
// include("languages/french.inc");
// include("languages/portuguese.inc");

// By default, WebSVN tries to use the iconv module (if enabled) to convert log messages from your
// system's character set to you default output encoding. If you find that your log messages aren't
// displayed correctly then you'll need to change the values here.

// $config->setInputEncoding("iso-8859-1");  // Encoding of log messages returned by svnlook on your system
// $config->setOutputEncoding("iso-8859-1"); // Encoding of resulting web page

// Note for Windows users:  To enable iconv you'll need to enable the extension in your php.ini file 
// AND copy iconv.dll (not php_iconv.dll) to your Windows system folder.  In most cases the correct
// encoding is set when you call $config->setServerIsWindows();.  

// Note for *nix users.  You'll need to have iconv compiled into your binary.  The default input and
// output encodings are taken from your locale informations.  Override these if they aren't correct.

// --- MULTIVIEWS ---

// Uncomment this line if you want to use MultiView to access the repository by, for example:
//
// http://servername/wsvn/repname/path/in/repository
//
// Note: The websvn directory will need to have Multiviews turned on in Apache, and you'll need to configure
//       wsvn.php

// $config->useMultiViews();

// --- FILE CONTENT ---
//
// You may wish certain file types to be GZIP'd and delieved to the user when clicked apon.
// This is useful for binary files and the like that don't display well in a browser window!
// Copy, uncomment and modify this line for each extension to which this rule should apply.
// (Don't forget the . before the extension.  You don't need an index between the []'s).
// If you'd rather that the files were delivered uncompressed with the associated MIME type,
// then read below.
//
// $zipped[] = ".dll";

// WebSVN has a built in list of associations from file extension to MIME content type. (You can
// view this list in setup.inc). By default, any extension clicked on via WebSVN that isn't
// included in this list will be brought up as ASCII text in the browser window (although this
// text may optionally be colourised.  See below). 
//
// For any extension that has a MIME type defined, the file will be delivered to the user as an
// attachment with the given MIME type (unless you've asked for it to be GZIP'd above).
//
// Following the examples below, you can add new associations, modify the default ones or even
// delete them entirely (to show them in ASCII via WebSVN).

// $contentType[".c"] = "plain/text"; // Create a new association
// $contentType[".doc"] = "plain/text"; // Modify an existing one
// unset($contentType[".m"] // Remove a default association

// --- COLOURISATION ---

// Uncomment this line if you want to use Enscript to colourise your file listings
//
// You'll need Enscript version 1.6 or higher AND Sed installed to use this feature. 
// Set the path above.
//
// $config->useEnscript();

// Enscript need to be told what the contents of a file are so that it can be colourised
// correctly.  WebSVN includes a predefined list of mappings from file extension to Enscript
// file type (viewable in setup.inc).
//
// Here you should add and other extensions not already listed or redefine the default ones. eg:
//
// $extEnscript[".pas"] = "pascal";

// --- RSSFEED ---

// Uncomment this line if you wish to hide the RSS feed links
//
// $config->hideRSS();

// --- MISCELLANOUS ---

// Uncomment this if you don't have the right to use it.  Be warned that you may need it however!
set_time_limit(0);

// Comment this line to turn off caching of repo information.  This will slow down your browsing.
$config->setCachingOn();

// Number of spaces to expand tabs to in diff/listing view
$config->expandTabsBy(8);

// WebSVN URLs now use the repository names rather than internal numbers.  This means that
// bookmarks remain the same when new projects are added.  In the unlikely event that you have
// multiple projects with the same name, uncomment this line to return to the previous system
//
// $config->setRepositoryAccessByNumber();

?>
