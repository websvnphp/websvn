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
  header('HTTP/1.x 403 Forbidden', true, 403);
  print 'Unable to download path '.$path."\n";
  exit;
}

$svnrep = new SVNRepository($rep);

// Fetch information about latest revision for this path
if (empty($rev)) {
  $history = $svnrep->getLog($path, 'HEAD', '', true, 1);
} else {
  $history = $svnrep->getLog($path, $rev, $rev - 1, true, 1);
}
if (is_string($history)) {
  echo $history;
  exit;
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
  $isDir = (substr($arcname, -1) == '/');
  if ($isDir) {
    $arcname = substr($arcname, 0, -1);
  }
  $arcname = basename($arcname);
  if ($arcname == '') {
    $arcname = $rep->name;
  }

  $plainfilename = $arcname;
  $arcname = $arcname.'.r'.$rev;

  $svnrep->exportDirectory($path, $tmpname.DIRECTORY_SEPARATOR.$arcname, $rev);

  // Set datetime of exported directory (and subdirectories) to datetime of revision so that every archive is equal
  $date = $logEntry->date;
  $ts = mktime(substr($date, 11, 2), substr($date, 14, 2), substr($date, 17, 2), substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
  setDirectoryTimestamp($tmpname.DIRECTORY_SEPARATOR.$arcname, $ts);

  // change to temp directory so that only relative paths are stored in tar
  chdir($tmpname);

  if ($isDir) {
    $dlmode = $config->getDefaultFolderDlMode();
  } else {
    $dlmode = $config->getDefaultFileDlMode();
  }

  // $_REQUEST parameter can override dlmode
  if (!empty($_REQUEST['dlmode'])) {
    $dlmode = $_REQUEST['dlmode'];
    if (substr($logEntry->path, -1) == '/') {
      if (!in_array($dlmode, $config->validFolderDlModes)) {
        $dlmode = $config->getDefaultFolderDlMode();
      }
    } else {
      if (!in_array($dlmode, $config->validFileDlModes)) {
        $dlmode = $config->getDefaultFileDlMode();
      }
    }
  }

  if ($dlmode == 'plain') {
    $dlarc  = $arcname;
    $dlmime = 'application/octetstream';

  } else if ($dlmode == 'zip') {
    $dlarc  = $arcname.'.zip';
    $dlmime = 'application/x-zip';
    // Create zip file
    $cmd = $config->zip.' -r '.quote($dlarc).' '.quote($arcname);
    execCommand($cmd, $retcode);
    if ($retcode != 0) {
      print'Unable to call zip command "'.$config->zip.'"';
    }

  } else {
    $tararc = $arcname.'.tar';
    $dlarc = $arcname.'.tar.gz';
    $dlmime = 'application/x-gzip';

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

    // GZIP it up
    if (function_exists('gzopen')) {
      $srcHandle = fopen($tmpname.DIRECTORY_SEPARATOR.$tararc, 'rb');
      $dstHandle = gzopen($tmpname.DIRECTORY_SEPARATOR.$dlarc, 'wb');
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
  }

  // Give the file to the browser
  if (is_readable($dlarc)) {
    $size = filesize($dlarc);

    if ($dlmode == 'plain') {
      $dlfilename = $plainfilename;
    } else {
      $dlfilename = $rep->name.'-'.$dlarc;
    }

    header('Content-Type: '.$dlmime);
    header('Content-Length: '.$size);
    header('Content-Disposition: attachment; filename="'. $dlfilename .'"');

    readfile($dlarc);

  } else {
    header('HTTP/1.x 404 Not Found', true, 404);

    print 'Unable to open file '.$dlarc."\n";
  }

  chdir('..');

  removeDirectory($tmpname);
}
