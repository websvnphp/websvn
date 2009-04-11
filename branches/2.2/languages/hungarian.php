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
$lang["LANGUAGENAME"] = "Magyar";
// This is the RFC 2616 (§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'hu';

$lang["LOG"] = "Napló";
$lang["DIFF"] = "Diff";

$lang["NOREP"] = "Nincs megadva repository";
$lang["NOPATH"] = "Az útvonal nem található";
$lang["NOACCESS"] = "Nincs megfelelő jogosultságod ahhoz, hogy olvasd ezt a könyvtárat";
$lang["RESTRICTED"] = "Korlátozott hozzáférés";
$lang["SUPPLYREP"] = "Kérlek, hogy állíts be legalább egy repository útvonalat az include/config.php file-ban a \$config->parentPath vagy \$config->addRepository használatával.<p>További részletekért nézd meg a telepítési kézikönyvet";

$lang["DIFFREVS"] = "Revíziók közti diff-ek";
$lang["AND"] = "és";
$lang["REV"] = "Rev";
$lang["LINE"] = "Sor";
$lang["SHOWENTIREFILE"] = "A teljes file mutatása";
$lang["SHOWCOMPACT"] = "Csak a különbségeket tartalmazó területeket mutassuk";

$lang["FILEDETAIL"] = "Részletek";
$lang["DIFFPREV"] = "Összehasonlítás az előzővel";
$lang["BLAME"] = "Felelős";

$lang["REVINFO"] = "Revízió információ";
$lang["GOYOUNGEST"] = "Ugrás a legfrissebb revízióhoz";
$lang["LASTMOD"] = "Utolsó módosítás";
$lang["LOGMSG"] = "Napló üzenet";
$lang["CHANGES"] = "Változások";
$lang["SHOWCHANGED"] = "Módosult file-ok mutatása";
$lang["HIDECHANGED"] = "Módosult file-ok elrejtése";
$lang["NEWFILES"] = "Új file-ok";
$lang["CHANGEDFILES"] = "Módosult file-ok";
$lang["DELETEDFILES"] = "Törölt file-ok";
$lang["VIEWLOG"] = "Napló&nbsp;megtekintése";
$lang["PATH"] = "Útvonal";
$lang["AUTHOR"] = "Szerző";
$lang["AGE"] = "Kor";
$lang["LOG"] = "Napló";
$lang["CURDIR"] = "Aktuális könyvtár";
$lang["TARBALL"] = "Tarball";

$lang["PREV"] = "Előző";
$lang["NEXT"] = "Következő";
$lang["SHOWALL"] = "Az összes mutatása";

$lang["BADCMD"] = "Hiba történt ennek a parancsnak a futtatásakor";
$lang["UNKNOWNREVISION"] = "A revízió nem található";

$lang["POWERED"] = "Powered by <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion&nbsp;repository-k";
$lang["SERVER"] = "Subversion&nbsp;szerver";

$lang["FILTER"] = "Szűrő feltételek";
$lang["STARTLOG"] = "Revíziótól";
$lang["ENDLOG"] = "Revízióig";
$lang["MAXLOG"] = "Maximum revíziók száma";
$lang["SEARCHLOG"] = "Keresés";
$lang["CLEARLOG"] = "Aktuális szűrő törlése";
$lang["MORERESULTS"] = "További találatok keresése...";
$lang["NORESULTS"] = "Nincsenek a feltételnek megfelelő napló üzenetek";
$lang["NOMORERESULTS"] = "Nincsen több olyan napló üzenet, ami megfelelne a feltételnek";
$lang['NOPREVREV'] = 'Nincs előző revízió';

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "file(-ok) módosultak";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "Sor száma.";
$lang["BLAMEFOR"] = "A revízió felelőse";

$lang["DAYLETTER"] = "n";
$lang["HOURLETTER"] = "ó";
$lang["MINUTELETTER"] = "p";
$lang["SECONDLETTER"] = "m";

$lang["GO"] = "Mehet";

$lang["PATHCOMPARISON"] = "Útvonalak összehasonlítása";
$lang["COMPAREPATHS"] = "Útvonalak hasonlítása";
$lang["COMPAREREVS"] = "Revíziók összehasonlítása";
$lang["PROPCHANGES"] = "Tulajdonos változások :";
$lang["CONVFROM"] = "Ez az összehasonlítás azokat a változtatások mutatja, amik az útvonal konvertálásához szükségesek ";
$lang["TO"] = "ERRE";
$lang["REVCOMP"] = "Fordított összehasonlítás";
$lang["COMPPATH"] = "Útvonal hasonlítása:";
$lang["WITHPATH"] = "ezzel az útvonallal:";
$lang["FILEDELETED"] = "A file törölve";
$lang["FILEADDED"] = "Új file";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";

