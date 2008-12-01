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
// russian.php
//
// Russian language strings
// by Alexey Chumakov <alex@chumakov.ru>
// UTF-8 encoding

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "Russian";
$lang['LANGUAGETAG'] = 'ru';

$lang["LOG"] = "Журнал";
$lang["DIFF"] = "Различия";

$lang["NOREP"] = "Не задано хранилище";
$lang["NOPATH"] = "Путь не найден";
$lang["NOACCESS"] = "Для чтения этого каталога у вас нет нужных прав";
$lang["RESTRICTED"] = "Ограниченный доступ";
$lang["SUPPLYREP"] = "Пожалуйста, настройте путь к хранилищу в include/config.php, пользуясь \$config->parentPath или \$config->addRepository<p>Дополнительные сведения приведены в руководстве по установке ";

$lang["DIFFREVS"] = "Различия между редакциями";
$lang["AND"] = "и";
$lang["REV"] = "Редакция";
$lang["LINE"] = "Строка";
$lang["SHOWENTIREFILE"] = "показать весь файл";
$lang["SHOWCOMPACT"] = "показать только места с различиями";

$lang["DIFFPREV"] = "сравнить с предыдущей";
$lang["BLAME"] = "авторство";

$lang["REVINFO"] = "Сведения о редакции";
$lang["GOYOUNGEST"] = "перейти к новейшей редакции";
$lang["LASTMOD"] = "Последнее изменение";
$lang["LOGMSG"] = "Запись в журнале";
$lang["CHANGES"] = "Изменения";
$lang["SHOWCHANGED"] = "показать измененные файлы";
$lang["HIDECHANGED"] = "скрыть измененные файлы";
$lang["NEWFILES"] = "Новые файлы";
$lang["CHANGEDFILES"] = "Измененные файлы";
$lang["DELETEDFILES"] = "Удаленные файлы";
$lang["VIEWLOG"] = "открыть&nbsp;журнал";
$lang["PATH"] = "Путь";
$lang["AUTHOR"] = "Автор";
$lang["AGE"] = "Давность";
$lang["LOG"] = "Журнал";
$lang["CURDIR"] = "Текущий каталог";
$lang["TARBALL"] = "Архив";

$lang["PREV"] = "пред.";
$lang["NEXT"] = "след.";
$lang["SHOWALL"] = "показать все";

$lang["BADCMD"] = "Ошибка при выполнении этой команды";
$lang["UNKNOWNREVISION"] = "Редакция не найдена";

$lang["POWERED"] = "Работает на <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Хранилища Subversion&nbsp;";
$lang["SERVER"] = "Сервер Subversion&nbsp;";

$lang["FILTER"] = "Параметры фильтрации";
$lang["STARTLOG"] = "От редакции";
$lang["ENDLOG"] = "До редакции";
$lang["MAXLOG"] = "Макс. редакций";
$lang["SEARCHLOG"] = "искать";
$lang["CLEARLOG"] = "очистить текущий фильтр";
$lang["MORERESULTS"] = "найти еще совпадения...";
$lang["NORESULTS"] = "Нет записей, совпадающих с вашим запросом";
$lang["NOMORERESULTS"] = "Больше нет записей, совпадающих с вашим запросом";

$lang["RSSFEEDTITLE"] = "RSS-канал WebSVN";
$lang["FILESMODIFIED"] = "файл(ов) изменено";
$lang["RSSFEED"] = "канал RSS";

$lang["LINENO"] = "№ строки";
$lang["BLAMEFOR"] = "Сведения об авторстве для редакции ";

$lang["DAYLETTER"] = "д";
$lang["HOURLETTER"] = "ч";
$lang["MINUTELETTER"] = "м";
$lang["SECONDLETTER"] = "с";

$lang["GO"] = "Перейти";

$lang["PATHCOMPARISON"] = "Сравнение путей";
$lang["COMPAREPATHS"] = "cравнить пути";
$lang["COMPAREREVS"] = "Сравнить редакции";
$lang["PROPCHANGES"] = "Изменения свойств :";
$lang["CONVFROM"] = "Такое сравнение показывает изменения, нужные для для преобразования пути ";
$lang["TO"] = "В";
$lang["REVCOMP"] = "обратное сравнение";
$lang["COMPPATH"] = "Сравнить путь:";
$lang["WITHPATH"] = "С путем:";
$lang["FILEDELETED"] = "Файл удален";
$lang["FILEADDED"] = "Новый файл";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

//$lang["NOBR"] = "<nobr>";
//$lang["ENDNOBR"] = "</nobr>";
