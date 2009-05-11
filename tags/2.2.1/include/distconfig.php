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
// config.php
//
// Configuration parameters

// --- FOLLOW THE INSTRUCTIONS BELOW TO CONFIGURE YOUR SETUP ---

// {{{ PLATFORM CONFIGURATION ---

// Configure the path to your subversion config file
// (e.g. if accepting certificates is required when using repositories via https)
// $config->setConfigPath('/tmp');

// Configure these lines if your commands aren't on your path.
//
// $config->setSVNCommandPath('Path/to/svn and svnlook/ e.g. c:\\program files\\subversion\\bin');
// $config->setDiffPath('Path/to/diff/command/');

// For syntax colouring, if option enabled...
// $config->setEnscriptPath('Path/to/enscript/command/');
// $config->setSedPath('Path/to/sed/command/');

// For delivered tarballs, if option enabled...
// $config->setTarPath('Path/to/tar/command/');

// For delivered GZIP'd files and tarballs, if option enabled...
// $config->setGZipPath('Path/to/gzip/command/');

// download folder/file zipped ...
// $config->setZipPath('Path/to/zip/command/');

// }}}

// {{{ REPOSITORY SETUP ---

// There are 2 methods for defining the repositiories available on the system.  Either you list
// them by hand, in which case you can give each one the name of your choice, or you use the
// parent path function, in which case the name of the directory is used as the repository name.
//
// In all cases, you may optionally supply a group name to the repositories.  This is useful in the
// case that you need to separate your projects.  Grouped Repositories are referred to using the
// convention GroupName.RepositoryName
//
// Performance is much better on local repositories (e.g. accessed by file:///).  However, you
// can also provide an interface onto a remote repository.  In this case you should supply the
// username and password needed to access it.
//
// To configure the repositories by hand, copy the appropriate line below, uncomment it and
// replace the name and URL of your repository.

// Local repositories (without and with optional group):
//
// $config->addRepository('NameToDisplay', 'URL to repository (e.g. file:///c:/svn/proj)');
// $config->addRepository('NameToDisplay', 'URL to repository (e.g. file:///c:/svn/proj)', 'group');
//
// Remote repositories (without and with optional group):
//
// $config->addRepository('NameToDisplay', 'URL (e.g. http://path/to/rep)', NULL, 'username', 'password');
// $config->addRepository('NameToDisplay', 'URL (e.g. http://path/to/rep)', 'group', 'username', 'password');
//
// Display Part of a repository as if it was a repository.
//
// Local repositories (without and with optional group):
//
// $config->addRepositorySubpath('NameToDisplay', 'URL to repository (e.g. file:///c:/svn/proj)', 'subpath');
// $config->addRepositorySubpath('NameToDisplay', 'URL to repository (e.g. file:///c:/svn/proj)', 'subpath', 'group');
//
// Remote repositories (without and with optional group):
//
// $config->addRepositorySubpath('NameToDisplay', 'URL (e.g. http://path/to/rep)', 'subpath', NULL, 'username', 'password');
// $config->addRepositorySubpath('NameToDisplay', 'URL (e.g. http://path/to/rep)', 'subpath', 'group', 'username', 'password');
//
// To use the parent path method (without and with optional group), uncomment the next line
// and replace the path with your one. You can call the function several times if you have several parent paths.
// Note that in this case the path is a filesystem path.
//
// $config->parentPath('Path/to/parent (e.g. c:\\svn)');
// $config->parentPath('Path/to/parent (e.g. c:\\svn)', 'group');
//
// To exclude a repository from being added by the parentPath method uncomment the next line
// and replace the path with your one. You can call the function several times if you have several paths to exclude.
//
// $config->addExcludedPath('Path/to/parent/excludedRep (e.g. c:\\svn\\excludedRep)');
//
// To add only a subset of repositories specified by the parent path you can call the function with a pattern.
//
// $config->parentPath('Path/to/parent (e.g. c:\\svn)', 'group', '/^beginwith/');

// }}}

// {{{ LOOK AND FEEL ---
//
// Uncomment ONLY the template file that you want.

$config->setTemplatePath("$locwebsvnreal/templates/calm/");
// $config->setTemplatePath("$locwebsvnreal/templates/BlueGrey/");

// You may also specify a per repository template file by uncommenting and changing the following
// line as necessary.  Use the convention "groupname.myrep" if your repository is in a group.

// $config->setTemplatePath('$locwebsvnreal/templates/Standard/', 'myrep'); // Access file for myrep

// The index page containing the projects may either be displayed as a flat view (the default),
// where grouped repositories are displayed as "GroupName.RepName" or as a tree view.
// In the case of a tree view, you may choose whether the entire tree is open by default.

// $config->useTreeIndex(false); // Tree index, closed by default
// $config->useTreeIndex(true);  // Tree index, open by default

// By default, WebSVN displays a tree view onto the current directory.  You can however
// choose to display a flat view of the current directory only, which may make the display
// load faster.  Uncomment this line if you want that.

// $config->useFlatView();

// By default, WebSVN displays subfolders first and than the files of a directory,
// both alphabetically sorted.
// To use alphabetic order independent iof folders and files uncomment this line.

// $config->setAlphabeticOrder(true);

// By default, WebSVN displays the information of the last modification
// (revision, age and author) for each entry in an extra column.
// To disable that uncomment this line.

// $config->setShowLastModInListing(false);

// By default, WebSVN displays the age of the last modification.
// Alternativly the date of the last modification can be shown.
// To show dates instead of ages uncomment this line.

// $config->setShowAgeInsteadOfDate(false);

// By default, WebSVN displays the a form to select an other repository.
// If you have a lot of repositories this slows done the script considerably.
// To disable that uncomment this line.

// $config->setShowRepositorySelectionForm(false);

// }}}

// {{{ LANGUAGE SETUP ---

// WebSVN uses the iconv module to convert messages from your system's character set to the
// UTF-8 output encoding. If you find that your log messages aren't displayed correctly then
// you'll need to change the value here.
//
// You may also specify the character encoding of the repository contents if different from
// the system encoding. This is typically the case for windows users, whereby the command
// line returns, for example, CP850 encoded strings, whereas the source files are encoded
// as iso-8859-1 by Windows based text editors.  When display text file, WebSVN will convert
// them from the content encoding to the output encoding (UTF-8).
//
// WebSVN does its best to automate all this, so only use the following if it doesn't work
// "out of the box". Uncomment and change one of the examples below.
//
// $config->setInputEncoding('CP850');  // Encoding of result returned by svn command line, etc.
// $config->setContentEncoding('iso-8859-1');  // Content encoding of all your repositories

// You may also specify a content encoding on a per repository basis.  Uncomment and copy this
// line as necessary.
//
// $config->setContentEncoding('iso-8859-1', 'MyEnc');

// Note for Windows users:  To enable iconv you'll need to enable the extension in your php.ini file
// AND copy iconv.dll (not php_iconv.dll) to your Windows system folder.  In most cases the correct
// encoding is set when you call $config->setServerIsWindows();.

// Note for *nix users.  You'll need to have iconv compiled into your binary.  The default input and
// output encodings are taken from your locale informations.  Override these if they aren't correct.

// Set the default language.  If you want English then don't do anything here.
//
// $config->setDefaultLanguage('en');

// Ignore the user supplied accepted languages to choose reasonable default language.
// If you want to force the default language - regardless of the client - uncomment the following line.
//
// $config->ignoreUserAcceptedLanguages();

// }}}

// {{{ MULTIVIEWS ---

// Uncomment this line if you want to use MultiView to access the repository by, for example:
//
// http://servername/wsvn/repname/path/in/repository
//
// Note: The websvn directory will need to have Multiviews turned on in Apache, and you'll need to configure
//       wsvn.php

// $config->useMultiViews();

// }}}

// {{{ ACCESS RIGHTS ---

// Uncomment this line if you want to use your Subversion access file to control access
// rights via WebSVN.  For this to work, you'll need to set up the same Apache based authentication
// to the WebSVN (or wsvn) directory as you have for Subversion itself.  More information can be
// found in install.txt

// $config->useAuthenticationFile('/path/to/accessfile'); // Global access file

// You may also specify a per repository access file by uncommenting and copying the following
// line as necessary.  Use the convention 'groupname.myrep' if your repository is in a group.

// $config->useAuthenticationFile('/path/to/accessfile', 'myrep'); // Access file for myrep

// }}}

// {{{ FILE CONTENT ---
//
// You may wish certain file types to be GZIP'd and delieved to the user when clicked apon.
// This is useful for binary files and the like that don't display well in a browser window!
// Copy, uncomment and modify this line for each extension to which this rule should apply.
// (Don't forget the . before the extension.  You don't need an index between the []'s).
// If you'd rather that the files were delivered uncompressed with the associated MIME type,
// then read below.
//
// $zipped[] = '.dll';

// Subversion controlled files have an svn:mime-type property that can
// be set on a file indicating its mime type. By default binary files
// are set to the generic appcliation/octet-stream, and other files
// don't have it set at all. WebSVN also has a built-in list of
// associations from file extension to MIME content type. (You can
// view this list in setup.php).
//
// Determining the content-type: By default, if the svn:mime-type
// property exists and is different from application/octet-stream, it
// is used. Otherwise, if the built-in list has a contentType entry
// for the extension of the file, that is used. Otherwise, if the
// svn:mime-type property exists has the generic binary value of
// application/octet-stream, the file will be served as a binary
// file. Otherwise, the file will be brought up as ASCII text in the
// browser window (although this text may optionally be colourised.
// See below).
//
// Uncomment this if you want to ignore any svn:mime-type property on your
// files.
//
// $config->ignoreSvnMimeTypes();
//
// Uncomment this if you want skip WebSVN's custom mime-type handling
//
// $config->ignoreWebSVNContentTypes();
//
// Following the examples below, you can add new associations, modify
// the default ones or even delete them entirely (to show them in
// ASCII via WebSVN).

// $contentType['.c'] = 'text/plain'; // Create a new association
// $contentType['.doc'] = 'text/plain'; // Modify an existing one
// unset($contentType['.m']); // Remove a default association

// If you want to selectively override one or more MIME types to display inline
// (e.g., the svn:mime-type property is something like text/plain or text/xml, or
// the file extension matches an entry in $contentType), you can choose to ignore
// one or more specific MIME types. This approach is finer-grained than ignoring
// all svn:mime-type properties, and displaying matching files inline such that
// they are highlighted correctly. (Regular expression matching is used.)

$config->addInlineMimeType("text/plain");
// $config->addInlineMimeType("text/*");

// }}}

// {{{ TARBALLS ---

// You need tar and gzip installed on your system.  Set the paths above if necessary
//
// Uncomment the line below to offer a tarball download option across all your
// repositories.
//
// $config->allowDownload();
//
// Set download modes
// $config->setDefaultFileDlMode('plain');
// $config->setDefaultFolderDlMode('gzip');
//
// Change the line below to set the temporary directory where to store generated tarball.
//
// $config->setTarballTmpDir('temp');
//
// To change the global option for individual repositories, uncomment and replicate
// the required line below (replacing 'myrep' for the name of the repository to be changed).
// Use the convention 'groupname.myrep' if your repository is in a group.

// $config->allowDownload('myrep'); // Specifically allow downloading for 'myrep'
// $config->disallowDownload('myrep'); // Specifically disallow downloading for 'myrep'

// You can also choose the minimum directory level from which you'll allow downloading.
// A value of zero will allow downloading from the root.  1 will allow downloding of directories
// in the root, etc.
//
// If your project is arranged with trunk, tags and branches at the root level, then a value of 2
// would allow the downloading of directories within branches/tags while disallowing the download
// of the entire branches or tags directories.  This would also stop downloading of the trunk, but
// see after for path exceptions.
//
// Change the line below to set the download level across all your repositories.

$config->setMinDownloadLevel(2);

// To change the level for individual repositories, uncomment and replicate
// the required line below (replacing 'myrep' for the name of the repository to be changed).
// Use the convention 'groupname.myrep' if your repository is in a group.

// $config->setMinDownloadLevel(2, 'myrep');

// Finally, you may add or remove certain directories (and their contents) either globally
// or on a per repository basis.  Uncomment and copy the following lines as necessary.  Note
// that the these are searched in the order than you give them until a match is made (with the
// exception that all the per repository exceptions are tested before the global ones).  This means
// that you must disallow /a/b/c/ before you allow /a/b/ otherwise the allowed match on /a/b/ will
// stop any further searching, thereby allowing downloads on /a/b/c/.

// Global exceptions possibilties:
//
// $config->addAllowedDownloadException('/path/to/allowed/directory/');
// $config->addDisAllowedDownloadException('/path/to/disallowed/directory/');
//
// Per repository exception possibilties:
// Use the convention 'groupname.myrep' if your repository is in a group.
//
// $config->addAllowedDownloadException('/path/to/allowed/directory/', 'myrep');
// $config->addDisAllowedDownloadException('/path/to/disallowed/directory/', 'myrep');

// }}}

// {{{ COLOURISATION ---

// Uncomment this line if you want to use Enscript to colourise your file listings
//
// You'll need Enscript version 1.6 or higher AND Sed installed to use this feature.
// Set the path above.
//
// $config->useEnscript();

// Enscript need to be told what the contents of a file are so that it can be colourised
// correctly.  WebSVN includes a predefined list of mappings from file extension to Enscript
// file type (viewable in setup.php).
//
// Here you should add and other extensions not already listed or redefine the default ones. eg:
//
// $extEnscript['.pas'] = 'pascal';
//
// Note that extensions are case sensitive.

// Uncomment this line if you want to use GeSHi to colourise your file listings
//
$config->useGeshi();

// GeSHi need to be told what the contents of a file are so that it can be colourised
// correctly.  WebSVN includes a predefined list of mappings from file extension to GeSHi
// languages (viewable in setup.php).
//
// Here you should add and other extensions not already listed or redefine the default ones. eg:
//
// $extGeshi['pascal'] = array('p', 'pas');
//
// Note that extensions are case sensitive.

// }}}

// {{{ RSSFEED ---

// Uncomment this line if you wish to hide the RSS feed links across all repositories
//
// $config->hideRSS();
//
// To change the global option for individual repositories, uncomment and replicate
// the required line below (replacing 'myrep' for the name of the repository to be changed).
// Use the convention 'groupname.myrep' if your repository is in a group.

// $config->hideRSS('myrep'); // Specifically hide RSS links for 'myrep'
// $config->showRSS('myrep'); // Specifically show RSS links for 'myrep'

// }}}

// {{{ BUGTRAQ ---

// Uncomment this line if you wish to use bugtraq: properties to show links to your BugTracker
// from the log messages.
//
// $config->useBugtraqProperties();
//
// To change the global option for individual repositories, uncomment and replicate
// the required line below (replacing 'myrep' for the name of the repository to be changed).
// Use the convention 'groupname.myrep' if your repository is in a group.

// $config->useBugtraqProperties('myrep'); // Specifically use bugtraq properties for 'myrep'
// $config->ignoreBugtraqProperties('myrep'); // Specifically ignore bugtraq properties for 'myrep'

// }}}

// {{{ MISCELLANEOUS ---

// Comment out this if you don't have the right to use it.  Be warned that you may need it however!
set_time_limit(0);

// Number of spaces to expand tabs to in diff/listing view across all repositories

$config->expandTabsBy(8);

// To change the global option for individual repositories, uncomment and replicate
// the required line below (replacing 'myrep' for the name of the repository to be changed).
// Use the convention 'groupname.myrep' if your repository is in a group.

// $config->expandTabsBy(3, 'myrep'); // Expand Tabs by 3 for repository 'myrep'

// }}}
