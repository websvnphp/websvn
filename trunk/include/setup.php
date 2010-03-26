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
// setup.php
//
// Global setup

// --- DON'T CHANGE THIS FILE ---
//
// User changes should be done in config.php

// Include the configuration class
require_once 'include/configclass.php';

// Create the config
$config = new WebSvnConfig();

if (DIRECTORY_SEPARATOR == '\\') {
	$config->setServerIsWindows();
}

// Set up locwebsvnhttp
// Note: we will use nothing in MultiViews mode so that the URLs use the root
//		 directory by default.
if (empty($locwebsvnhttp)) {
	$locwebsvnhttp = defined('WSVN_MULTIVIEWS') ? '' : '.';
}
if (empty($locwebsvnreal)) {
	$locwebsvnreal = '.';
}

$vars['locwebsvnhttp'] = $locwebsvnhttp;

// {{{ Content Types
// Set up the default content-type extension handling

$contentType = array(
	'.dwg'		 => 'application/acad', // AutoCAD Drawing files
	'.arj'		 => 'application/arj', //  
	'.ccad'		=> 'application/clariscad', // ClarisCAD files
	'.drw'		 => 'application/drafting', // MATRA Prelude drafting
	'.dxf'		 => 'application/dxf', // DXF (AutoCAD)
	'.xl'			=> 'application/excel', // Microsoft Excel
	'.unv'		 => 'application/i-deas', //SDRC I-DEAS files
	'.igs'		 => 'application/iges', // IGES graphics format
	'.iges'		=> 'application/iges', // IGES graphics format
	'.hqx'		 => 'application/mac-binhex40', // Macintosh BinHex format
	'.word'		=> 'application/msword', // Microsoft Word
	'.w6w'		 => 'application/msword', // Microsoft Word
	'.doc'		 => 'application/msword', // Microsoft Word
	'.wri'		 => 'application/mswrite', // Microsoft Write
	'.bin'		 => 'application/octet-stream', // Uninterpreted binary
	'.exe'		 => 'application/x-msdownload', // Windows EXE
	'.oda'		 => 'application/oda', //  
	'.pdf'		 => 'application/pdf', // PDF (Adobe Acrobat)
	'.ai'			=> 'application/postscript', // PostScript
	'.ps'			=> 'application/postscript', // PostScript
	'.eps'		 => 'application/postscript', // PostScript
	'.prt'		 => 'application/pro_eng', // PTC Pro/ENGINEER
	'.part'		=> 'application/pro_eng', // PTC Pro/ENGINEER
	'.rtf'		 => 'application/rtf', // Rich Text Format
	'.set'		 => 'application/set', // SET (French CAD standard)
	'.stl'		 => 'application/sla', // Stereolithography
	'.sol'		 => 'application/solids', // MATRA Prelude Solids
	'.stp'		 => 'application/STEP', // ISO-10303 STEP data files
	'.step'		=> 'application/STEP', // ISO-10303 STEP data files
	'.vda'		 => 'application/vda', // VDA-FS Surface data
	'.dir'		 => 'application/x-director', // Macromedia Director
	'.dcr'		 => 'application/x-director', // Macromedia Director
	'.dxr'		 => 'application/x-director', // Macromedia Director
	'.mif'		 => 'application/x-mif', // FrameMaker MIF Format
	'.csh'		 => 'application/x-csh', // C-shell script
	'.dvi'		 => 'application/x-dvi', // TeX DVI
	'.gz'			=> 'application/x-gzip', // GNU Zip
	'.gzip'		=> 'application/x-gzip', // GNU Zip
	'.hdf'		 => 'application/x-hdf', // ncSA HDF Data File
	'.latex'	 => 'application/x-latex', // LaTeX source
	'.nc'			=> 'application/x-netcdf', // Unidata netCDF
	'.cdf'		 => 'application/x-netcdf', // Unidata netCDF
	'.sit'		 => 'application/x-stuffit', // Stiffut Archive
	'.tcl'		 => 'application/x-tcl', // TCL script
	'.texinfo' => 'application/x-texinfo', // Texinfo (Emacs)
	'.texi'		=> 'application/x-texinfo', // Texinfo (Emacs)
	'.t'			 => 'application/x-troff', // Troff
	'.tr'			=> 'application/x-troff', // Troff
	'.roff'		=> 'application/x-troff', // Troff
	'.man'		 => 'application/x-troff-man', // Troff with MAN macros
	'.me'			=> 'application/x-troff-me', // Troff with ME macros
	'.ms'			=> 'application/x-troff-ms', // Troff with MS macros
	'.src'		 => 'application/x-wais-source', // WAIS source
	'.bcpio'	 => 'application/x-bcpio', // Old binary CPIO
	'.cpio'		=> 'application/x-cpio', // POSIX CPIO
	'.gtar'		=> 'application/x-gtar', // GNU tar
	'.shar'		=> 'application/x-shar', // Shell archive
	'.sv4cpio' => 'application/x-sv4cpio', // SVR4 CPIO
	'.sv4crc'	=> 'application/x-sv4crc', // SVR4 CPIO with CRC
	'.tar'		 => 'application/x-tar', // 4.3BSD tar format
	'.ustar'	 => 'application/x-ustar', // POSIX tar format
	'.hlp'		 => 'application/x-winhelp', // Windows Help
	'.zip'		 => 'application/zip', // ZIP archive

	'.au'	 => 'audio/basic', // Basic audio (usually m-law)
	'.snd'	=> 'audio/basic', // Basic audio (usually m-law)
	'.aif'	=> 'audio/x-aiff', // AIFF audio
	'.aiff' => 'audio/x-aiff', // AIFF audio
	'.aifc' => 'audio/x-aiff', // AIFF audio
	'.ra'	 => 'audio/x-pn-realaudio', // RealAudio
	'.ram'	=> 'audio/x-pn-realaudio', // RealAudio
	'.rpm'	=> 'audio/x-pn-realaudio-plugin', // RealAudio (plug-in)
	'.wav'	=> 'audio/x-wav', // Windows WAVE audio
	'.mp3'	=> 'audio/x-mp3', // MP3 files

	'.gif'	=> 'image/gif', // gif image
	'.ief'	=> 'image/ief', // Image Exchange Format
	'.jpg'	=> 'image/jpeg', // JPEG image
	'.jpe'	=> 'image/jpeg', // JPEG image
	'.jpeg' => 'image/jpeg', // JPEG image
	'.pict' => 'image/pict', // Macintosh PICT
	'.tiff' => 'image/tiff', // TIFF image
	'.tif'	=> 'image/tiff', // TIFF image
	'.ras'	=> 'image/x-cmu-raster', // CMU raster
	'.pnm'	=> 'image/x-portable-anymap', // PBM Anymap format
	'.pbm'	=> 'image/x-portable-bitmap', // PBM Bitmap format
	'.pgm'	=> 'image/x-portable-graymap', // PBM Graymap format
	'.ppm'	=> 'image/x-portable-pixmap', // PBM Pixmap format
	'.rgb'	=> 'image/x-rgb', // RGB Image
	'.xbm'	=> 'image/x-xbitmap', // X Bitmap
	'.xpm'	=> 'image/x-xpixmap', // X Pixmap
	'.xwd'	=> 'image/x-xwindowdump', // X Windows dump (xwd) format

	'.zip'	=> 'multipart/x-zip', // PKZIP Archive
	'.gzip' => 'multipart/x-gzip', // GNU ZIP Archive

	'.mpeg'	=> 'video/mpeg', // MPEG video
	'.mpg'	 => 'video/mpeg', // MPEG video
	'.mpe'	 => 'video/mpeg', // MPEG video
	'.mpeg'	=> 'video/mpeg', // MPEG video
	'.qt'		=> 'video/quicktime', // QuickTime Video
	'.mov'	 => 'video/quicktime', // QuickTime Video
	'.avi'	 => 'video/msvideo', // Microsoft Windows Video
	'.movie' => 'video/x-sgi-movie', // SGI Movieplayer format
	'.wrl'	 => 'x-world/x-vrml', // VRML Worlds

	'.ods'	=> 'application/vnd.oasis.opendocument.spreadsheet',					 // OpenDocument Spreadsheet
	'.ots'	=> 'application/vnd.oasis.opendocument.spreadsheet-template',	// OpenDocument Spreadsheet Template
	'.odp'	=> 'application/vnd.oasis.opendocument.presentation',					// OpenDocument Presentation
	'.otp'	=> 'application/vnd.oasis.opendocument.presentation-template', // OpenDocument Presentation Template
	'.odg'	=> 'application/vnd.oasis.opendocument.graphics',							// OpenDocument Drawing
	'.otg'	=> 'application/vnd.oasis.opendocument.graphics-template',		 // OpenDocument Drawing Template
	'.odc'	=> 'application/vnd.oasis.opendocument.chart',								 // OpenDocument Chart
	'.otc'	=> 'application/vnd.oasis.opendocument.chart-template',				// OpenDocument Chart Template
	'.odf'	=> 'application/vnd.oasis.opendocument.formula',							 // OpenDocument Formula
	'.otf'	=> 'application/vnd.oasis.opendocument.formula-template',			// OpenDocument Formula Template
	'.odi'	=> 'application/vnd.oasis.opendocument.image',								 // OpenDocument Image
	'.oti'	=> 'application/vnd.oasis.opendocument.image-template',				// OpenDocument Image Template
	'.odb'	=> 'application/vnd.oasis.opendocument.database',							// OpenDocument Database
	'.odt'	=> 'application/vnd.oasis.opendocument.text',									// OpenDocument Text
	'.ott'	=> 'application/vnd.oasis.opendocument.text-template',				 // OpenDocument Text Template
	'.odm'	=> 'application/vnd.oasis.opendocument.text-master',					 // OpenDocument Master Document
	'.oth'	=> 'application/vnd.oasis.opendocument.text-web',							// OpenDocument HTML Template
);

// }}}

// {{{ Enscript file extensions

// List of extensions recognised by enscript.

$extEnscript = array(
	'.ada'		 => 'ada',
	'.adb'		 => 'ada',
	'.ads'		 => 'ada',
	'.awk'		 => 'awk',
	'.c'			 => 'c',
	'.c++'		 => 'cpp',
	'.cc'			=> 'cpp',
	'.cpp'		 => 'cpp',
	'.csh'		 => 'csh',
	'.cxx'		 => 'cpp',
	'.diff'		=> 'diffu',
	'.dpr'		 => 'delphi',
	'.e'			 => 'eiffel',
	'.el'			=> 'elisp',
	'.eps'		 => 'postscript',
	'.f'			 => 'fortran',
	'.for'		 => 'fortran',
	'.gs'			=> 'haskell',
	'.h'			 => 'c',
	'.hpp'		 => 'cpp',
	'.hs'			=> 'haskell',
	'.htm'		 => 'html',
	'.html'		=> 'html',
	'.idl'		 => 'idl',
	'.java'		=> 'java',
	'.js'			=> 'javascript',
	'.lgs'		 => 'haskell',
	'.lhs'		 => 'haskell',
	'.m'			 => 'objc',
	'.m4'			=> 'm4',
	'.man'		 => 'nroff',
	'.nr'			=> 'nroff',
	'.p'			 => 'pascal',
	'.pas'		 => 'delphi',
	'.patch'	 => 'diffu',
	'.pkg'		 => 'sql',
	'.pl'			=> 'perl',
	'.pm'			=> 'perl',
	'.pp'			=> 'pascal',
	'.ps'			=> 'postscript',
	'.s'			 => 'asm',
	'.scheme'	=> 'scheme',
	'.scm'		 => 'scheme',
	'.scr'		 => 'synopsys',
	'.sh'			=> 'sh',
	'.shtml'	 => 'html',
	'.sql'		 => 'sql',
	'.st'			=> 'states',
	'.syn'		 => 'synopsys',
	'.synth'	 => 'synopsys',
	'.tcl'		 => 'tcl',
	'.tex'		 => 'tex',
	'.texi'		=> 'tex',
	'.texinfo' => 'tex',
	'.v'			 => 'verilog',
	'.vba'		 => 'vba',
	'.vh'			=> 'verilog',
	'.vhd'		 => 'vhdl',
	'.vhdl'		=> 'vhdl',
	'.py'			=> 'python',
);

// }}}

// {{{ GeSHi file extensions

// List of extensions recognised by GeSHi.

$extGeshi = array(
	'actionscript3' => array('as'),
	'ada' => array('ada', 'adb', 'ads'),
	'asm' => array('ash', 'asi', 'asm'),
	'asp' => array('asp'),
	'bash' => array('sh'),
	'bibtex' => array('bib'),
	'c' => array('c'),
	'cfm' => array('cfm', 'cfml'),
	'cobol' => array('cbl'),
	'cpp' => array('cc', 'cpp', 'cxx', 'c++', 'h', 'hpp'),
	'csharp' => array('cs'),
	'css' => array('css'),
	'd' => array('d'),
	'delphi' => array('dpk', 'dpr', 'pas'),
	'diff' => array('diff', 'patch'),
	'dos' => array('bat', 'cmd'),
	'eiffel' => array('e'),
	'erlang' => array('erl'),
	'email' => array('eml'),
	'fortran' => array('f', 'for'),
	'gettext' => array('po', 'pot'),
	'gml' => array('gml'),
	'gnuplot' => array('plt'),
	'groovy' => array('groovy'),
	'haskell' => array('gs', 'hs', 'lgs', 'lhs'),
	'html4strict' => array('html', 'htm'),
	'idl' => array('idl'),
	'ini' => array('desktop', 'ini'),
	'java5' => array('java'),
	'javascript' => array('js'),
	'latex' => array('tex'),
	'lisp' => array('lisp'),
	'lua' => array('lua'),
	'make' => array('make'),
	'matlab' => array('m'),
	'perl' => array('pl', 'pm'),
	'php' => array('php', 'php3', 'php4', 'php5', 'phps', 'phtml'),
	'povray' => array('pov'),
	'providex' => array('pvc', 'pvx'),
	'python' => array('py'),
	'reg' => array('reg'),
	'ruby' => array('rb'),
	'scala' => array('scala'),
	'scheme' => array('scm', 'scheme'),
	'scilab' => array('sci'),
	'smalltalk' => array('st'),
	'sql' => array('sql'),
	'tcl' => array('tcl'),
	'vb' => array('bas'),
	'vh' => array('v', 'verilog'),
	'vhdl' => array('vhd', 'vhdl'),
	'vim' => array('vim'),
	'whitespace' => array('ws'),
	'xml' => array('xml', 'xsl', 'xsd', 'xib', 'wsdl', 'svg', 'plist'),
	'z80' => array('z80'),
);

// }}}

// Loads English localized strings by default (must go before config.php)
require 'languages/english.php';

// Get the user's personalised config (requires the locwebsvnhttp stuff above)
if (file_exists('include/config.php')) {
	require_once 'include/config.php';
} else {
	die('File "include/config.php" does not exist, please create one. The example file "include/distconfig.php" may be copied and modified as needed.');
}

// Make sure that the input locale is set up correctly
setlocale(LC_ALL, '');

// assure that a default timezone is set
if (function_exists('date_default_timezone_get')) {
	$timezone = @date_default_timezone_get();
	date_default_timezone_set($timezone); 
}

// Initialize the version of SVN that is being used by WebSVN internally.
require_once 'include/svnlook.php';
$vars['svnversion'] = $config->getSubversionVersion();

// Initialize an array with all query parameters except language and template.
$queryParams = $_GET + $_POST;
unset($queryParams['language']);
unset($queryParams['template']);

// If the request specifies a language, store in a permanent/session cookie.
// Otherwise, check for cookies specifying a particular language.
$language = ''; // RFC 4646 language tag for representing the selected language.
if (!empty($_REQUEST['language'])) {
	$language = $_REQUEST['language'];
	setcookie('storedlang', $language, time() + (60 * 60 * 24 * 356 * 10), '/');
	setcookie('storedsesslang', $language);
} else if (isset($_COOKIE['storedlang'])) {
	$language = $_COOKIE['storedlang'];
} else if (isset($_COOKIE['storedsesslang'])) {
	$language = $_COOKIE['storedsesslang'];
}
// Load available languages (populates $languages array)
require 'languages/languages.php';
// Get the default language as defined by config.php
$defaultLanguage = $config->getDefaultLanguage();
if (!isset($languages[$defaultLanguage]))
	$defaultLanguage = 'en';
// Determine which language to actually use
$language = getUserLanguage($languages, $defaultLanguage, $language);
$vars['language_code'] = $language;
// For languages other than English, load translated strings over existing ones.
if ($language != 'en')
	require 'languages/'.$languages[$language][0].'.php';
// Generate the HTML form for selecting a different language
$vars['language_form'] = '<form action="?'.buildQuery($queryParams).'" method="get" id="langform">';
$vars['language_select'] = '<select name="language" onchange="javascript:this.form.submit();">';
foreach ($languages as $code => $names) {
	$sel = ($code == $language) ? '" selected="selected' : '';
	$vars['language_select'] .= '<option value="'.$code.$sel.'">'.$names[2].' - '.$names[1].'</option>';
}
$vars['language_select'] .= '</select>';
$vars['language_submit'] = '<noscript><input type="submit" value="'.$lang['GO'].'" /></noscript>';
$vars['language_endform'] = '</form>';

// Load repository if possible
if ($config->multiViews) {
	$rep = null; // MultiViews has custom code to load a repository
} else {
	// Load repository matching 'repname' parameter (if set) or the default.
	$repname = @$_REQUEST['repname'];
	if (isset($repname)) {
		$rep = $config->findRepository($repname);
	} else {
		$reps = $config->getRepositories();
		$rep = (isset($reps[0]) ? $reps[0] : null);
	}
	// Make sure that the user has set up a repository
	if ($rep == null) {
		$vars['error'] = $lang['SUPPLYREP'];
	} else if (is_string($rep)) {
		$vars['error'] = $rep;
		$rep = null;
	} else {
		$vars['repurl'] = $config->getURL($rep, '', 'dir');
		$vars['clientrooturl'] = $rep->clientRootURL;
		$vars['repname'] = escape($rep->getDisplayName());
		$vars['allowdownload'] = $rep->getAllowDownload();
	}
	// With MultiViews, wsvn creates the form once the current project is found.
	createProjectSelectionForm();
	createRevisionSelectionForm();
}

// If the request specifies a template, store in a permanent/session cookie.
// Otherwise, check for cookies specifying a particular template.
$template = '';
if (!empty($_REQUEST['template'])) {
	$template = $_REQUEST['template'];
	setcookie('storedtemplate', $template, time() + (60 * 60 * 24 * 365 * 10), '/');
	setcookie('storedsesstemplate', $template);
} else if (isset($_COOKIE['storedtemplate'])) {
	$template = $_COOKIE['storedtemplate'];
} else if (isset($_COOKIE['storedsesstemplate'])) {
	$template = $_COOKIE['storedsesstemplate'];
}

$templates = array();
// Skip creating template list when selected repository has specific template.
if ($rep == null || $rep->templatePath === false) {
	// Get all templates defined in config.php; use last path component as name.
	foreach ($config->templatePaths as $path) {
		$templates[$path] = basename($path);
	}
	$selectedTemplatePath = $config->getTemplatePath();
	if ($template != '' && in_array($template, $templates)) {
		$selectedTemplatePath = array_search($template, $templates);
		$config->userTemplate = $selectedTemplatePath;
	}
}

// Generate the HTML form for selecting a different template
if (count($templates) > 1) {
	$vars['template_form'] = '<form action="?'.buildQuery($queryParams).'" method="get" id="templateform">';
	$vars['template_select'] = '<select name="template" onchange="javascript:this.form.submit();">';
	natcasesort($templates);
	foreach ($templates as $path => $name) {
		$sel = ($path == $selectedTemplatePath) ? ' selected="selected"' : '';
		$vars['template_select'] .= '<option value="'.$name.'"'.$sel.'>'.$name.'</option>';
	}
	$vars['template_select'] .= '</select>';
	$vars['template_submit'] = '<noscript><input type="submit" value="'.$lang['GO'].'" /></noscript>';
	$vars['template_endform'] = '</form>';
} else {
	$vars['template_form'] = '';
	$vars['template_select'] = '';
	$vars['template_submit'] = '';
	$vars['template_endform'] = '';
}

$vars['indexurl'] = $config->getURL('', '', 'index');
$vars['validationurl'] = getFullURL($_SERVER['SCRIPT_NAME']).'?'.buildQuery($queryParams + array('template' => $template, 'language' => $language), '%26');

// To avoid a possible XSS exploit, need to clean up the passed-in path first
$path = !empty($_REQUEST['path']) ? $_REQUEST['path'] : null;
if ($path === null || $path === '')
	$path = '/';
$vars['safepath'] = escape($path);
// Set operative and peg revisions (if specified) and save passed-in revision
$rev = (int)@$_REQUEST['rev'];
$peg = (int)@$_REQUEST['peg'];
if ($peg === 0)
	$peg = '';
$passrev = $rev;

$listing = array();

// Set up response headers
header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: '.$language);

// Function to create the project selection HTML form
function createProjectSelectionForm() {
	global $config, $vars, $rep, $lang;

	$vars['projects_form'] = '';
	$vars['projects_select'] = '';
	$vars['projects_submit'] = '';
	$vars['projects_endform'] = '';

	if (!$config->showRepositorySelectionForm() || count($config->getRepositories()) < 2)
		return;

	if ($rep) {
		$currentRepoName = $rep->getDisplayName();
		$options = '';
	} else {
		$currentRepoName = '';
		$options = '<option value="" selected="selected"></option>';
	}
	foreach ($config->getRepositories() as $repository) {
		if ($repository->hasReadAccess('/', true)) {
			$repoName = $repository->getDisplayName();
			$sel = ($repoName == $currentRepoName) ? ' selected="selected"' : '';
			$options .= '<option value="'.escape($repoName).'"'.$sel.'>'.escape($repoName).'</option>';
		}
	}
	if (strlen($options) === 0)
		return;

	$url = $config->getURL(-1, '', 'form');
	$hidden = ($config->multiViews) ? '<input type="hidden" name="op" value="form" />' : '';
	$hidden .= '<input type="hidden" name="selectproj" value="1" />';
	$vars['projects_form'] = '<form action="'.$url.'" method="get" id="projectform">'.$hidden;
	$vars['projects_select'] = '<select name="repname" onchange="javascript:this.form.submit();">'.$options.'</select>';
	$vars['projects_submit'] = '<noscript><input type="submit" value="'.$lang['GO'].'" /></noscript>';
	$vars['projects_endform'] = '</form>';
}

// Function to create the revision selection HTML form
function createRevisionSelectionForm() {
	global $config, $lang, $vars, $rep, $path, $rev, $peg;

	if ($rep == null)
		return;

	$params = array('repname' => $rep->getDisplayName(),
	                'path' => ($path == '/' ? '' : $path),
	                'peg' => ($peg ? $peg : $rev));
	$hidden = '';
	foreach ($params as $key => $value) {
		if ($value)
			$hidden .= '<input type="hidden" name="'.$key.'" value="'.escape($value).'" />';
	}
	// The blank "action" attribute makes form link back to the containing page.
	$vars['revision_form'] = '<form action="" method="get" id="revisionform">'.$hidden;
	$vars['revision_input'] = '<input type="text" size="5" name="rev" value="'.($rev ? $rev : 'HEAD').'" />';
	$vars['revision_submit'] = '<input type="submit" value="'.$lang['GO'].'" />';
	$vars['revision_endform'] = '</form>';
}

function checkSendingAuthHeader($rep = false) {
	global $config;
	$auth = null;
	if ($rep) {
		$auth =& $rep->getAuth();
	} else {
		$auth =& $config->getAuth();
	}
	$loggedin = $auth->hasUsername();
	if (!$loggedin) {
		header('WWW-Authenticate: Basic realm="'.str_replace('"', '\"', $auth->getBasicRealm()).'"');
		header('HTTP/1.x 401 Unauthorized', true, 401);
	} else {
		header('HTTP/1.x 403 Forbidden', true, 403);
	}
}
