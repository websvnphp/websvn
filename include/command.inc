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
// command.inc
//
// External command handling

function transChars($str)
{
   global $config;
   
   if ($config->serverIsWindows)
   {
      //$output = iconv("cp850", "iso-8859-1", $ooutput]);
      return htmlentities(strtr($str, "…‚Šˆ‹Œ“—–‡", "àéèêïîôùûç"));
   }
   
   return $str;
}

function transArray($arr)
{
   global $config;
   
   $narr = array();
   
   if ($config->serverIsWindows)
   {
      foreach ($arr as $key => $value)
      {
         //$output = iconv("cp850", "iso-8859-1", $ooutput]);
         $narr[$key] = htmlentities(strtr(rtrim($value), "…‚Šˆ‹Œ“—–‡", "àéèêïîôùûç"));
      }
   }
   else
   {
      foreach ($arr as $key => $value)
      {
         $narr[$key] = htmlentities(rtrim($value));
      }
   }
      
   return $narr;
}

function runCommand($cmd)
{
   global $lang;
   
   $output = array ();
   $err = false;
   
   // Try to run the command normally
   if ($handle = popen($cmd, "r"))
   {
      $firstline = true;
		while (!feof($handle))
		{
		   $line = fgets($handle);
		   if ($firstline && $line == "")
		   {
		      $err = true;
		   }
		   $firstline = false;
		   $output[] = transChars(rtrim($line));
		}
		
		pclose($handle);
		if (!$err)
		   return $output;
   }

   echo "${lang["BADCMD"]}: <i><b>$cmd</b></i><br>";
   
   // Rerun the command, this time grabbing the error information
   $output = transChars(shell_exec($cmd."  2>&1"));
   if (!empty($output)) print "<p>".nl2br($output);
   exit;
}

?>