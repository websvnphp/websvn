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
// dl.php
//
// Create gz/tar files of the requested item

require("include/setup.inc");
require("include/svnlook.inc");
require("include/template.inc");

// A function to clean up what we've been doing

function cleanup($dirname)
{
   if ($handle = opendir($dirname))
   {
      // For each file...
      while (false !== ($file = readdir($handle)))
      {
         if ($file != "." && $file != "..")
         {  
            if (is_dir($dirname.DIRECTORY_SEPARATOR.$file))
            {
               if (!cleanup($dirname.DIRECTORY_SEPARATOR.$file))
                  return false;
            }
            else
            {
               if (!@unlink($dirname.DIRECTORY_SEPARATOR.$file))
                  return false;
            }
         }
      }
      
      closedir($handle);
      @rmdir($dirname);
      return true;
   }
   else
      return false;
}

$rep = (int)@$_REQUEST["rep"];
$path = escapeshellcmd(@$_REQUEST["path"]);
$rev = (int)@$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;

// Make sure that we have a repository
if (!isset($rep))
{
   echo $lang["NOREP"];
   exit;
}

list ($repname, $reppath) = $config->getRepository($rep);
$svnrep = new SVNRepository($reppath);

if ($path{0} != "/")
   $ppath = "/".$path;
else
   $ppath = $path;

// If there's no revision info, go to the lastest revision for this path
$history = $svnrep->getHistory($path);
$youngest = $history[0]["rev"];

if (empty($rev))
   $rev = $youngest;

// Create a temporary directory.  Here we have an unavoidable but highly
// unlikely to occure race condition

$tmpname = tempnam("temp", "wsvn");
unlink($tmpname);
if (mkdir($tmpname))
{
   // Get the name of the directory being archived
   $arcname = substr($path, 0, -1);
   $arcname = basename($arcname);

   if (!mkdir($tmpname.DIRECTORY_SEPARATOR.$arcname))
   {
      print "Unable to create temporary directory - '$tmpname".DIRECTORY_SEPARATOR."$arcname'";
      //cleanup($tmpname);
      exit;
   }

   // Get the list of files/directories that we need to create
   
   $files = $svnrep->dirContents($path, $rev, true);
   $dirs = array();
   $level = 0;
   
   $curdir = $tmpname.DIRECTORY_SEPARATOR.$arcname.DIRECTORY_SEPARATOR;
   array_push($dirs, $curdir);
   
   foreach ($files as $name)
   {
      $isDir = $name{strlen($name) - 1} == "/";

      // Get the directory level      
      $spaces = 0;
      while ($name{$spaces} == " ")
         $spaces++;
      
      // Go up a directory if necessary   
      if ($spaces < $level)
      {
         $diff = $level - $spaces;
         while ($diff)
         {
            $curdir = array_pop($dirs);
            $diff--;
            $level--;
         }
      }

      $name = trim($name);

      if ($isDir)
      {
         array_push($dirs, $curdir);
         
         // Remove generique directory separator
         $name = substr($name, 0, -1);
         
         if (!mkdir($curdir.$name))
         {
            print "Unable to create temporary directory - '$tmpname".DIRECTORY_SEPARATOR."$name'";
            cleanup($tmpname);
            exit;
         }
         
         $curdir .= $name.DIRECTORY_SEPARATOR;
         $level = $spaces + 1;
      }
      else
      {         
         // Output the contents of the file
         $svnrep->getFileContents(quote($path.$name), quote($curdir.$name), $rev, true);
      }
   }
   
   // Create the tar file
   chdir($tmpname);
   exec($config->tar." -cf $arcname.tar $arcname");
   
   // ZIP it up
   exec($config->gzip." $arcname.tar");
   $size = filesize("$arcname.tar.gz");

   // Give the file to the browser

   if ($fp = @fopen("$arcname.tar.gz","rb"))
   {
      header("Content-Type: application/x-gzip");
      header("Content-Length: $size");
      header("Content-Disposition: attachment; filename=$arcname.tar.gz");
      @fpassthru($fp);
   }
   else
   {
      print "Unable to open file $arcname.tar.gz";
   }
   
   fclose($fp);
   
   chdir("..");
   cleanup($tmpname);
}
else
   print "Unable to create temporary directory";
   
?>