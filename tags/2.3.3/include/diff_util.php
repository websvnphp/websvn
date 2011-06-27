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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
//
// --
//
// diff_util.php
//
// help diff_inc.php to make sensible changes from added and deleted diff lines
// These lines are automatically paired and also inline diff is performed to show
// insertions/deletions on one line

@include_once 'Text/Diff.php';

// Interface for diffing function
class LineDiffInterface {
	// similarity 1 means that strings are very close to each other
	// 0 means totally different
	function lineSimilarity($text1, $text2) {
		assert(false);
	}

	// return array($left, $right) annotated with <ins> and <del>
	function inlineDiff($text1, $highlighted1, $text2, $highlighted2, $highlighted) {
		assert(false);
	}
}

// Default line diffing function
class LineDiff extends LineDiffInterface {

	function LineDiff($ignoreWhitespace) {
		$this->ignoreWhitespace = $ignoreWhitespace;
	}

	// {{{ levenshtein2
	// levenshtein edit distance, on small strings use php function
	// on large strings approximate distance using words
	// computed by dynamic programming
	function levenshtein2($str1, $str2) {
		if (strlen($str1) < 255 && strlen($str2) < 255) {
			return levenshtein($str1, $str2);
		}
		$n = count($str1);
		$m = count($str2);
		$d = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
		for ($i = 1; $i < $n + 1; $i++) {
			$d[$i][0] = $i;
		}
		for ($j = 1; $j < $m + 1; $j++) {
			$d[0][$j] = $j;
		}
		$l1 = explode(' ', $str1);
		$l2 = explode(' ', $str2);
		for ($i = 1; $i < $n + 1; $i++) {
			for ($j = 1; $j < $m + 1; $j++) {
				$c = ($l1[$i - 1] == $l2[$j - 1]) ? 0 : strlen($l1[$i - 1]) + strlen($l2[$i - 1]);
				$d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $c);
			}
		}
		return $d[$n][$m];
	}
	// }}}

	// {{{ lineSimilarity
	function lineSimilarity($text1, $text2) {
		$distance = $this->levenshtein2($text1, $text2);
		return max(0.0, 1.0 - $distance / (strlen($text1) + strlen($text2) + 4));
	}
	// }}}

	// {{{  tokenize whole line into words
	// note that separators are returned as tokens of length 1
	// and if $ignoreWhitespace is true, consecutive whitespaces are returned as one token
	function tokenize($string, $highlighted, $ignoreWhitespace) {
		$html = array('<' => '>', '&' => ';');
		$whitespaces = array("\t","\n","\r",' ');
		$separators = array('.','-','+','*','/','<','>','?','(',')','&','/','{','}','[',']',':',';');
		$data = array();
		$segment = '';
		$segmentIsWhitespace = true;
		$count = strlen($string);
		for ($i = 0; $i < $count; $i++) {
			$c = $string[$i];
			if ($highlighted && array_key_exists($c, $html)) {
				if ($segment != '') {
					$data[] = $segment;
				}
				// consider html tags and entities as a single token
				$endchar = $html[$c];
				$segment = $c;
				do {
					$i++;
					$c = $string[$i];
					$segment .= $c;
				} while ($c != $endchar && $i < $count - 1);
				$data[] = $segment;
				$segment = '';
				$segmentIsWhitespace = false;
			} else if (in_array($c, $separators) || (!$ignoreWhitespace && in_array($c, $whitespaces))) {
				// if it is separator or whitespace and we do not consider consecutive whitespaces
				if ($segment != '') {
					$data[] = $segment;
				}
				$data[] = $c;
				$segment = '';
				$segmentIsWhitespace = true;
			} else if (in_array($c, $whitespaces)) {
				// if it is whitespace and we consider consecutive whitespaces as one token
				if (!$segmentIsWhitespace) {
					$data[] = $segment;
					$segment = '';
					$segmentIsWhitespace = true;
				}
				$segment .= $c;
			} else {
				// no separator or whitespace
				if ($segmentIsWhitespace && $segment != '') {
					$data[] = $segment;
					$segment = '';
				}
				$segment .= $c;
				$segmentIsWhitespace = false;
			}
		}
		if ($segment != '') {
			$data[] = $segment;
		}
		return $data;
	}
	// }}}

	// {{{ lineDiff
	function inlineDiff($text1, $highlighted1, $text2, $highlighted2, $highlighted) {
		$whitespaces = array(' ', "\t", "\n", "\r");

		$do_diff = true;

		if ($text1 == '' || $text2 == '') {
			$do_diff = false;
		}

		if ($this->ignoreWhitespace && (str_replace($whitespaces, array(), $text1) == str_replace($whitespaces, array(), $text2))) {
			$do_diff = false;
		}

		// Exit gracefully if loading of Text_Diff failed
		if (!class_exists('Text_Diff') || !class_exists('Text_MappedDiff')) {
			$do_diff = false;
		}

		// Return highlighted lines without doing inline diff
		if (!$do_diff) {
			return array($highlighted1, $highlighted2);
		}

		$tokens1 = $this->tokenize($highlighted1, $highlighted, $this->ignoreWhitespace);
		$tokens2 = $this->tokenize($highlighted2, $highlighted, $this->ignoreWhitespace);

		if (!$this->ignoreWhitespace) {
			$diff = @new Text_Diff('native', array($tokens1, $tokens2));
		} else {
			// we need to create mapped parts for MappedDiff
			$mapped1 = array();
			foreach ($tokens1 as $token) {
				$mapped1[] = str_replace($whitespaces, array(), $token);
			}
			$mapped2 = array();
			foreach ($tokens2 as $token) {
				$mapped2[] = str_replace($whitespaces, array(), $token);
			}
			$diff = @new Text_MappedDiff($tokens1, $tokens2, $mapped1, $mapped2);
		}

		// now, get the diff and annotate text
		$edits = $diff->getDiff();

		$line1 = '';
		$line2 = '';
		foreach ($edits as $edit) {
			if (@is_a($edit, 'Text_Diff_Op_copy')) {
				$line1 .= implode('', $edit->orig);
				$line2 .= implode('', $edit->final);
			} else if (@is_a($edit, 'Text_Diff_Op_delete')) {
				$line1 .= '<del>'.implode('', $edit->orig).'</del>';
			} else if (@is_a($edit, 'Text_Diff_Op_add')) {
				$line2 .= '<ins>'.implode('', $edit->final).'</ins>';
			} else if (@is_a($edit, 'Text_Diff_Op_change')) {
				$line1 .= '<del>'.implode('', $edit->orig).'</del>';
				$line2 .= '<ins>'.implode('', $edit->final).'</ins>';
			} else {
				assert(false);
			}
		}
		return array($line1, $line2);
	}
	// }}}
}

// Class for computing sensibly added/deleted block of lines.
class SensibleLineChanges {
	var $_added = array();
	var $_deleted = array();
	var $_lineDiff = null;

	function SensibleLineChanges($lineDiff) {
		$this->_lineDiff = $lineDiff;
	}

	function addDeletedLine($text, $highlighted_text, $lineno) {
		$this->_deleted[] = array($text, $highlighted_text, $lineno);
	}

	function addAddedLine($text, $highlighted_text, $lineno) {
		$this->_added[] = array($text, $highlighted_text, $lineno);
	}

	// this function computes simple match - first min(deleted,added) lines are marked as changed
	// it is intended to be run instead of _computeBestMatching if the diff is too big
	function _computeFastMatching() {
		$result = array();
		$q = 0;
		while ($q < $n && $q < $m) {
			$result[] = array($this->_deleted[$q], $this->_added[$q]);
			$q++;
		}
		while ($q < $n) {
			$result[] = array($this->_deleted[$q], null);
			$q++;
		}
		while ($q < $m) {
			$result[] = array(null, $this->_added[$q]);
			$q++;
		}
		return $result;
	}

	// {{{ _computeBestMatching
	// dynamically compute best matching
	// note that this is O(n*m) * O(line similarity)
	function _computeBestMatching() {
		$n = count($this->_deleted);
		$m = count($this->_added);

		// if the computation will be slow, just run fast algorithm
		if ($n * $m > 10000) {
			return $this->_computeFastMatching();
		}

		// dyn[$i][$j] holds best sum of similarities we can obtain if we match
		// first $i deleted lines and first $j added lines
		$dyn = array_fill(0, $n + 1, array_fill(0, $m + 1, 0.0));
		// backlinks, so we can reconstruct best layout easily
		$back = array_fill(0, $n + 1, array_fill(0, $m + 1, -1));

		// if there is no similarity, prefer adding/deleting lines
		$value_del = 0.1;
		$value_add = 0.1;

		// initialize arrays
		for ($i = 1; $i <= $n; $i++) {
			$back[$i][0] = 0;
			$dyn[$i][0] = $value_del * $i;
		}
		for ($j = 1; $j <= $m; $j++) {
			$back[0][$j] = 1;
			$dyn[0][$j] = $value_add * $j;
		}

		// main dynamic programming
		for ($i = 1; $i <= $n; $i++) {
			for ($j = 1; $j <= $m; $j++) {
				$best = - 1.0;
				$b = -1;
				if ($dyn[$i - 1][$j] + $value_del >= $best) {
					$b = 0;
					$best = $dyn[$i - 1][$j] + $value_del;
				}
				if ($dyn[$i][$j - 1] + $value_add >= $best) {
					$b = 1;
					$best = $dyn[$i][$j - 1] + $value_add;
				}
				$sim = $this->_lineDiff->lineSimilarity($this->_deleted[$i - 1][0], $this->_added[$j - 1][0]);
				if ($dyn[$i - 1][$j - 1] + $sim >= $best) {
					$b = 2;
					$best = $dyn[$i - 1][$j - 1] + $sim;
				}
				$back[$i][$j] = $b;
				$dyn[$i][$j] = $best;
			}
		}

		// compute layout for best result
		$i = $n;
		$j = $m;
		$result = array();

		while ($i + $j >= 1) {
			switch($back[$i][$j]) {
				case 2: array_push($result, array($this->_deleted[$i - 1], $this->_added[$j - 1]));
					$i--;
					$j--;
					break;
				case 1: array_push($result, array(null, $this->_added[$j - 1]));
					$j--;
					break;
				case 0: array_push($result, array($this->_deleted[$i - 1], null));
					$i--;
					break;
				default:
					assert(false);
			}
		}
		return array_reverse($result);
	}
	// }}}

	// {{{ addChangesToListing
	// add computed changes to the listing
	function addChangesToListing(&$listingHelper, $highlighted) {
		$matching = $this->_computeBestMatching();
		foreach ($matching as $change) {
			if ($change[1] == null) {
				// deleted -- preserve original highlighted text
				$listingHelper->addDeletedLine($change[0][1], $change[0][2]);
			} else if ($change[0] == null) {
				// added   -- preserve original highlighted text
				$listingHelper->addAddedLine($change[1][1], $change[1][2]);
			} else {
				// this is fully changed line, make inline diff
				$diff = $this->_lineDiff->inlineDiff($change[0][0], $change[0][1], $change[1][0], $change[1][1], $highlighted);
				$listingHelper->addChangedLine($diff[0], $change[0][2], $diff[1], $change[1][2]);
			}
		}
		$this->clear();
	}
	// }}}

	function clear() {
		$this->_added = array();
		$this->_deleted = array();
	}
}

if (!function_exists('str_split')) {
	function str_split($string, $string_length = 1) {
		if ($string_length < 1) return false;
		$parts = array();
		do {
			$parts[] = substr($string, 0, $string_length);
			$string = substr($string, $string_length);
		} while ($string !== false);
		return $parts;
	}
}
