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
// utils.inc
//
// General utility commands

// createDirLinks
//
// Create a list of links to the current path that'll be available from the template

function createDirLinks($rep, $path, $rev, $showchanged)
{
   global $vars, $config;
   
   $subs = explode("/", $path);
   $sofar = "";
   $count = count($subs);
   $vars["curdirlinks"] = "";
   
   // The number of links depends on the last item.  It's empty if
   // we're looing at a directory, and full if it's a file
   if (empty($subs[$count - 1]))
   {
      $limit = $count - 2;
      $dir = true;
   }
   else
   {
      $limit = $count - 1;
      $dir = false;
   }
      
   for ($n = 0; $n < $limit; $n++)
   {
      $sofar .= $subs[$n]."/";
      $sofarurl = $config->getURL($rep, $sofar, "dir");
      $vars["curdirlinks"] .= "[<a href=\"${sofarurl}rev=$rev&sc=$showchanged\">".$subs[$n]."/]</a> ";
   }
   
   if ($dir)
   {
      $vars["curdirlinks"] .=  "[<b>".$subs[$n]."</b>/]";
   }
   else
   {
      $vars["curdirlinks"] .=  "[<b>".$subs[$n]."</b>]";
   }
}

// Create links out of http:// and mailto: tags

function create_anchors($text)
{
   $ret = $text;

   // Match correctly formed URLs that aren't already links
	$ret = preg_replace("#\b(?<!href=\")([a-z]+?)://(\S*)([\w/]+)#i",
	                    "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>",
	                    $ret);
	                    
	// Now match anything beginning with www, as long as it's not //www since they were matched above                    
	$ret = preg_replace("#\b(?<!//)www\.(\S*)([\w/]+)#i",
	                    "<a href=\"http://www.\\1\\2\" target=\"_blank\">www.\\1\\2</a>",
	                    $ret);

	// Match email addresses
	$ret = preg_replace("#\b([\w\-_.]+)@([\w\-.]+)\b#i",
	                    "<a href=\"mailto:\\1@\\2\">\\1@\\2</a>",
	                    $ret);
   
   // Replace any usernames
	$ret = preg_replace("#\[:nom:([^\]]*)\]#e",
	                    "username(0, trim(\"\\1\"))",
	                    $ret);
   
	return ($ret);
}

?>