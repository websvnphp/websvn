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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
//
// --
//
// search.php
//
// Show the search listing for the given term in repository/path/revision

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';
require_once 'include/template.php';
require_once 'include/bugtraq.php';

function removeURLSeparator($url) 
{
	return preg_replace('#(\?|&(amp;)?)$#', '', $url);
}

function urlForPath($fullpath, $passRevString) 
{
	global $config, $rep;

	$isDir = $fullpath[strlen($fullpath) - 1] == '/';

	if ($isDir) 
	{
		if ($config->treeView)
		{
			$url = $config->getURL($rep, $fullpath, 'dir').$passRevString;
			$id = anchorForPath($fullpath);
			$url .= '#'.$id.'" id="'.$id;
		} 
		else
		{
			$url = $config->getURL($rep, $fullpath, 'dir').$passRevString;
		}
	} 
	else 
	{
		$url = $config->getURL($rep, $fullpath, 'file').$passRevString;
	}

	return removeURLSeparator($url);
}

function showSearchResults($svnrep, $path, $searchstring, $rev, $peg, $listing,  $index=0, $treeview = true) 
{

	global $config, $lang, $rep, $passrev, $peg, $passRevString;

	// List each file in the current directory
	$loop = 0;
	$last_index = 0;
	$accessToThisDir = $rep->hasReadAccess($path, false);

	$openDir = false;
	$logList = $svnrep->getListSearch($path, $searchstring, $rev, $peg);
	
	if (!$logList)
	{
		return $listing;
	}

	$downloadRevAndPeg = createRevAndPegString($rev, $peg ? $peg : $rev);

	foreach ($logList->entries as $entry) 
	{
		$isDir = $entry->isdir;
		$file = $entry->file;
		$isDirString = ($isDir) ? 'isdir=1&amp;' : '';

		// Only list files/directories that are not designated as off-limits
		$access = ($isDir)	? $rep->hasReadAccess($path.$file, false)
							: $accessToThisDir;

		if (!$access)
		{
			continue;
		}

		$listvar = &$listing[$index];
		$listvar['rowparity'] = $index % 2;

		if ($isDir) 
		{
			$listvar['filetype'] = ($openDir) ? 'diropen' : 'dir';
			$openDir = true;
		}
		else 
		{
			$listvar['filetype'] = strtolower(strrchr($file, '.'));
			$openDir = false;
		}

		$listvar['isDir'] = $isDir;
		$listvar['openDir'] = $openDir;
		$listvar['path'] = $path.$file;

		$tempelements = explode('/',$file);

		if ($tempelements[count($tempelements)-1] === "")
		{
			$lastindexfile = count($tempelements)-1 - 1;
			$listvar['node'] = $lastindexfile; // t-node
			$listvar['level'] = ($treeview) ? $lastindexfile : 0;
			$listvar['filename'] = $tempelements[$lastindexfile];
		}
		else
		{
			$lastindexfile = count($tempelements)-1;
			$listvar['node'] = $lastindexfile; // t-node
			$listvar['level'] = ($treeview) ? $lastindexfile : 0;
			$listvar['filename'] = $tempelements[$lastindexfile];
		}

		for ($j=1;$j<=$lastindexfile;$j++)
		{
			$listvar['last_i_node'][$j] = false;
		}

		if ($isDir) 
		{
			$listvar['fileurl'] = urlForPath($path.$file, $passRevString);
		}
		else
		{
			$listvar['fileurl'] = urlForPath($path.$file, createDifferentRevAndPegString($passrev, $peg));
		}

		$listvar['filelink'] = '<a href="'.$listvar['fileurl'].'">'.$listvar['filename'].'</a>';

		if ($isDir) 
		{
			$listvar['logurl'] = $config->getURL($rep, $path.$file, 'log').$isDirString.$passRevString;
		}
		else
		{
			$listvar['logurl'] = $config->getURL($rep, $path.$file, 'log').$isDirString.createDifferentRevAndPegString($passrev, $peg);
		}

		if ($treeview)
		{
			$listvar['compare_box'] = '<input type="checkbox" name="compare[]" value="'.escape($path.$file).'@'.$passrev.'" onclick="enforceOnlyTwoChecked(this)" />';
		}

		if ($config->showLastModInListing()) 
		{
			$listvar['committime'] = $entry->committime;
			$listvar['revision'] = $entry->rev;
			$listvar['author'] = $entry->author;
			$listvar['age'] = $entry->age;
			$listvar['date'] = $entry->date;
			$listvar['revurl'] = $config->getURL($rep, $path.$file, 'revision').$isDirString.createRevAndPegString($entry->rev, $peg ? $peg : $rev);
		}

		if ($rep->isDownloadAllowed($path.$file))
		{
			$downloadurl = $config->getURL($rep, $path.$file, 'dl').$isDirString.$downloadRevAndPeg;

			if ($isDir) 
			{
				$listvar['downloadurl'] = $downloadurl;
				$listvar['downloadplainurl'] = '';
			}
			else
			{
				$listvar['downloadplainurl'] = $downloadurl;
				$listvar['downloadurl'] = '';
			}
		}
		else
		{
			$listvar['downloadplainurl'] = '';
			$listvar['downloadurl'] = '';
		}

		if ($rep->isRssEnabled())
		{
			// RSS should always point to the latest revision, so don't include rev
			$listvar['rssurl'] = $config->getURL($rep, $path.$file, 'rss').$isDirString.createRevAndPegString('', $peg);
		}

		$loop++;
		$index++;
		$last_index = $index;
	}

	return $listing;
}

// Make sure that we have a repository
if (!$rep)
{
	renderTemplate404('directory','NOREP');
}

$svnrep = new SVNRepository($rep);

if (!empty($rev))
{
	$info = $svnrep->getInfo($path, $rev, $peg);

	if ($info) 
	{
		$path = $info->path;
		$peg = (int)$info->rev;
	}
}

$history = $svnrep->getLog($path, 'HEAD', 1, false, 2, ($path == '/') ? '' : $peg);

if (!$history)
{
	unset($vars['error']);
	$history = $svnrep->getLog($path, '', '', false, 2, ($path == '/') ? '' : $peg);
	if (!$history)
	{
		renderTemplate404('directory','NOPATH');
	}
}

$youngest = ($history && isset($history->entries[0])) ? $history->entries[0]->rev : 0;

// Unless otherwise specified, we get the log details of the latest change
$lastChangedRev = ($passrev) ? $passrev : $youngest;

if ($lastChangedRev != $youngest)
{
	$history = $svnrep->getLog($path, $lastChangedRev, 1, false, 2, $peg);
}

$logEntry = ($history && isset($history->entries[0])) ? $history->entries[0] : 0;

$headlog = $svnrep->getLog('/', '', '', true, 1);
$headrev = ($headlog && isset($headlog->entries[0])) ? $headlog->entries[0]->rev : 0;

// If we're not looking at a specific revision, get the HEAD revision number
// (the revision of the rest of the tree display)

if (empty($rev))
{
	$rev = $headrev;
}

if ($path == '' || $path[0] != '/')
{
	$ppath = '/'.$path;
}
else
{
	$ppath = $path;
}

createPathLinks($rep, $ppath, $passrev, $peg);
$passRevString = createRevAndPegString($passrev, $peg);
$isDirString = 'isdir=1&amp;';

$revurl = $config->getURL($rep, $path != '/' ? $path : '', 'dir');
$revurlSuffix = $path != '/' ? '#'.anchorForPath($path) : '';

if ($rev < $youngest)
{
	if ($path == '/')
	{
		$vars['goyoungesturl'] = $config->getURL($rep, '', 'dir');
	}
	else
	{
		$vars['goyoungesturl'] = $config->getURL($rep, $path, 'dir').createRevAndPegString($youngest, $peg ? $peg: $rev).$revurlSuffix;
	}

	$vars['goyoungestlink'] = '<a href="'.$vars['goyoungesturl'].'"'.($youngest ? ' title="'.$lang['REV'].' '.$youngest.'"' : '').'>'.$lang['GOYOUNGEST'].'</a>';

	$history2 = $svnrep->getLog($path, $rev, $youngest, true, 2, $peg);

	if (isset($history2->entries[1]))
	{
		$nextRev = $history2->entries[1]->rev;
		if ($nextRev != $youngest)
		{
			$vars['nextrev'] = $nextRev;
			$vars['nextrevurl'] = $revurl.createRevAndPegString($nextRev, $peg).$revurlSuffix;
		}
	}

	unset($vars['error']);
}

if (isset($history->entries[1]))
{
	$prevRev = $history->entries[1]->rev;
	$prevPath = $history->entries[1]->path;
	$vars['prevrev'] = $prevRev;
	$vars['prevrevurl'] = $revurl.createRevAndPegString($prevRev, $peg).$revurlSuffix;
}

$bugtraq = new Bugtraq($rep, $svnrep, $ppath);

$vars['action'] = '';
$vars['rev'] = $rev;
$vars['peg'] = $peg;
$vars['path'] = escape($ppath);
$vars['lastchangedrev'] = $lastChangedRev;

if ($logEntry)
{
	$vars['date'] = $logEntry->date;
	$vars['age'] = datetimeFormatDuration(time() - strtotime($logEntry->date));
	$vars['author'] = $logEntry->author;
	$vars['log'] = nl2br($bugtraq->replaceIDs(create_anchors(xml_entities($logEntry->msg))));
}

$vars['revurl'] = $config->getURL($rep, ($path == '/' ? '' : $path), 'revision').$isDirString.$passRevString;
$vars['revlink'] = '<a href="'.$vars['revurl'].'">'.$lang['LASTMOD'].'</a>';

if ($history && count($history->entries) > 1)
{
	$vars['compareurl'] = $config->getURL($rep, '', 'comp').'compare[]='.urlencode($history->entries[1]->path).'@'.$history->entries[1]->rev. '&amp;compare[]='.urlencode($history->entries[0]->path).'@'.$history->entries[0]->rev;
	$vars['comparelink'] = '<a href="'.$vars['compareurl'].'">'.$lang['DIFFPREV'].'</a>';
}

$vars['logurl'] = $config->getURL($rep, $path, 'log').$isDirString.$passRevString;
$vars['loglink'] = '<a href="'.$vars['logurl'].'">'.$lang['VIEWLOG'].'</a>';

if ($rep->isRssEnabled())
{
	$vars['rssurl'] = $config->getURL($rep, $path, 'rss').$isDirString.createRevAndPegString('', $peg);
	$vars['rsslink'] = '<a href="'.$vars['rssurl'].'">'.$lang['RSSFEED'].'</a>';
}

// Set up the tarball link
$subs = explode('/', $path);
$level = count($subs) - 2;
if ($rep->isDownloadAllowed($path) && !isset($vars['warning']))
{
	$vars['downloadurl'] = $config->getURL($rep, $path, 'dl').$isDirString.$passRevString;
}

$vars['compare_form'] = '<form method="get" action="'.$config->getURL($rep, '', 'comp').'" id="compare">';

if ($config->multiViews)
{
	$vars['compare_form'] .= '<input type="hidden" name="op" value="comp"/>';
}
else
{
	$vars['compare_form'] .= '<input type="hidden" name="repname" value="'.$repname.'" />';
}

$vars['compare_submit'] = '<input type="submit" value="'.$lang['COMPAREPATHS'].'" />';
$vars['compare_endform'] = '</form>';

$vars['showlastmod'] = $config->showLastModInListing();

if ($_GET["search"] === NULL)
{
	$vars['warning'] = $lang['NOSEARCHTERM'];
	createSearchSelectionForm();
}
else
{
	createSearchSelectionForm();
	$vars['compare_box'] = '';
	$listing = showSearchResults($svnrep, $path, $_GET["search"], $rev, $peg, array(),0,$config->treeView);
}

if (!$rep->hasReadAccess($path))
{
	$vars['error'] = $lang['NOACCESS'];
	sendHeaderForbidden();
}

$vars['restricted'] = !$rep->hasReadAccess($path, false);

renderTemplate('directory');
