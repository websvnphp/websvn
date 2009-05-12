<?php

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004 Erik Le Blanc
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
// Translated by Yokav (mailme@yokav.info)
//
// french.php
//
// French language strings

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "Francais";
// This is the RFC 2616 (§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'fr';

$lang["LOG"] = "Journal";
$lang["DIFF"] = "Diff&eacute;.";

$lang["NOREP"] = "Pas de d&eacute;p&ocirc;t fourni";
$lang["NOPATH"] = "R&eacute;pertoire non trouv&eacute;";
$lang["NOACCESS"] = "Vous n'avez pas la permission n&eacute;cessaire pour acc&eacute;der &agrave; ce r&eacute;pertoire";
$lang["RESTRICTED"] = "Acc&egrave;s restreint";
$lang["SUPPLYREP"] = "Veuillez indiquer le r&eacute;pertoire d'un d&eacute;p&ocirc;t dans le fichier include/config.php en utilisant \$config->parentPath ou \$config->addRepository<p>Lire le guide d'installation pour plus de d&eacute;tails";

$lang["DIFFREVS"] = "Diff&eacute;rences entre les r&eacute;visions";
$lang["AND"] = "et";
$lang["REV"] = "R&eacute;vision";
$lang["LINE"] = "Ligne";
$lang["SHOWENTIREFILE"] = "Afficher tout le fichier";
$lang["SHOWCOMPACT"] = "Afficher seulement les passages avec des diff&eacute;rences";
$lang["IGNOREWHITESPACE"] = "Ignorer les espaces blanc";
$lang["REGARDWHITESPACE"] = "Prendre en compte les espaces blanc";

$lang["LISTING"] = "Arborescence";
$lang["FILEDETAIL"] = "D&eacute;tails";
$lang["DIFFPREV"] = "Diff&eacute;rence avec la pr&eacute;c&eacute;dente";
$lang["BLAME"] = "Responsabilit&eacute;";

$lang["REVINFO"] = "Information sur la R&eacute;vision";
$lang["GOYOUNGEST"] = "Aller &agrave; la r&eacute;vision la plus r&eacute;cente";
$lang["LASTMOD"] = "Derni&egrave;re modification";
$lang["LOGMSG"] = "Message du journal";
$lang["CHANGES"] = "Changements";
$lang["SHOWCHANGED"] = "Montrer les fichiers modifi&eacute;s";
$lang["HIDECHANGED"] = "Cacher les fichiers modifi&eacute;s";
$lang["NEWFILES"] = "Nouveaux fichiers";
$lang["CHANGEDFILES"] = "Fichiers modifi&eacute;s";
$lang["DELETEDFILES"] = "Fichiers supprim&eacute;s";
$lang["VIEWLOG"] = "Afficher&nbsp;le&nbsp;Journal";
$lang["PATH"] = "Chemin";
$lang["AUTHOR"] = "Auteur";
$lang["AGE"] = "Anciennet&eacute;";
$lang["LOG"] = "Journal";
$lang["CURDIR"] = "R&eacute;pertoire courant";
$lang["TARBALL"] = "Tarball";
$lang["DOWNLOAD"] = "T&eacute;l&eacute;charger";

$lang["PREV"] = "Pr&eacute;c&eacute;dent";
$lang["NEXT"] = "Suivant";
$lang["SHOWALL"] = "Tout montrer";

$lang["BADCMD"] = "Cette commande a provoqu&eacute; une erreur";
$lang["UNKNOWNREVISION"] = "R&eacute;vision non trouv&eacute;e";

$lang["POWERED"] = "Propuls&eacute; par <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "D&eacute;p&ocirc;ts&nbsp;Subversion";
$lang["SERVER"] = "Serveur&nbsp;Subversion";

$lang["FILTER"] = "Options de recherche";
$lang["STARTLOG"] = "De la r&eacute;v.";
$lang["ENDLOG"] = "A la r&eacute;v.";
$lang["MAXLOG"] = "Max r&eacute;vs.";
$lang["SEARCHLOG"] = "Rechercher dans le journal";
$lang["CLEARLOG"] = "Effacer la recherche courante";
$lang["MORERESULTS"] = "Trouver plus de r&eacute;ponses...";
$lang["NORESULTS"] = "Il n'y a pas de r&eacute;ponse &agrave; votre recherche dans le journal";
$lang["NOMORERESULTS"] = "Il n'y a pas plus de r&eacute;ponses &agrave; votre recherche";
$lang["NOPREVREV"] = "Pas de r&eacute;vision ant&eacute;rieur";

$lang["RSSFEEDTITLE"] = "Flux RSS de WebSVN";
$lang["FILESMODIFIED"] = "fichier(s) modifi&eacute;(s)";
$lang["RSSFEED"] = "Flux RSS";

$lang["LINENO"] = "Ligne";
$lang["BLAMEFOR"] = "Dernier responsable";

$lang["DAYLETTER"] = "j";
$lang["HOURLETTER"] = "h";
$lang["MINUTELETTER"] = "m";
$lang["SECONDLETTER"] = "s";

$lang["GO"] = "Go";

$lang["PATHCOMPARISON"] = "Comparaison de dossiers";
$lang["COMPAREPATHS"] = "Comparer les dossiers";
$lang["COMPAREREVS"] = "Comparer les r&eacute;visions";
$lang["PROPCHANGES"] = "Changements de propri&eacute;t&eacute; :";
$lang["CONVFROM"] = "Cette comparaison affiche les changements n&eacute;cessaires pour convertir le dossier ";
$lang["TO"] = "EN";
$lang["REVCOMP"] = "Comparaison inverse";
$lang["COMPPATH"] = "Comparer le dossier :";
$lang["WITHPATH"] = "Au dossier :";
$lang["FILEDELETED"] = "Fichier supprim&eacute;";
$lang["FILEADDED"] = "Nouveau fichier";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
