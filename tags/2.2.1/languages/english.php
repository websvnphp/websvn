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
// english.php
//
// English language strings

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "English";
// This is the RFC 2616 (§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'en';

$lang["LOG"] = "Log";
$lang["DIFF"] = "Diff";

$lang["NOREP"] = "No repository given";
$lang["NOPATH"] = "Path not found";
$lang["NOACCESS"] = "You do not have the necessary permissions to read this content";
$lang["RESTRICTED"] = "Restricted access";
$lang["SUPPLYREP"] = "Please set up a repository path in include/config.php using \$config->parentPath or \$config->addRepository<p>See the installation guide for more details";

$lang["DIFFREVS"] = "Diff between revs";
$lang["AND"] = "and";
$lang["REV"] = "Rev";
$lang["LINE"] = "Line";
$lang["SHOWENTIREFILE"] = "Show entire file";
$lang["SHOWCOMPACT"] = "Only display areas with differences";
$lang["IGNOREWHITESPACE"] = "Ignore whitespace";
$lang["REGARDWHITESPACE"] = "Regard whitespace";

$lang["LISTING"] = "Directory listing";
$lang["FILEDETAIL"] = "Details";
$lang["DIFFPREV"] = "Compare with Previous";
$lang["BLAME"] = "Blame";

$lang["REVINFO"] = "Revision Information";
$lang["GOYOUNGEST"] = "Go to most recent revision";
$lang["LASTMOD"] = "Last modification";
$lang["LOGMSG"] = "Log message";
$lang["CHANGES"] = "Changes";
$lang["SHOWCHANGED"] = "Show changed files";
$lang["HIDECHANGED"] = "Hide changed files";
$lang["NEWFILES"] = "New Files";
$lang["CHANGEDFILES"] = "Modified files";
$lang["DELETEDFILES"] = "Deleted files";
$lang["VIEWLOG"] = "View Log";
$lang["PATH"] = "Path";
$lang["AUTHOR"] = "Author";
$lang["AGE"] = "Age";
$lang["LOG"] = "Log";
$lang["CURDIR"] = "Current Directory";
$lang["TARBALL"] = "Tarball";
$lang["DOWNLOAD"] = "Download";

$lang["PREV"] = "Prev";
$lang["NEXT"] = "Next";
$lang["SHOWALL"] = "Show All";

$lang["BADCMD"] = "Error running this command";
$lang["UNKNOWNREVISION"] = "Revision not found";

$lang["POWERED"] = "Powered by <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion Repositories";
$lang["SERVER"] = "Subversion Server";

$lang["FILTER"] = "Filtering Options";
$lang["STARTLOG"] = "From rev";
$lang["ENDLOG"] = "To rev";
$lang["MAXLOG"] = "Max revs";
$lang["SEARCHLOG"] = "Search for";
$lang["CLEARLOG"] = "Clear current filter";
$lang["MORERESULTS"] = "Find more matches...";
$lang["NORESULTS"] = "There are no logs matching your query";
$lang["NOMORERESULTS"] = "There are no more logs matching your query";
$lang['NOPREVREV'] = 'No previous revision';

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "file(s) modified";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "Line No.";
$lang["BLAMEFOR"] = "Blame information for rev";

$lang["DAYLETTER"] = "d";
$lang["HOURLETTER"] = "h";
$lang["MINUTELETTER"] = "m";
$lang["SECONDLETTER"] = "s";

$lang["GO"] = "Go";

$lang["PATHCOMPARISON"] = "Path Comparison";
$lang["COMPAREPATHS"] = "Compare Paths";
$lang["COMPAREREVS"] = "Compare Revisions";
$lang["PROPCHANGES"] = "Property changes :";
$lang["CONVFROM"] = "This comparison shows the changes necessary to convert path ";
$lang["TO"] = "TO";
$lang["REVCOMP"] = "Reverse comparison";
$lang["COMPPATH"] = "Compare Path:";
$lang["WITHPATH"] = "With Path:";
$lang["FILEDELETED"] = "File deleted";
$lang["FILEADDED"] = "New file";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
