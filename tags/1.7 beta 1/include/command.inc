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

function replaceEntities($str)
{
   global $config;
   
   if (version_compare(phpversion(), "4.1.0", "<"))
      $str = htmlentities($str);
   else
      $str = htmlentities($str, ENT_COMPAT, $config->outputEnc);

   return $str;
}

function transChars($str, $doEnt = true)
{
   global $config;
   
   // Try to convert the messages based on the locale information
   if ($config->inputEnc && $config->outputEnc)
   {
      if (function_exists("iconv"))
      {
         $output = @iconv($config->inputEnc, $config->outputEnc, $str);
         if (!empty($output))
            $str = $output;
      }
   }
   
   if ($doEnt) $str = replaceEntities($str);

   return $str;
}

function transArray($arr)
{
   global $config;
   
   $narr = array();
   
   foreach ($arr as $key => $value)
   {
      // Try to convert the messages based on the locale information
      if ($config->inputEnc && $config->outputEnc)
      {
         if (function_exists("iconv"))
         {
            $output = @iconv($config->inputEnc, $config->outputEnc, $value);
            if (!empty($output))
               $value = $output;
         }
      }

      $narr[$key] = replaceEntities(rtrim($value));
   }
      
   return $narr;
}

function quoteCommand($cmd, $redirecterr)
{
   global $config;
   
   if ($redirecterr)
      $cmd .= " 2>&1";
      
   // On Windows machines, the whole line needs quotes round it so that it's
   // passed to cmd.exe correctly

   if ($config->serverIsWindows)
      $cmd = "\"$cmd\"";
   
   return $cmd;
}

function runCommand($cmd, $mayReturnNothing = false)
{
   global $lang;
   
   $output = array ();
   $err = false;

   $c = quoteCommand($cmd, false);
      
   // Try to run the command normally
   if ($handle = popen($c, "r"))
   {
      $firstline = true;
		while (!feof($handle))
		{
		   $line = fgets($handle);
		   if ($firstline && $line == "" && !$mayReturnNothing)
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

   $c = quoteCommand($cmd, true);

   $output = transChars(shell_exec($c));
   if (!empty($output)) print "<p>".nl2br($output);
   exit;
}

// quote
//
// Quote a string to send to the command line

function quote($str)
{
   global $config;

   if ($config->serverIsWindows)
      return "\"$str\"";
   else
      return escapeshellarg($str);      
}

?>