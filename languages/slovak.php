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
$lang["LANGUAGENAME"] = "Slovak";
// This is the RFC 2616 (§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'sk';

$lang["LOG"] = "Log";
$lang["DIFF"] = "Diff";

$lang["NOREP"] = "Neurčené úložisko";
$lang["NOPATH"] = "Cesta nebola nájdená";
$lang["NOACCESS"] = "Nemáte oprávnenie pre čítanie tohto adresára";
$lang["RESTRICTED"] = "Obmedzený prístup";
$lang["SUPPLYREP"] = "Prosím nastavte cestu k úložisku v include/config.php pomocou \$config->parentPath alebo \$config->addRepository<p>Pre ďalšie detaily konzultujte inštalačnú príručku";

$lang["DIFFREVS"] = "Rozdiel medzi revíziami";
$lang["AND"] = "a";
$lang["REV"] = "Rev";
$lang["LINE"] = "Riadok";
$lang["SHOWENTIREFILE"] = "Ukáž celý súbor";
$lang["SHOWCOMPACT"] = "Ukáž len oblasti s rozdielmi";

$lang["FILEDETAIL"] = "Podrobnosti";
$lang["DIFFPREV"] = "Porovnaj s predchádzajúcou";
$lang["BLAME"] = "Blame";

$lang["REVINFO"] = "Informácie o revízii";
$lang["GOYOUNGEST"] = "Choď na aktuálnu revíziu";
$lang["LASTMOD"] = "Posledná modifikácia";
$lang["LOGMSG"] = "Správa denníka";
$lang["CHANGES"] = "Zmeny";
$lang["SHOWCHANGED"] = "Ukáž zmenené súbory";
$lang["HIDECHANGED"] = "Skry smenené súbory";
$lang["NEWFILES"] = "Nové súbory";
$lang["CHANGEDFILES"] = "Zmenené súbory";
$lang["DELETEDFILES"] = "Odstránené súbory";
$lang["VIEWLOG"] = "Zobraz&nbsp;denník";
$lang["PATH"] = "Cesta";
$lang["AUTHOR"] = "Autor";
$lang["AGE"] = "Vek";
$lang["LOG"] = "Denník";
$lang["CURDIR"] = "Aktuálny priečinok";
$lang["TARBALL"] = "Tarball";

$lang["PREV"] = "Predch";
$lang["NEXT"] = "Ďalší";
$lang["SHOWALL"] = "Ukáž všetko";

$lang["BADCMD"] = "Chyba pri vykonávaní tohto príkazu";
$lang["UNKNOWNREVISION"] = "Revízia nebola nájdená";

$lang["POWERED"] = "Powered by <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion&nbsp;úložiská";
$lang["SERVER"] = "Subversion&nbsp;Server";

$lang["FILTER"] = "Možnosti filtrovania";
$lang["STARTLOG"] = "Od rev.";
$lang["ENDLOG"] = "Po rev.";
$lang["MAXLOG"] = "Max. rev.";
$lang["SEARCHLOG"] = "Hľadaj";
$lang["CLEARLOG"] = "Zmaž aktuálny filter";
$lang["MORERESULTS"] = "Nájdi viac zhôd...";
$lang["NORESULTS"] = "Vašej otázke nezodpovedajú žiadne záznamy denníka";
$lang["NOMORERESULTS"] = "Vašej otázke zodpovedá viac záznamov denníka";
$lang['NOPREVREV'] = 'Žiadna predchádzajúca revízia';

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "súbor(y) zmenený(/é)";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "Riadok č.";
$lang["BLAMEFOR"] = "Blame informácie pre rev.";

$lang["DAYLETTER"] = "d";
$lang["HOURLETTER"] = "h";
$lang["MINUTELETTER"] = "m";
$lang["SECONDLETTER"] = "s";

$lang["GO"] = "Choď";

$lang["PATHCOMPARISON"] = "Porovnanie ciest";
$lang["COMPAREPATHS"] = "Porovnaj cesty";
$lang["COMPAREREVS"] = "Porovnaj revízie";
$lang["PROPCHANGES"] = "Zmeny vlastností :";
$lang["CONVFROM"] = "Toto porovnanie ukazuje zmeny potrebné na zmenu cesty ";
$lang["TO"] = "NA";
$lang["REVCOMP"] = "Obrátené porovnanie";
$lang["COMPPATH"] = "Porovnaj cestu:";
$lang["WITHPATH"] = "S cestou:";
$lang["FILEDELETED"] = "Súbor odstránený";
$lang["FILEADDED"] = "Nový súbor";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
