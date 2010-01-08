<?php

// help diff_inc.php to make sensible changes from added and deleted diff lines

// Interface for diffing function
class LineDiffInterface {
	// similarity 1 means that strings are very close to each other
	// 0 means totally different
	function lineSimilarity($text1, $text2) {
		assert(false);
	}

	// return array($left, $right) annotated with <ins> and <del> 
	// will be implemented in next patch
	//function lineDiff($text1, $text2);
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

		$l1 = explode(' ', $str1);
		$l2 = explode(' ', $str2);

		$n = count($str1);
		$m = count($str2);

		$d = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
		$ret = 0;


		for ($i = 1; $i < $n + 1; $i++) {
			$d[$i][0] = $i;
		}
		for ($j = 1; $j < $m + 1; $j++) {
			$d[0][$j] = $j;
		}

		for ($i = 1; $i < $n + 1; $i++) {
			for ($j = 1; $j < $m + 1; $j++) {
				if ($l1[$i - 1] == $l2[$j - 1]) {
					$c = 0;
				} else {
					$c = strlen($l1[$i - 1]) + strlen($l2[$i - 1]);
				}
				$d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $c);
			}
		}
		return $d[$i][$j];
	}
	// }}}

	// {{{ lineSimilarity
	function lineSimilarity($text1, $text2) {
		$distance = $this->levenshtein2($text1, $text2);
		$similarity =  1.0 - $distance / (strlen($text1) + strlen($text2) + 4);
		if ($similarity < 0) $similarity = 0;
		return $similarity;
	}
	// }}}
}

// Class computing sensibly added/deleted block of lines.
class SensibleLineChanges {
	var $_added = array();
	var $_deleted = array();
	var $_lineDiff = null;

	function SensibleLineChanges(LineDiffInterface $lineDiff) {
		$this->_lineDiff = $lineDiff;
	}

	function addDeletedLine($text, $highlighted_text, $lineno) {
		$this->_deleted[] = array($text, $highlighted_text, $lineno);
	}

	function addAddedLine($text, $highlighted_text, $lineno) {
		$this->_added[] = array($text, $highlighted_text, $lineno);
	}


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
				$best = -1.0;
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
					$i--; $j--;
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
	// takes diff.php's $listing and $index and
	// add computed changes to the listing
	function addChangesToListing($listingHelper) {
		$matching = $this->_computeBestMatching();

		foreach ($matching as $change) {
			if ($change[1] == null) { // deleted
				// preserve original highlighted text
				$listingHelper->addDeletedLine($change[0][1], $change[0][2]);
			} else if ($change[0] == null) { // added
				// preserve original highlighted text
				$listingHelper->addAddedLine($change[1][1], $change[1][2]);

			} else { // this is fully changed line
				// this will be changed in next patch to display inline diff
				$listingHelper->addChangedLine($change[0][1], $change[0][2],
						$change[1][1], $change[1][2]);
			}
		}
		$this->clear();
	}
	// }}}

	function clear(){
		$this->_added = array();
		$this->_deleted = array();
	}
}
