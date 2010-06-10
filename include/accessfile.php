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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
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
		// does not use parse_ini_file function since php 5.3 does not support comment lines starting with #
		$contents = file($name);
		$cursection = '';
		$curkey = '';

		foreach ($contents as $line) {
			$line = rtrim($line);
			$str = ltrim($line);
			if (empty($str)) {
				continue;
			}

			// @todo remove ' in the next major release to be in line with the svn book
			if ($str{0} == '#' || $str{0} == "'") {
				continue;
			}

			if ($str != $line && !empty($cursection) && !empty($curkey)) {
				// line starts with whitespace
				$this->sections[$cursection][$curkey] .= strtolower($str);
			} else if ($str{0} == '[' && $str{strlen($str) - 1} == ']') {
				$cursection = strtolower(substr($str, 1, strlen($str) - 2));
			} else if (!empty($cursection)) {
				if (!isset($this->sections[$cursection])) {
					$this->sections[$cursection] = array();
				}
				list($key, $val) = explode('=', $str, 2);
				$key = strtolower(trim($key));
				$curkey = $key;
				if ($cursection == 'groups' && isset($this->sections[$cursection][$key])) {
					$this->sections[$cursection][$key] .= ',' . strtolower(trim($val));
				} else {
					$this->sections[$cursection][$key] = strtolower(trim($val));
				}
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
