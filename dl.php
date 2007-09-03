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

// Make sure that this operation is allowed

if (!$rep->isDownloadAllowed($path))
   exit;

$svnrep = new SVNRepository($rep);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getLog($path, "", "", true);
$youngest = $history->entries[0]->rev;

if (empty($rev))
   $rev = $youngest;

// Create a temporary directory.  Here we have an unavoidable but highly
// unlikely to occure race condition

$tmpname = tempnam("temp", "wsvn");
@unlink($tmpname);

if (mkdir($tmpname))
{
   // Get the name of the directory being archived
   $arcname = substr($path, 0, -1);
   $arcname = basename($arcname);
   if (empty($arcname))
      $arcname = $rep->name;
      
   $arcname = $arcname . ".r$rev";

   $svnrep->exportDirectory($path, $tmpname.DIRECTORY_SEPARATOR.$arcname, $rev);
   
   // Create the tar file
   chdir($tmpname);
   exec(quoteCommand($config->tar." -cf ".quote("$arcname.tar")." ".quote($arcname)));
   
   // ZIP it up
   exec(quoteCommand($config->gzip." ".quote("$arcname.tar")));
   $size = filesize("$arcname.tar.gz");

   // Give the file to the browser

   if ($fp = @fopen("$arcname.tar.gz","rb"))
   {
      header("Content-Type: application/x-gzip");
      header("Content-Length: $size");
      header("Content-Disposition: attachment; filename=\"".$rep->name."-$arcname.tar.gz\"");
      // Use a loop to transfer the data  4KB at a time.
      while(!feof($fp))
      {
         echo fread($fp, 4096);
         ob_flush();
      }
   }
   else
   {
      print "Unable to open file $arcname.tar.gz";
   }
   
   fclose($fp);
   
   chdir("..");

   // Delete the directory.  Why doesn't PHP have a generic recursive directory
   // deletion command?  It's stupid.

   if ($config->serverIsWindows)
   {
      $cmd = quoteCommand("rmdir /S /Q ".quote($tmpname));
   }
   else
   {
      $cmd = quoteCommand("rm -rf ".quote($tmpname));
   }
   
   @exec($cmd);
}
?>
