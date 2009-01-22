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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// --
//
// dl.php
//
// Create gz/tar files of the requested item

require_once("include/setup.php");
require_once("include/svnlook.php");
require_once("include/utils.php");

ini_set('include_path', $locwebsvnreal.'/lib/pear'.$config->pathSeparator.ini_get('include_path'));
@include_once("Archive/Tar.php");

function setDirectoryTimestamp($dir, $ts) {
  global $config;
  // changing the modification time of a directory under windows is only supported since php 5.3.0
  if (!$config->serverIsWindows || version_compare(PHP_VERSION, '5.3.0alpha') !== -1) {
    touch($dir, $ts);

    $handle = opendir($dir);
    if ($handle) {
      while (($file = readdir($handle)) !== false) {
        if ($file == '.' || $file == '..') {
          continue;
        }
        $f = $dir.DIRECTORY_SEPARATOR.$file;
        if (is_dir($f)) {
          setDirectoryTimestamp($f, $ts);
        }
      }
      closedir($handle);
    }
  }
}

function removeDirectory($dir) {
  if (is_dir($dir)) {
    $dir = rtrim($dir, '/');
    $handle = dir($dir);
    while (($file = $handle->read()) !== false) {
      if ($file == '.' || $file == '..') {
        continue;
      }

      $f = $dir.DIRECTORY_SEPARATOR.$file;
      if (!is_link($f) && is_dir($f)) {
        removeDirectory($f);
      } else {
        @unlink($f);
      }
    }
    $handle->close();
    @rmdir($dir);
    return true;
  }
  return false;
}

// Make sure that this operation is allowed

if (!$rep->isDownloadAllowed($path)) {
  exit;
}

$svnrep = new SVNRepository($rep);

// Fetch information about latest revision for this path
if (empty($rev)) {
  $history = $svnrep->getLog($path, 'HEAD', '', true, 1);
} else {
  $history = $svnrep->getLog($path, $rev, $rev - 1, true, 1);
}
$logEntry = $history->entries[0];

if (empty($rev)) {
  $rev = $logEntry->rev;
}

// Create a temporary directory.  Here we have an unavoidable but highly
// unlikely to occure race condition

$tmpname = tempnam($config->getTarballTmpDir(), 'wsvn');
@unlink($tmpname);

if (mkdir($tmpname)) {
  // Get the name of the directory being archived
  $arcname = $path;
  if (substr($arcname, -1) == '/') {
    $arcname = substr($arcname, 0, -1);
  }
  $arcname = basename($arcname);
  if ($arcname == '') {
    $arcname = $rep->name;
  }

  $arcname = $arcname.'.r'.$rev;
  $tararc = $arcname.'.tar';
  $gzarc = $arcname.'.tar.gz';

  $svnrep->exportDirectory($path, $tmpname.DIRECTORY_SEPARATOR.$arcname, $rev);

  // Set datetime of exported directory (and subdirectories) to datetime of revision so that every archive is equal
  $date = $logEntry->date;
  $ts = mktime(substr($date, 11, 2), substr($date, 14, 2), substr($date, 17, 2), substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
  setDirectoryTimestamp($tmpname.DIRECTORY_SEPARATOR.$arcname, $ts);

  // change to temp directory so that only relative paths are stored in tar
  chdir($tmpname);

  // Create the tar file
  $retcode = 0;
  if (class_exists('Archive_Tar')) {
    $tar = new Archive_Tar($tararc);
    $created = $tar->create($arcname);
    if (!$created) {
      $retcode = 1;
      print'Unable to create tar archive';
    }

  } else {
    $cmd = $config->tar.' -cf '.quote($tararc).' '.quote($arcname);
    execCommand($cmd, $retcode);
    if ($retcode != 0) {
      print'Unable to call tar command "'.$config->tar.'"';
    }
  }
  if ($retcode != 0) {
    chdir('..');
    removeDirectory($tmpname);
    exit(0);
  }

  // Set datetime of tar file to datetime of revision
  touch($tararc, $ts);

  // ZIP it up
  if (function_exists('gzopen')) {
    $srcHandle = fopen($tmpname.DIRECTORY_SEPARATOR.$tararc, 'rb');
    $dstHandle = gzopen($tmpname.DIRECTORY_SEPARATOR.$gzarc, 'wb');
    if (!$srcHandle || !$dstHandle) {
      print'Unable to open file for gz-compression';
      chdir('..');
      removeDirectory($tmpname);
      exit(0);
    }
    while (!feof($srcHandle)) {
      gzwrite($dstHandle, fread($srcHandle, 1024 * 512));
    }
    fclose($srcHandle);
    gzclose($dstHandle);

  } else {
    $cmd = $config->gzip.' '.quote($tararc);
    $retcode = 0;
    execCommand($cmd, $retcode);
    if ($retcode != 0) {
      print'Unable to call gzip command "'.$config->gzip.'"';
      chdir('..');
      removeDirectory($tmpname);
      exit(0);
    }
  }

  // Give the file to the browser
  if (is_readable($gzarc)) {
    $size = filesize($gzarc);

    header('Content-Type: application/x-gzip');
    header('Content-Length: '.$size);
    header('Content-Disposition: attachment; filename="'.$rep->name.'-'.$gzarc.'"');

    readfile($gzarc);
  } else {
    print'Unable to open file '.$gzarc;
  }

  chdir('..');

  removeDirectory($tmpname);
}
