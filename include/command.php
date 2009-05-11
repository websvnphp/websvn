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
// command.php
//
// External command handling

// {{{ replaceEntities
//
// Replace character codes with HTML entities for display purposes.
// This routine assumes that the character encoding of the string is
// that of the local system (i.e., it's a string returned from a command
// line command).

function replaceEntities($str, $rep) {
  global $config;

  // Ideally, we'd do this:
  //
  // $str = htmlentities($str, ENT_COMPAT, $config->inputEnc);
  //
  // However, htmlentities is very limited in it's ability to process
  // character encodings.  We have to rely on something more powerful.

  if (version_compare(phpversion(), "4.1.0", "<")) {
    // In this case, we can't do any better than assume that the
    // input encoding is ISO-8859-1.

    $str = htmlentities($str, ENT_COMPAT);
  } else {
    $str = toOutputEncoding($str, $rep->getContentEncoding());

    // $str is now encoded as UTF-8.
    $str = htmlentities($str, ENT_COMPAT, $config->outputEnc);
  }

  return $str;
}

// }}}

// {{{ toOutputEncoding

function toOutputEncoding($str, $inputEncoding = "") {
  global $config;

  if (empty($inputEncoding)) {
    $inputEncoding = $config->inputEnc;
  }

  // Try to convert the messages based on the locale information
  if ($config->inputEnc && $config->outputEnc) {
    if (function_exists("iconv")) {
      $output = @iconv($inputEncoding, $config->outputEnc, $str);
      if (!empty($output)) {
        $str = $output;
      }
    }
  }

  return $str;
}

// }}}

// {{{ quoteCommand

function quoteCommand($cmd) {
  global $config;

  // On Windows machines, the whole line needs quotes round it so that it's
  // passed to cmd.exe correctly

  if ($config->serverIsWindows) {
    $cmd = "\"$cmd\"";
  }

  return $cmd;
}

// }}}

// {{{ execCommand

function execCommand($cmd, &$retcode) {
  global $config;

  // On Windows machines, the whole line needs quotes round it so that it's
  // passed to cmd.exe correctly
  // Since php 5.3.0 the quoting seems to be done internally

  if ($config->serverIsWindows && version_compare(PHP_VERSION, '5.3.0alpha') === -1) {
    $cmd = "\"$cmd\"";
  }

  return @exec($cmd, $tmp, $retcode);
}

// }}}

// {{{ popenCommand

function popenCommand($cmd, $mode) {
  global $config;

  // On Windows machines, the whole line needs quotes round it so that it's
  // passed to cmd.exe correctly
  // Since php 5.3.0 the quoting seems to be done internally

  if ($config->serverIsWindows && version_compare(PHP_VERSION, '5.3.0alpha') === -1) {
    $cmd = "\"$cmd\"";
  }

  return popen($cmd, $mode);
}

// }}}

// {{{ passthruCommand

function passthruCommand($cmd) {
  global $config;

  // On Windows machines, the whole line needs quotes round it so that it's
  // passed to cmd.exe correctly
  // Since php 5.3.0 the quoting seems to be done internally

  if ($config->serverIsWindows && version_compare(PHP_VERSION, '5.3.0alpha') === -1) {
    $cmd = "\"$cmd\"";
  }

  return passthru($cmd);
}

// }}}

// {{{ runCommand

function runCommand($cmd, $mayReturnNothing = false) {
  global $lang;

  $output = array();
  $err = false;

  $c = quoteCommand($cmd);

  $descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));

  $resource = proc_open($c, $descriptorspec, $pipes);
  $error = "";

  if (!is_resource($resource)) {
    echo"<p>".$lang['BADCMD'].": <code>".$cmd."</code></p>";
    exit;
  }

  $handle = $pipes[1];
  $firstline = true;
  while (!feof($handle)) {
    $line = fgets($handle);
    if ($firstline && empty($line) && !$mayReturnNothing) {
      $err = true;
    }

    $firstline = false;
    $output[] = toOutputEncoding(rtrim($line));
  }

  while (!feof($pipes[2])) {
    $error .= fgets($pipes[2]);
  }

  $error = toOutputEncoding(trim($error));

  fclose($pipes[0]);
  fclose($pipes[1]);
  fclose($pipes[2]);

  proc_close($resource);

  if (!$err) {
    return $output;
  } else {
    echo"<p>".$lang['BADCMD'].": <code>".$cmd."</code></p><p>".nl2br($error)."</p>";
  }
}

// }}}

// {{{ quote
//
// Quote a string to send to the command line

function quote($str) {
  global $config;

  if ($config->serverIsWindows) {
    return "\"$str\"";
  } else {
    return escapeshellarg($str);
  }
}

// }}}
