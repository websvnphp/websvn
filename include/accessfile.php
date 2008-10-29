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
// accessfile.php
//
// Read a .ini style file

class IniFile {
  var $sections;

  // {{{ __construct

  function IniFile() {
    $this->sections = array();
  }

  // }}}

  // {{{ readIniFile

  function readIniFile($name) {
    $contents = file($name);
    $cursection = '';
    $first = true;

    foreach ($contents as $str) {
      $str = trim($str);
      if (empty($str)) {
        continue;
      }

      if ($str{0} == '#' or $str{0} == "'") {
        continue;
      }

      if ($str{0} == '[') {
        $cursection = strtolower(substr($str, 1, strlen($str) - 2));
        if (!($str{strlen($str) - 2} == '/' or $str == '[groups]')) {
          $cursection .= '/';
        }
        $first = true;
      } else if (!empty($cursection)) {
        if ($first === true) {
          $this->sections[$cursection] = array();
        }
        list($key, $val) = split('=', $str);
        $this->sections[$cursection][strtolower(trim($key))] = strtolower(trim($val));
        $first = false;
      }
    }
  }

  // }}}

  // {{{ getSections

  function &getSections() {
    return $this->sections;
  }

  // }}}

  // {{{ getValues

  function getValues($section) {
    return @$this->sections[strtolower($section)];
  }

  // }}}

  // {{{ getValue

  function getValue($section, $key) {
    return @$this->sections[strtolower($section)][strtolower($key)];
  }

  // }}}
}
