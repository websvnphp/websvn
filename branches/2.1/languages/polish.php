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
// polish.php
//
// Polish language strings in UTF-8 encoding

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "Polish";
$lang['LANGUAGETAG'] = 'pl';

$lang["LOG"] = "Dziennik zmian";
$lang["DIFF"] = "Różnice";

$lang["NOREP"] = "Nie podano żadnego repozytorium";
$lang["NOPATH"] = "Nie odnaleziono ścieżki";
$lang["SUPPLYREP"] = "Proszę ustawić ścieżkę do repozytoriów w
include/config.php za pomocą \$config->parentPath lub
\$config->addRepository<p>Aby uzyskać więcej szczegółów zapoznaj się z
podręcznikiem instalacyjnym";

$lang["DIFFREVS"] = "Różnice pomiędzy wersjami";
$lang["AND"] = "i";
$lang["REV"] = "Wersja";
$lang["LINE"] = "Linia";
$lang["SHOWENTIREFILE"] = "Pokaż cały plik";
$lang["SHOWCOMPACT"] = "Pokaż tylko fragmety w których zaszły zmiany";

$lang["DIFFPREV"] = "Porównaj z poprzednią wersją";
$lang["BLAME"] = "Wkład pracy";

$lang["REVINFO"] = "Informacje o wersji";
$lang["GOYOUNGEST"] = "Przejdź do najnowszej wersji";
$lang["LASTMOD"] = "Ostatnia zmiana";
$lang["LOGMSG"] = "Wpis z dziennika zmian";
$lang["CHANGES"] = "Zmiany";
$lang["SHOWCHANGED"] = "Pokaż zmienione pliki";
$lang["HIDECHANGED"] = "Ukryj zmienione pliki";
$lang["NEWFILES"] = "Nowe pliki";
$lang["CHANGEDFILES"] = "Zmienione pliki";
$lang["DELETEDFILES"] = "Usunięte pliki";
$lang["VIEWLOG"] = "Pokaż&nbsp;dziennik&nbsp;zmian";
$lang["PATH"] = "Ścieżka";
$lang["AUTHOR"] = "Autor";
$lang["AGE"] = "Wiek";
$lang["LOG"] = "Dziennik&nbsp;zmian";
$lang["CURDIR"] = "Aktualny katalog";
$lang["TARBALL"] = "Archiwum tar";

$lang["PREV"] = "Poprzednia strona";
$lang["NEXT"] = "Następna strona";
$lang["SHOWALL"] = "Pokaż wszyskie";

$lang["BADCMD"] = "Błąd podczas wykonywania polecenia";

$lang["POWERED"] = "Obsługiwane przez <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Projekty&nbsp;Subversion";
$lang["SERVER"] = "Serwer Subversion";

$lang["SEARCHLOG"] = "Przeszukaj dziennik zmian";
$lang["CLEARLOG"] = "Usuń rezulaty wyszukiwania";
$lang["MORERESULTS"] = "Znajdź więcej dopasowań...";
$lang["NORESULTS"] = "Żaden wpis w dzinniku zmian nie pasuje do zapytania";
$lang["NOMORERESULTS"] = "Nie ma już więcej wpisów pasujących do
zadanych kryterów";

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "zmienione pliki";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "Linia nr";
$lang["BLAMEFOR"] = "Wkład pracy dla wersji";

$lang["YEARS"] = "lat";
$lang["MONTHS"] = "miesięcy";
$lang["WEEKS"] = "tygodni";
$lang["DAYS"] = "dni";
$lang["HOURS"] = "godzin";
$lang["MINUTES"] = "minut";

$lang["GO"] = "Przejdź";

$lang["PATHCOMPARISON"] = "Porównywanie katalogów";
$lang["COMPAREPATHS"] = "Porównaj katalogi";
$lang["COMPAREREVS"] = "Porównaj wersje";
$lang["PROPCHANGES"] = "Zmiany właściwości :";
$lang["CONVFROM"] = "Poniższe zestawienie pokazuje zmiany konieczne by
zaktualizować katalog";
$lang["TO"] = "na";
$lang["REVCOMP"] = "Odwróć porównanie";
$lang["COMPPATH"] = "Porównaj katalog:";
$lang["WITHPATH"] = "Z katalogiem:";
$lang["FILEDELETED"] = "Plik usunięty";
$lang["FILEADDED"] = "Nowy plik";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
