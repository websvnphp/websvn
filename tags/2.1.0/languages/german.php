<?php

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004 Stephan Stapel, <stephan.stapel@web.de>
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
// germany.php
//
// German language strings

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "German";
// This is the RFC 2616 (§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'de';

$lang["LOG"] = "Log";
$lang["DIFF"] = "Diff";

$lang["NOREP"] = "Kein Repository angegeben.";
$lang["NOPATH"] = "Pfad nicht gefunden";
$lang["NOACCESS"] = "Sie haben keine ausreichende Berechtigungen um diese Inhalte zu lesen";
$lang["RESTRICTED"] = "Beschr&auml;nkter Zugriff";
$lang["SUPPLYREP"] = "Bitte den Repository-Pfad in include/config.php mit \$config->parentPath oder \$config->addRepository angeben.<p>Genauere Informationen finden sich in der Installationsanleitung";

$lang["DIFFREVS"] = "Vergleich zwischen Revisionen";
$lang["AND"] = "und";
$lang["REV"] = "Revision";
$lang["LINE"] = "Zeile";
$lang["SHOWENTIREFILE"] = "Ganze Datei anzeigen";
$lang["SHOWCOMPACT"] = "Nur ge&auml;nderte Bereiche";

$lang["LISTING"] = "Verzeichnisansicht";
$lang["FILEDETAIL"] = "Details";
$lang["DIFFPREV"] = "Vergleich mit vorheriger";
$lang["BLAME"] = "Blame";

$lang["REVINFO"] = "Revisionsinformation";
$lang["GOYOUNGEST"] = "Zur aktuellen Revision";
$lang["LASTMOD"] = "Letzte &Auml;nderung";
$lang["LOGMSG"] = "Logeintrag";
$lang["CHANGES"] = "&Auml;nderungen";
$lang["SHOWCHANGED"] = "Ge&auml;nderte Dateien anzeigen";
$lang["HIDECHANGED"] = "Ge&auml;nderte Dateien verstecken";
$lang["NEWFILES"] = "Neue Dateien";
$lang["CHANGEDFILES"] = "Ge&auml;nderte Dateien";
$lang["DELETEDFILES"] = "Gel&ouml;schte Dateien";
$lang["VIEWLOG"] = "Log&nbsp;anzeigen";
$lang["PATH"] = "Pfad";
$lang["AUTHOR"] = "Autor";
$lang["AGE"] = "Alter";
$lang["LOG"] = "Log";
$lang["CURDIR"] = "Aktuelles Verzeichnis";
$lang["TARBALL"] = "Archiv Download";

$lang["PREV"] = "Zur&uuml;ck";
$lang["NEXT"] = "Weiter";
$lang["SHOWALL"] = "Alles anzeigen";

$lang["BADCMD"] = "Fehler beim Ausf&uuml;hren des Befehls";
$lang["UNKNOWNREVISION"] = "Revision nicht gefunden";

$lang["POWERED"] = "Powered by <a href=\"http://www.websvn.info\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion-Projekte";
$lang["SERVER"] = "Subversion-Server";

$lang["FILTER"] = "Filteroptionen";
$lang["STARTLOG"] = "Von Rev.";
$lang["ENDLOG"] = "bis Rev.";
$lang["MAXLOG"] = "Max. Rev.";
$lang["SEARCHLOG"] = "Suche im Log nach";
$lang["CLEARLOG"] = "Aktuelle Suche l&ouml;schen";
$lang["MORERESULTS"] = "Weitere Ergebnisse finden...";
$lang["NORESULTS"] = "Es wurden keine Treffer erzielt";
$lang["NOMORERESULTS"] = "Keine weiteren Treffer f&uuml;r diese Suche";
$lang['NOPREVREV'] = 'Keine vorherige Revision';

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "Ver&auml;nderte Dateien";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "Zeilennr.";
$lang["BLAMEFOR"] = "Blame-Information f&uuml;r Rev.";

$lang["DAYLETTER"] = "t";
$lang["HOURLETTER"] = "h";
$lang["MINUTELETTER"] = "m";
$lang["SECONDLETTER"] = "s";

$lang["GO"] = "Los";

$lang["PATHCOMPARISON"] = "Pfadvergleich";
$lang["COMPAREPATHS"] = "Vergleiche Pfade";
$lang["COMPAREREVS"] = "Vergleiche Revisionen";
$lang["PROPCHANGES"] = "Ge&auml;nderte Eigenschaften :";
$lang["CONVFROM"] = "Dieser Vergleich zeigt die &Auml;nderungen zwischen ";
$lang["TO"] = "und";
$lang["REVCOMP"] = "Revisionen vertauschen";
$lang["COMPPATH"] = "Vergleiche Pfad:";
$lang["WITHPATH"] = "Mit Pfad:";
$lang["FILEDELETED"] = "Datei gel&ouml;scht";
$lang["FILEADDED"] = "Neue Datei";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
