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
      $vars["curdirlinks"] .= "[<a href=\"${sofarurl}rev=$rev&amp;sc=$showchanged\">".$subs[$n]."/]</a> ";
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
      
	return ($ret);
}

function getFullURL($loc)
{
   $protocol = 'http';
   
   if (isset($_SERVER["HTTPS"]) && (strtolower($_SERVER["HTTPS"]) != "off"))
   {
   	$protocol = "https";
   }
   
   $port = ":".$_SERVER["SERVER_PORT"];
   if ((":80" == $port && "http" == $protocol) ||
       (":443" == $port && "https" == $protocol)) 
   {
      $port = "";
   }
   
   if (isset($_SERVER["HTTP_HOST"]))
   {
   	$host = $_SERVER["HTTP_HOST"];
   }
   else if (isset($_SERVER["SERVER_NAME"]))
   {
   	$host = $_SERVER["SERVER_NAME"].$port;
   }
   else if (isset($_SERVER["SERVER_ADDR"]))
   {
   	$host = $_SERVER["SERVER_ADDR"].$port;
   }
   else
   {
      print "Unable to redirect";
      exit;
   }
   
   $url	= $protocol . "://" . $host . $loc;

   return $url;
}

// hardspace
//
// Replace the spaces at the front of a line with hard spaces

# XXX: this is an unnecessary function; you can prevent whitespace from being
#      trimmed via CSS (use the "white-space: pre;" properties). ~J
# in the meantime, here's an improved function

function hardspace($s)
{
   global $rep;

   // Expand tabs to configured shiftwidth
   $sw = $rep->getExpandTabsBy();

   // Replace spaces and tabs with no-break spaces (which is unnecessary as I
   // mentioned above ~J)
   // To do this, we walk through and copy the string, replacing whitespace as necessary
   $str = '';
   for ($i = 0; $i < strlen($s); $i++)
   {
      # it might be better to copy chunks of the non-whitespace string at a time
      # by skipping $i to the next whitespace position, but something tells me
      # that won't help much ~J
      switch ($s{$i})
      {
         case ' ':
            $str .= '&nbsp;';
            break;

         case "\t":
            $str .= str_repeat('&nbsp;', ($sw - (($i+1) % $sw)));
            break;

         default:
            $str .= $s{$i};
            break;
      }
   }

   return $str;
}


// datetimeFormatDuration
//
// Formats a duration of seconds for display.
//
// $seconds the number of seconds until something
// $nbsp true if spaces should be replaced by nbsp
// $skipSeconds true if seconds should be omitted
//
// return the formatted duration (e.g. @c "8h  6m  1s")

function datetimeFormatDuration($seconds, $nbsp = false, $skipSeconds = false)
{
   global $lang;
   
	if ($seconds < 0)
	{
		$seconds = -$seconds;
		$neg = true;
	}
	else
		$neg = false;

	$qty = array();

	$qty[] = (int)($seconds / (60 * 60 * 24));
	$seconds %= 60 * 60 * 24;

	$qty[] = (int)($seconds / (60 * 60));
	$seconds %= 60 * 60;

	$qty[] = (int)($seconds / 60);
	$qty[] = (int)($seconds % 60);

	$text = "";
	$names = $lang["DAYLETTER"].$lang["HOURLETTER"].$lang["MINUTELETTER"];
	if (!$skipSeconds) $names .= $lang["SECONDLETTER"];

	$any = false;
	for ($i = 0; $i < strlen($names); $i++)
		// If a "higher valued" time slot had a value or this time slot
		// has a value or this is the very last entry (i.e. all values
		// are 0 and we still want to print seconds)
		if ($any || $qty[$i] || $i == sizeof($qty) - 1)
		{
			$any = true;

			if ($i)
				$text .= sprintf("%2d", $qty[$i]);
			else
				$text .= $qty[$i];

			$text .= "{$names{$i}} ";
		}

	$text = trim($text);
	if ($neg)
		$text = "-$text";

	return $nbsp ? str_replace(" ", "&nbsp;", $text) : $text;
}

// getParameterisedSelfUrl
//
// Get the relative URL (PHP_SELF) with GET and POST data

function getParameterisedSelfUrl($params = true)
{
   global $config;
   $sep = "?";

   if ($config->multiViews)
   {
      $url = preg_replace("/\.php/", "", $_SERVER['PHP_SELF'], 1);
   }
   else
   {
      $url = basename($_SERVER['PHP_SELF']);

      // Sometimes the .php isn't on the end.  Damn strange...
      if (strchr($url, '.') === false)
         $url .= ".php";   
   }

   if ($params)
   {
     $arr = $_GET + $_POST;
     while (list($k,$v) = each($arr))
     {
        if (is_array($v))
        {
           foreach ($v As $key => $value)
           {
              $url .= "$sep$k"."[".$key."]"."=$value";
              $sep = "&";
           }
        }
        else
         $url .= "$sep$k=$v";

        $sep = "&";
     }
   }

   $url .= $sep;

   return $url;
}
?>
