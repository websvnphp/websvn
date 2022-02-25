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
// dl.php
//
// Allow for file and directory downloads, creating zip/tar/gz files if needed.

require_once 'include/setup.php';
require_once 'include/svnlook.php';
require_once 'include/utils.php';

if (!defined('USE_AUTOLOADER')) {
	@include_once 'Archive/Tar.php';
}

function setDirectoryTimestamp($dir, $timestamp) {
	global $config;
	// Changing the timestamp of a directory in Windows is only supported in PHP 5.3.0+
	touch($dir, $timestamp);
	if (is_dir($dir)) {
		// Set timestamp for all contents, recursing into subdirectories
		$handle = opendir($dir);
		if ($handle) {
			while (($file = readdir($handle)) !== false) {
				if ($file == '.' || $file == '..') {
					continue;
				}
				$f = $dir.DIRECTORY_SEPARATOR.$file;
				if (is_dir($f)) {
					setDirectoryTimestamp($f, $timestamp);
				}
			}
			closedir($handle);
		}
	}
}

// Make sure that downloading the specified file/directory is permitted

if (!$rep->isDownloadAllowed($path)) {
	http_response_code(403);
	error_log('Unable to download resource at path: '.$path);
	print 'Unable to download resource at path: '.xml_entities($path);
	exit;
}

if (!$rep)
{
	http_response_code(404);
	exit;
}

$svnrep = new SVNRepository($rep);

// Fetch information about a revision (if unspecified, the latest) for this path
if (empty($rev))
{
	$history = $svnrep->getLog($path, 'HEAD', '', true, 1, $peg);
}
else if ($rev == $peg)
{
	$history = $svnrep->getLog($path, '', 1, true, 1, $peg);
}
else
{
	$history = $svnrep->getLog($path, $rev, $rev - 1, true, 1, $peg);
}

$logEntry = ($history) ? $history->entries[0] : null;

if (!$logEntry)
{
	http_response_code(404);
	error_log('Unable to download resource at path: '.$path);
	print 'Unable to download resource at path: '.xml_entities($path);
	exit(0);
}

if (empty($rev))
{
	$rev = $logEntry->rev;
}

// Create a temporary filename to be used for a directory to archive a download.
// Here we have an unavoidable but highly unlikely to occur race condition.
$tempDir = tempnamWithCheck($config->getTempDir(), 'websvn');

@unlink($tempDir);
mkdir($tempDir);
// Create the name of the directory being archived
$archiveName = $path;
$isDir = (substr($archiveName, -1) == '/');

if ($isDir)
{
	$archiveName = substr($archiveName, 0, -1);
}

$archiveName = basename($archiveName);

if ($archiveName == '')
{
	$archiveName = $rep->name;
}

$plainfilename = $archiveName;
$archiveName .= '.r'.$rev;

// Export the requested path from SVN repository to the temp directory
$svnExportResult = $svnrep->exportRepositoryPath($path, $tempDir.DIRECTORY_SEPARATOR.$archiveName, $rev, $peg);

if ($svnExportResult != 0)
{
	http_response_code(500);
	error_log('svn export failed for: '.$archiveName);
	print 'svn export failed for "'.xml_entities($archiveName).'".';
	removeDirectory($tempDir);
	exit(0);
}

// For security reasons, disallow direct downloads of filenames that
// are a symlink, since they may be a symlink to anywhere (/etc/passwd)
// Deciding whether the symlink is relative and legal within the
// repository would be nice but seems to error prone at this moment.
if ( is_link($tempDir.DIRECTORY_SEPARATOR.$archiveName) )
{
	http_response_code(500);
	error_log('to be downloaded file is symlink, aborting: '.$archiveName);
	print 'Download of symlinks disallowed: "'.xml_entities($archiveName).'".';
	removeDirectory($tempDir);
	exit(0);
}

// Set timestamp of exported directory (and subdirectories) to timestamp of
// the revision so every archive of a given revision has the same timestamp.
$revDate = $logEntry->date;
$timestamp = mktime(substr($revDate, 11, 2), // hour
										substr($revDate, 14, 2), // minute
										substr($revDate, 17, 2), // second
										substr($revDate, 5, 2), // month
										substr($revDate, 8, 2), // day
										substr($revDate, 0, 4)); // year
setDirectoryTimestamp($tempDir, $timestamp);

// Change to temp directory so that only relative paths are stored in archive.
$oldcwd = getcwd();
chdir($tempDir);

if ($isDir)
{
	$downloadMode = $config->getDefaultDirectoryDlMode();
}
else
{
	$downloadMode = $config->getDefaultFileDlMode();
}

// $_REQUEST parameter can override dlmode
if (!empty($_REQUEST['dlmode']))
{
	$downloadMode = $_REQUEST['dlmode'];

	if (substr($logEntry->path, -1) == '/')
	{
		if (!in_array($downloadMode, $config->validDirectoryDlModes))
		{
			$downloadMode = $config->getDefaultDirectoryDlMode();
		}
	}
	else
	{
		if (!in_array($downloadMode, $config->validFileDlModes))
		{
			$downloadMode = $config->getDefaultFileDlMode();
		}
	}
}

$downloadArchive = $archiveName;

if ($downloadMode == 'plain')
{
	$downloadMimeType = 'application/octet-stream';

}
else if ($downloadMode == 'zip')
{
	$downloadMimeType = 'application/zip';
	$downloadArchive .= '.zip';
	// Create zip file
	$cmd = $config->zip.' --symlinks -r '.quote($downloadArchive).' '.quote($archiveName);
	execCommand($cmd, $retcode);

	if ($retcode != 0)
	{
		error_log('Unable to call zip command: '.$cmd);
		print 'Unable to call zip command. See webserver error log for details.';
	}
}
else
{
	$downloadMimeType = 'application/gzip';
	$downloadArchive .= '.tar.gz';
	$tarArchive = $archiveName.'.tar';

	// Create the tar file
	$retcode = 0;

	if (class_exists('Archive_Tar'))
	{
		$tar = new Archive_Tar($tarArchive);
		$created = $tar->create(array($archiveName));

		if (!$created)
		{
			$retcode = 1;
			http_response_code(500);
			print 'Unable to create tar archive.';
		}
	}
	else
	{
		$cmd = $config->tar.' -cf '.quote($tarArchive).' '.quote($archiveName);
		execCommand($cmd, $retcode);

		if ($retcode != 0)
		{
			http_response_code(500);
			error_log('Unable to call tar command: '.$cmd);
			print 'Unable to call tar command. See webserver error log for details.';
		}
	}

	if ($retcode != 0)
	{
		chdir($oldcwd);
		removeDirectory($tempDir);
		exit(0);
	}

	// Set timestamp of tar file to timestamp of revision
	touch($tarArchive, $timestamp);

	// GZIP it up
	if (function_exists('gzopen'))
	{
		$srcHandle = fopen($tarArchive, 'rb');
		$dstHandle = gzopen($downloadArchive, 'wb');

		if (!$srcHandle || !$dstHandle)
		{
			http_response_code(500);
			print 'Unable to open file for gz-compression.';
			chdir($oldcwd);
			removeDirectory($tempDir);
			exit(0);
		}

		while (!feof($srcHandle))
		{
			gzwrite($dstHandle, fread($srcHandle, 1024 * 512));
		}

		fclose($srcHandle);
		gzclose($dstHandle);
	}
	else
	{
		$cmd = $config->gzip.' '.quote($tarArchive);
		$retcode = 0;
		execCommand($cmd, $retcode);

		if ($retcode != 0)
		{
			http_response_code(500);
			error_log('Unable to call gzip command: '.$cmd);
			print 'Unable to call gzip command. See webserver error log for details.';
			chdir($oldcwd);
			removeDirectory($tempDir);
			exit(0);
		}
	}
}

// Give the file to the browser
if (is_readable($downloadArchive))
{
	if ($downloadMode == 'plain')
	{
		$downloadFilename = $plainfilename;
	}
	else
	{
		$downloadFilename = $rep->name.'-'.$downloadArchive;
	}

	header('Content-Type: '.$downloadMimeType);
	header('Content-Length: '.filesize($downloadArchive));
	header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($downloadFilename));
	readfile($downloadArchive);
}
else
{
	http_response_code(404);
	print 'Unable to open file: '.xml_entities($downloadArchive);
}

chdir($oldcwd);
removeDirectory($tempDir);
