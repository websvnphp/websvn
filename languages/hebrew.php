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
// Hebrew.php
//
// Hebrew language strings

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "עברית";
// This is the RFC 2616 (?§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'he-IL';

$lang["LOG"] = "לוג";
$lang["DIFF"] = "שוני";

$lang["NOREP"] = "גנזך לא נמצא";
$lang["NOPATH"] = "נתיב לא נמצא";
$lang["NOACCESS"] = "אין לך את ההרשאות הנחוצות לתיקייה זו";
$lang["RESTRICTED"] = "גישה מוגבלת";
$lang["SUPPLYREP"] = 'אנא הוסף את הנתיב לגנזך ב include/config.php ע"י $config->parentPath או $config->addRepository<p>בדוק את מדריך ההתקנה עבור הפרטים';
$lang["DIFFREVS"] = "שוני בין גרסאות";
$lang["AND"] = "ו";
$lang["REV"] = "גרסה";
$lang["LINE"] = "שורה";
$lang["SHOWENTIREFILE"] = "הראה את כל הקובץ";
$lang["SHOWCOMPACT"] = "הראה רק חלקים ששונו";

$lang["DIFFPREV"] = "השווה עם הקודם";
$lang["BLAME"] = "האשם";

$lang["REVINFO"] = "מידע על הגרסה";
$lang["GOYOUNGEST"] = "לך לגרסה האחרונה";
$lang["LASTMOD"] = "שינוי אחרון";
$lang["LOGMSG"] = "מסר הלוג";
$lang["CHANGES"] = "שינויים";
$lang["SHOWCHANGED"] = "הראה קבצים ששונו";
$lang["HIDECHANGED"] = "החבא קבצים ששונו";
$lang["NEWFILES"] = "קבצים חדשים";
$lang["CHANGEDFILES"] = "קבצים ששונו";
$lang["DELETEDFILES"] = "קבצים שנמחקו";
$lang["VIEWLOG"] = "הראה לוג";
$lang["PATH"] = "נתיב";
$lang["AUTHOR"] = "כותב";
$lang["AGE"] = "גיל";
$lang["LOG"] = "לוג";
$lang["CURDIR"] = "תיקייה נוכחית";
$lang["TARBALL"] = "אוסך מכווץ";

$lang["PREV"] = "הקודם";
$lang["NEXT"] = "הבא";
$lang["SHOWALL"] = "הראה הכל";

$lang["BADCMD"] = "שגיאה בהרצת הפקודה";
$lang["UNKNOWNREVISION"] = "גרסה לא נמצאה";

$lang["POWERED"] = "פועל על ידי <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion&nbsp;גנזכי";
$lang["SERVER"] = "Subversion&nbsp;שרת";

$lang["FILTER"] = "אפשרויות חיפוש";
$lang["STARTLOG"] = "מגרסה";
$lang["ENDLOG"] = "עד גרסה";
$lang["MAXLOG"] = "מקסימום גרסאות";
$lang["SEARCHLOG"] = "חפש את";
$lang["CLEARLOG"] = "נקה חיפוש נוכחי";
$lang["MORERESULTS"] = "מצא עוד נתונים...";
$lang["NORESULTS"] = "אין לוגים התואמים את החיפוש שלך";
$lang["NOMORERESULTS"] = "אין יותר לוגים התואמים את החיפוש שלך";
$lang['NOPREVREV'] = 'אין גרסה קודמת';

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "קבצים ששונו";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "מס שורה";
$lang["BLAMEFOR"] = "האשמה בגרסה";

$lang["DAYLETTER"] = "יום";
$lang["HOURLETTER"] = "שעה";
$lang["MINUTELETTER"] = "דקה";
$lang["SECONDLETTER"] = "שניה";

$lang["GO"] = "בצע";

$lang["PATHCOMPARISON"] = "השוואת נתיבים";
$lang["COMPAREPATHS"] = "השווה נתיבים";
$lang["COMPAREREVS"] = "השווה גרסאות";
$lang["PROPCHANGES"] = "שינוי מאפיינים";
$lang["CONVFROM"] = "ההשואה הנוכחית מראה את השינויים הנחוצים להמרת נתיב";
$lang["TO"] = "מ";
$lang["REVCOMP"] = "השוואה הפוכה";
$lang["COMPPATH"] = "השוואת נתיב:";
$lang["WITHPATH"] = "עם נתיב:";
$lang["FILEDELETED"] = "קובץ נמחק";
$lang["FILEADDED"] = "קובץ חדש";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";

