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
// bugtraq.php
//
// Functions for accessing the bugtraq properties and replacing issue IDs
// with URLs.
//
// For more information about bugtraq, see
// http://svn.collab.net/repos/tortoisesvn/trunk/doc/issuetrackers.txt

class Bugtraq {
	// {{{ Properties

	var $msgstring;
	var $urlstring;
	var $logregex;
	var $append;

	var $firstPart;
	var $firstPartLen;
	var $lastPart;
	var $lastPartLen;

	var $propsfound = false;

	// }}}

	// {{{ __construct($rep, $svnrep, $path)

	function Bugtraq($rep, $svnrep, $path) {
		global $config;

		if ($rep->isBugtraqEnabled()) {
			$pos = strrpos($path, '/');
			$parent = substr($path, 0, $pos + 1);
			$this->append = true;

			$enoughdata = false;
			while (!$enoughdata && (strpos($parent, '/') !== false)) {
				$properties = $svnrep->getProperties($parent);
				if (empty($this->msgstring) && in_array('bugtraq:message', $properties)) $this->msgstring = $svnrep->getProperty($parent, 'bugtraq:message');
				if (empty($this->logregex) && in_array('bugtraq:logregex', $properties)) $this->logregex = $svnrep->getProperty($parent, 'bugtraq:logregex');
				if (empty($this->urlstring) && in_array('bugtraq:url', $properties)) $this->urlstring = $svnrep->getProperty($parent, 'bugtraq:url');
				if (in_array('bugtraq:append', $properties) && $svnrep->getProperty($parent, 'bugtraq:append') == 'false') $this->append = false;

				$parent = substr($parent, 0, -1); // Remove the trailing slash
				$pos = strrpos($parent, '/'); // Find the last trailing slash
				$parent = substr($parent, 0, $pos + 1); // Find the previous parent directory
				$enoughdata = ((!empty($this->msgstring) || !empty($this->logregex)) && !empty($this->urlstring));
			}

			$this->msgstring = trim(@$this->msgstring);
			$this->urlstring = trim(@$this->urlstring);

			if ($enoughdata && !empty($this->msgstring)) {
				$this->initPartInfo();
			}

			if ($enoughdata) {
				$this->propsfound = true;
			}
		}
	}

	// }}}

	// {{{ initPartInfo()

	function initPartInfo() {
		if (($bugidpos = strpos($this->msgstring, '%BUGID%')) !== false && strpos($this->urlstring, '%BUGID%') !== false) {
			// Get the textual parts of the message string for comparison purposes
			$this->firstPart = substr($this->msgstring, 0, $bugidpos);
			$this->firstPartLen = strlen($this->firstPart);
			$this->lastPart = substr($this->msgstring, $bugidpos + 7);
			$this->lastPartLen = strlen($this->lastPart);
		}
	}

	// }}}

	// {{{ replaceIDs($message)

	function replaceIDs($message) {
		if (!$this->propsfound) return $message;

		// First we search for the message string
		$logmsg	= '';
		$message = rtrim($message);

		if ($this->append) {
			// Just compare the last line
			if (($offset = strrpos($message, "\n")) !== false) {
				$logmsg = substr($message, 0, $offset + 1);
				$bugLine = substr($message, $offset + 1);
			} else {
				$bugLine = $message;
			}
		} else {
			if (($offset = strpos($message, "\n")) !== false) {
				$bugLine = substr($message, 0, $offset);
				$logmsg = substr($message, $offset);
			} else {
				$bugLine = $message;
			}
		}

		// Make sure that our line really is an issue tracker message
		if (isset($this->firstPart) && isset($this->lastPart) && ((strncmp($bugLine, $this->firstPart, $this->firstPartLen) == 0)) && strcmp(substr($bugLine, -$this->lastPartLen, $this->lastPartLen), $this->lastPart) == 0) {
			// Get the issues list
			if ($this->lastPartLen > 0) {
				$issues = substr($bugLine, $this->firstPartLen, -$this->lastPartLen);
			} else {
				$issues = substr($bugLine, $this->firstPartLen);
			}

			// Add each reference to the first part of the line
			$line = $this->firstPart;
			while ($pos = strpos($issues, ',')) {
				$issue	= trim(substr($issues, 0, $pos));
				$issues = substr($issues, $pos + 1);

				$line .= '<a href="'.str_replace('%BUGID%', $issue, $this->urlstring).'">'.$issue.'</a>, ';
			}
			$line .= '<a href="'.str_replace('%BUGID%', trim($issues), $this->urlstring).'">'.trim($issues).'</a>'.$this->lastPart;

			if ($this->append) {
				$message = $logmsg.$line;
			} else {
				$message = $line.$logmsg;
			}
		}

		// Now replace all other instances of bug IDs that match the regex
		if ($this->logregex) {
			$message = rtrim($message);
			$line = '';
			$allissues = '';

			$lines = explode("\n", $this->logregex);
			$regex_all = '~'.$lines[0].'~';
			$regex_single = @$lines[1];

			if (empty($regex_single)) {
				// If the property only contains one line, then the pattern is only designed
				// to find one issue number at a time.	e.g. [Ii]ssue #?(\d+).	In this case
				// we need to replace the matched issue ID with the link.

				if ($numMatches = preg_match_all($regex_all, $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
					$addedOffset = 0;
					for ($match = 0; $match < $numMatches; $match++) {
						$issue = $matches[$match][1][0];
						$issueOffset = $matches[$match][1][1];

						$issueLink = '<a href="'.str_replace('%BUGID%', $issue, $this->urlstring).'">'.$issue.'</a>';
						$message = substr_replace($message, $issueLink, $issueOffset + $addedOffset, strlen($issue));
						$addedOffset += strlen($issueLink) - strlen($issue);
					}
				}
			} else {
				// It the property contains two lines, then the first is a pattern for extracting
				// multiple issue numbers, and the second is a pattern extracting each issue
				// number from the multiple match.	e.g. [Ii]ssue #?(\d+)(,? ?#?(\d+))+ and (\d+)

				while (preg_match($regex_all, $message, $matches, PREG_OFFSET_CAPTURE)) {
					$completeMatch = $matches[0][0];
					$completeMatchOffset = $matches[0][1];

					$replacement = $completeMatch;

					if ($numMatches = preg_match_all('~'.$regex_single.'~', $replacement, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
						$addedOffset = 0;
						for ($match = 0; $match < $numMatches; $match++) {
							$issue = $matches[$match][1][0];
							$issueOffset = $matches[$match][1][1];

							$issueLink = '<a href="'.str_replace('%BUGID%', $issue, $this->urlstring).'">'.$issue.'</a>';
							$replacement = substr_replace($replacement, $issueLink, $issueOffset + $addedOffset, strlen($issue));
							$addedOffset += strlen($issueLink) - strlen($issue);
						}
					}

					$message = substr_replace($message, $replacement, $completeMatchOffset, strlen($completeMatch));
				}
			}
		}

		return $message;
	}

	// }}}

}

// The BugtraqTestable class is a derived class that is used to test the matching
// abilities of the Bugtraq class.	In particular, it allows for the initialisation of the
// class without the need for a repository.

class BugtraqTestable extends Bugtraq {
	// {{{ __construct()

	function BugtraqTestable() {
		// This constructor serves to assure that the parent constructor is not
		// called.
	}

	// }}}

	// {{{ setUpVars($message, $url, $regex, $append)

	function setUpVars($message, $url, $regex, $append) {
		$this->msgstring = $message;
		$this->urlstring = $url;
		$this->logregex = $regex;
		$this->append = $append;
		$this->propsfound = true;

		$this->initPartInfo();
	}

	// }}}

	// {{{ setMessage($message)

	function setMessage($message) {
		$this->msgstring = $message;
	}

	// }}}

	// {{{ setUrl($url)

	function setUrl($url) {
		$this->urlstring = $url;
	}

	// }}}

	// {{{ setRegex($regex)

	function setRegEx($regex) {
		$this->logregex = $regex;
	}

	// }}}

	// {{{ setAppend($append)

	function setAppend($append) {
		$this->append = $append;
	}

	// }}}

	// {{{ printVars()

	function printVars() {
		echo 'msgstring = '.$this->msgstring."\n";
		echo 'urlstring = '.$this->urlstring."\n";
		echo 'logregex = '.$this->logregex."\n";
		echo 'append = '.$this->append."\n";

		echo 'firstPart = '.$this->firstPart."\n";
		echo 'firstPartLen = '.$this->firstPartLen."\n";
		echo 'lastPart = '.$this->lastPart."\n";
		echo 'lastPartLen = '.$this->lastPartLen."\n";
	}

	// }}}
}

// {{{ test_bugtraq()

function test_bugtraq() {
	$tester = new BugtraqTestable;

	$tester->setUpVars('BugID: %BUGID%',
		'http://bugtracker/?id=%BUGID%',
		'[Ii]ssue #?(\d+)',
		true
	);

	//$tester->printVars();

	$res = $tester->replaceIDs('BugID: 789'."\n".
		'This is a test message that refers to issue #123 and'."\n".
		'issue #456.'."\n".
		'BugID: 789'
	);

	echo nl2br($res).'<p>';

	$res = $tester->replaceIDs('BugID: 789, 101112'."\n".
		'This is a test message that refers to issue #123 and'."\n".
		'issue #456.'."\n".
		'BugID: 789, 101112'
	);

	echo nl2br($res).'<p>';

	$tester->setAppend(false);

	$res = $tester->replaceIDs('BugID: 789'."\n".
		'This is a test message that refers to issue #123 and'."\n".
		'issue #456.'."\n".
		'BugID: 789'
	);

	echo nl2br($res).'<p>';

	$res = $tester->replaceIDs('BugID: 789, 101112'."\n".
		'This is a test message that refers to issue #123 and'."\n".
		'issue #456.'."\n".
		'BugID: 789, 101112'
	);

	echo nl2br($res).'<p>';

	$tester->setUpVars('BugID: %BUGID%',
		'http://bugtracker/?id=%BUGID%',
		'[Ii]ssues?:?(\s*(,|and)?\s*#\d+)+\n(\d+)',
		true
	);

	$res = $tester->replaceIDs('BugID: 789, 101112'."\n".
		'This is a test message that refers to issue #123 and'."\n".
		'issues #456, #654 and #321.'."\n".
		'BugID: 789, 101112'
	);

	echo nl2br($res).'<p>';

	$tester->setUpVars('Test: %BUGID%',
		'http://bugtracker/?id=%BUGID%',
		'\s*[Cc]ases*\s*[IDs]*\s*[#: ]+((\d+[ ,:;#]*)+)\n(\d+)',
		true
	);

	$res = $tester->replaceIDs('Cosmetic change'."\n".
		'CaseIDs: 48'
	);

	echo nl2br($res).'<p>';
}

// }}}
