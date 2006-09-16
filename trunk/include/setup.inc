<?php
# vim:et:ts=3:sts=3:sw=3:fdm=marker:

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright © 2004-2006 Tim Armes, Matt Sicker
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
// setup.inc
//
// Global setup

// --- DON'T CHANGE THIS FILE ---
//
// User changes should be done in config.ini

// Include the configuration class
require_once 'include/configclass.inc';

// Create the config
$config = new Config;

// Set up the default character encodings
if (function_exists('iconv_get_encoding'))
{
  $config->setInputEncoding(iconv_get_encoding('input_encoding'));
}

// Define the language array
$lang = array();
$langNames = array();

// Set up locwebsvnhttp
// Note: we will use nothing in MultiViews mode so that the URLs use the root
//       directory by default.
if (empty($locwebsvnhttp))
   $locwebsvnhttp = defined('WSVN_MULTIVIEWS') ? '' : '.';

if (empty($locwebsvnreal))
   $locwebsvnreal = '.';

$vars['locwebsvnhttp'] = $locwebsvnhttp;

// Include a default language file (must go before config.inc)
require 'languages/english.inc';

// Get the user's personalised config (requires the locwebsvnhttp stuff above)
require_once 'config.inc';

// Load PHP_Compat if we're going to use it. This needs to be done after including config.inc (which contains
// the setting) but before svnlook.inc (which requires util.inc, which contains PHP4-incompatible functions)
if ($config->isPHPCompatEnabled()) {
   require_once($config->getPHPCompatFile());
   require_once 'include/php5compat.inc';
}

require_once 'include/svnlook.inc';

// Make sure that the input locale is set up correctly
setlocale(LC_ALL, '');

// {{{ Content-Type's
// Set up the default content-type extension handling

$contentType = array (

'.dwg' => 'application/acad', // AutoCAD Drawing files
'.DWG' => 'application/acad', // AutoCAD Drawing files
'.arj' => 'application/arj', //  
'.ccAD' => 'application/clariscad', // ClarisCAD files
'.DRW' => 'application/drafting', // MATRA Prelude drafting
'.dxf' => 'application/dxf', // DXF (AutoCAD)
'.DXF' => 'application/dxf', // DXF (AutoCAD)
'.xl' => 'application/excel', // Microsoft Excel
'.unv' => 'application/i-deas', //SDRC I-DEAS files
'.UNV' => 'application/i-deas', //SDRC I-DEAS files
'.igs' => 'application/iges', // IGES graphics format
'.iges' => 'application/iges', // IGES graphics format
'.IGS' => 'application/iges', // IGES graphics format
'.IGES' => 'application/iges', // IGES graphics format
'.hqx' => 'application/mac-binhex40', // Macintosh BinHex format
'.word' => 'application/msword', // Microsoft Word
'.w6w' => 'application/msword', // Microsoft Word
'.doc' => 'application/msword', // Microsoft Word
'.wri' => 'application/mswrite', // Microsoft Write
'.bin' => 'application/octet-stream', // Uninterpreted binary
'.exe' => 'application/x-msdownload', // Windows EXE
'.EXE' => 'application/x-msdownload', // Windows EXE
'.oda' => 'application/oda', //  
'.pdf' => 'application/pdf', // PDF (Adobe Acrobat)
'.ai' => 'application/postscript', // PostScript
'.PS' => 'application/postscript', // PostScript
'.ps' => 'application/postscript', // PostScript
'.eps' => 'application/postscript', // PostScript
'.prt' => 'application/pro_eng', // PTC Pro/ENGINEER
'.PRT' => 'application/pro_eng', // PTC Pro/ENGINEER
'.part' => 'application/pro_eng', // PTC Pro/ENGINEER
'.rtf' => 'application/rtf', // Rich Text Format
'.set' => 'application/set', // SET (French CAD standard)
'.SET' => 'application/set', // SET (French CAD standard)
'.stl' => 'application/sla', // Stereolithography
'.STL' => 'application/sla', // Stereolithography
'.SOL' => 'application/solids', // MATRA Prelude Solids
'.stp' => 'application/STEP', // ISO-10303 STEP data files
'.STP' => 'application/STEP', // ISO-10303 STEP data files
'.step' => 'application/STEP', // ISO-10303 STEP data files
'.STEP' => 'application/STEP', // ISO-10303 STEP data files
'.vda' => 'application/vda', // VDA-FS Surface data
'.VDA' => 'application/vda', // VDA-FS Surface data
'.dir' => 'application/x-director', // Macromedia Director
'.dcr' => 'application/x-director', // Macromedia Director
'.dxr' => 'application/x-director', // Macromedia Director
'.mif' => 'application/x-mif', // FrameMaker MIF Format
'.csh' => 'application/x-csh', // C-shell script
'.dvi' => 'application/x-dvi', // TeX DVI
'.gz' => 'application/x-gzip', // GNU Zip
'.gzip' => 'application/x-gzip', // GNU Zip
'.hdf' => 'application/x-hdf', // ncSA HDF Data File
'.latex' => 'application/x-latex', // LaTeX source
'.nc' => 'application/x-netcdf', // Unidata netCDF
'.cdf' => 'application/x-netcdf', // Unidata netCDF
'.sit' => 'application/x-stuffit', // Stiffut Archive
'.tcl' => 'application/x-tcl', // TCL script
'.texinfo' => 'application/x-texinfo', // Texinfo (Emacs)
'.texi' => 'application/x-texinfo', // Texinfo (Emacs)
'.t' => 'application/x-troff', // Troff
'.tr' => 'application/x-troff', // Troff
'.roff' => 'application/x-troff', // Troff
'.man' => 'application/x-troff-man', // Troff with MAN macros
'.me' => 'application/x-troff-me', // Troff with ME macros
'.ms' => 'application/x-troff-ms', // Troff with MS macros
'.src' => 'application/x-wais-source', // WAIS source
'.bcpio' => 'application/x-bcpio', // Old binary CPIO
'.cpio' => 'application/x-cpio', // POSIX CPIO
'.gtar' => 'application/x-gtar', // GNU tar
'.shar' => 'application/x-shar', // Shell archive
'.sv4cpio' => 'application/x-sv4cpio', // SVR4 CPIO
'.sv4crc' => 'application/x-sv4crc', // SVR4 CPIO with CRC
'.tar' => 'application/x-tar', // 4.3BSD tar format
'.ustar' => 'application/x-ustar', // POSIX tar format
'.hlp' => 'application/x-winhelp', // Windows Help
'.zip' => 'application/zip', // ZIP archive
'.au' => 'audio/basic', // Basic audio (usually m-law)
'.snd' => 'audio/basic', // Basic audio (usually m-law)
'.aif' => 'audio/x-aiff', // AIFF audio
'.aiff' => 'audio/x-aiff', // AIFF audio
'.aifc' => 'audio/x-aiff', // AIFF audio
'.ra' => 'audio/x-pn-realaudio', // RealAudio
'.ram' => 'audio/x-pn-realaudio', // RealAudio
'.rpm' => 'audio/x-pn-realaudio-plugin', // RealAudio (plug-in)
'.wav' => 'audio/x-wav', // Windows WAVE audio
'.mp3' => 'audio/x-mp3', // MP3 files
'.gif' => 'image/gif', // gif image
'.ief' => 'image/ief', // Image Exchange Format
'.jpg' => 'image/jpeg', // JPEG image
'.JPG' => 'image/jpeg', // JPEG image
'.JPE' => 'image/jpeg', // JPEG image
'.jpe' => 'image/jpeg', // JPEG image
'.JPEG' => 'image/jpeg', // JPEG image
'.jpeg' => 'image/jpeg', // JPEG image
'.pict' => 'image/pict', // Macintosh PICT
'.tiff' => 'image/tiff', // TIFF image
'.tif' => 'image/tiff', // TIFF image
'.ras' => 'image/x-cmu-raster', // CMU raster
'.pnm' => 'image/x-portable-anymap', // PBM Anymap format
'.pbm' => 'image/x-portable-bitmap', // PBM Bitmap format
'.pgm' => 'image/x-portable-graymap', // PBM Graymap format
'.ppm' => 'image/x-portable-pixmap', // PBM Pixmap format
'.rgb' => 'image/x-rgb', // RGB Image
'.xbm' => 'image/x-xbitmap', // X Bitmap
'.xpm' => 'image/x-xpixmap', // X Pixmap
'.xwd' => 'image/x-xwindowdump', // X Windows dump (xwd) format
'.zip' => 'multipart/x-zip', // PKZIP Archive
'.gzip' => 'multipart/x-gzip', // GNU ZIP Archive
'.mpeg' => 'video/mpeg', // MPEG video
'.mpg' => 'video/mpeg', // MPEG video
'.MPG' => 'video/mpeg', // MPEG video
'.MPE' => 'video/mpeg', // MPEG video
'.mpe' => 'video/mpeg', // MPEG video
'.MPEG' => 'video/mpeg', // MPEG video
'.mpeg' => 'video/mpeg', // MPEG video
'.qt' => 'video/quicktime', // QuickTime Video
'.mov' => 'video/quicktime', // QuickTime Video
'.avi' => 'video/msvideo', // Microsoft Windows Video
'.movie' => 'video/x-sgi-movie', // SGI Movieplayer format
'.wrl' => 'x-world/x-vrml', // VRML Worlds
'.odt' => 'application/vnd.oasis.opendocument.text',                  // OpenDocument Text
'.ott' => 'application/vnd.oasis.opendocument.text-template',         // OpenDocument Text Template
'.ods' => 'application/vnd.oasis.opendocument.spreadsheet',           // OpenDocument Spreadsheet
'.ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',  // OpenDocument Spreadsheet Template
'.odp' => 'application/vnd.oasis.opendocument.presentation',          // OpenDocument Presentation
'.otp' => 'application/vnd.oasis.opendocument.presentation-template', // OpenDocument Presentation Template
'.odg' => 'application/vnd.oasis.opendocument.graphics',              // OpenDocument Drawing
'.otg' => 'application/vnd.oasis.opendocument.graphics-template',     // OpenDocument Drawing Template
'.odc' => 'application/vnd.oasis.opendocument.chart',                 // OpenDocument Chart
'.otc' => 'application/vnd.oasis.opendocument.chart-template',        // OpenDocument Chart Template
'.odf' => 'application/vnd.oasis.opendocument.formula',               // OpenDocument Formula
'.otf' => 'application/vnd.oasis.opendocument.formula-template',      // OpenDocument Formula Template
'.odi' => 'application/vnd.oasis.opendocument.image',                 // OpenDocument Image
'.oti' => 'application/vnd.oasis.opendocument.image-template',        // OpenDocument Image Template
'.odm' => 'application/vnd.oasis.opendocument.text-master',           // OpenDocument Master Document
'.oth' => 'application/vnd.oasis.opendocument.text-web',              // HTML Document Template
'.odb' => 'application/vnd.oasis.opendocument.database',              // OpenDocument Database

);

// }}}

// {{{ Enscript file extensions

// List of extensions recognised by enscript.

$extEnscript = array
(
  '.ada'     => 'ada',
  '.adb'     => 'ada',
  '.ads'     => 'ada',
  '.awk'     => 'awk',
  '.c'       => 'c',
  '.c++'     => 'cpp',
  '.cc'      => 'cpp',
  '.cpp'     => 'cpp',
  '.csh'     => 'csh',
  '.cxx'     => 'cpp',
  '.diff'    => 'diffu',
  '.dpr'     => 'delphi',
  '.el'      => 'elisp',
  '.eps'     => 'postscript',
  '.f'       => 'fortran',
  '.for'     => 'fortran',
  '.gs'      => 'haskell',
  '.h'       => 'c',
  '.hpp'     => 'cpp',
  '.hs'      => 'haskell',
  '.htm'     => 'html',
  '.html'    => 'html',
  '.idl'     => 'idl',
  '.java'    => 'java',
  '.js'      => 'javascript',
  '.lgs'     => 'haskell',
  '.lhs'     => 'haskell',
  '.m'       => 'objc',
  '.m4'      => 'm4',
  '.man'     => 'nroff',
  '.nr'      => 'nroff',
  '.p'       => 'pascal',
  '.pas'     => 'delphi',
  '.patch'   => 'diffu',
  '.pkg'     => 'sql', 
  '.pl'      => 'perl',
  '.pm'      => 'perl',
  '.pp'      => 'pascal',
  '.ps'      => 'postscript',
  '.s'       => 'asm',
  '.scheme'  => 'scheme',
  '.scm'     => 'scheme',
  '.scr'     => 'synopsys',
  '.sh'      => 'sh',
  '.shtml'   => 'html',
  '.sql'     => 'sql',
  '.st'      => 'states',
  '.syn'     => 'synopsys',
  '.synth'   => 'synopsys',
  '.tcl'     => 'tcl',
  '.tex'     => 'tex',
  '.texi'    => 'tex',
  '.texinfo' => 'tex',
  '.v'       => 'verilog',
  '.vba'     => 'vba',
  '.vh'      => 'verilog',
  '.vhd'     => 'vhdl',
  '.vhdl'    => 'vhdl',
  '.py'      => 'python',
  
  // The following are handled internally by WebSVN, since there's no
  // support for them in Enscript
  
  '.php'     => 'php',
  '.phtml'   => 'php',
  '.php3'    => 'php',
  '.inc'     => 'php'  
);

// }}}

// Default 'zipped' array

$zipped = array ();

// Set up the version info

initSvnVersion($major,$minor);

// Get the language choice as defained as the default by config.inc
$user_defaultLang = $lang['LANGUAGENAME'];

// Override this with the user choice if there is one, and memorise the setting
// as a cookie (since we don't have user accounts, we can't store the setting
// anywhere else).  We try to memorise a permanant cookie and a per session cookie
// in case the user's disabled permanant ones.

if (!empty($_REQUEST['langchoice']))
{
   $user_defaultLang = $_REQUEST['langchoice'];
   setcookie('storedlang', $_REQUEST['langchoice'], time()+(3600*24*356*10), '/');
   setcookie('storedsesslang', $_REQUEST['langchoice']);
}
else // Try to read an existing cookie if there is one
{
   if (!empty($_COOKIE['storedlang'])) $user_defaultLang = $_COOKIE['storedlang'];
   else if (!empty($_COOKIE['storedsesslang'])) $user_defaultLang = $_COOKIE['storedsesslang'];
}
   
$user_defaultFile = '';
if ($handle = opendir('languages'))
{
   // Read the language name for each language.
   while (false !== ($file = readdir($handle)))
   {
      if ($file{0} != '.' && !is_dir('languages'.DIRECTORY_SEPARATOR.$file))
      {
         $lang['LANGUAGENAME'] = '';
         require 'languages/'.$file;
         if ($lang['LANGUAGENAME'] != '')
         {
            $langNames[] = $lang['LANGUAGENAME'];
            if ($lang['LANGUAGENAME'] == $user_defaultLang)
               $user_defaultFile = $file;
         }
      }
   }

   closedir($handle);
    
   // XXX: this shouldn't be necessary
   // ^ i.e. just require english.inc, then the desired language
   // Reload english to get untranslated strings
   require 'languages/english.inc';
   
   // Reload the default language
   if (!empty($user_defaultFile))
      require 'languages/'.$user_defaultFile;
      
   $url = getParameterisedSelfUrl(true);
   $vars["lang_form"] = "<form action=\"$url\" method=\"post\" id=\"langform\">";
   $vars["lang_select"] = "<select name=\"langchoice\" onchange=\"javascript:this.form.submit();\">";
   
   reset($langNames);
   foreach ($langNames as $name)
   {
      $sel = "";
      if ($name == $user_defaultLang) $sel = "selected";
      $vars["lang_select"] .= "<option value=\"$name\" $sel>$name</option>";
   }
   
   $vars["lang_select"] .= "</select>";
   $vars["lang_submit"] = "<input type=\"submit\" value=\"${lang["GO"]}\">";
   $vars["lang_endform"] = "</form>";   
}

// Set up headers

header("Content-type: text/html; charset=".$config->outputEnc);

// Make sure that the user has set up a repository

$reps = $config->getRepositories();
if (empty($reps[0]))
{
   echo $lang["SUPPLYREP"];
   exit;
}

// Override the rep parameter with the repository name if it's available
$repname = @$_REQUEST["repname"];
if (isset($repname))
{
    $rep = $config->findRepository($repname);
}
else
   $rep = $reps[0];
   
// Retrieve other standard parameters

# due to possible XSS exploit, we need to clean up path first
$path = !empty($_REQUEST['path']) ? $_REQUEST['path'] : null;
$vars['safepath'] = htmlentities($path);
$rev = (int)@$_REQUEST["rev"];
$showchanged = (@$_REQUEST["sc"] == 1)?1:0;

// Function to create the project selection HTML form
function createProjectSelectionForm()
{ 
   global $config, $vars, $rep, $lang, $showchanged;
   
   $url = $config->getURL(-1, "", "form");
   $vars["projects_form"] = "<form action=\"$url\" method=\"post\" id=\"projectform\">";
   
   $reps = $config->getRepositories();
   $vars["projects_select"] = "<select name=\"repname\" onchange=\"javascript:this.form.submit();\">";
   
   foreach ($reps as $trep)
   {
      if ($trep->hasReadAccess("/", true))
      {
         if ($rep->getDisplayName() == $trep->getDisplayName())
            $sel = "selected";
         else
            $sel = "";
            
         $vars["projects_select"] .= "<option value=\"".$trep->getDisplayName()."\" $sel>".$trep->getDisplayName()."</option>";
      }
   }
   
   $vars["projects_select"] .= "</select>";
   
   $vars["projects_submit"] = "<input type=\"submit\" value=\"${lang["GO"]}\" />";
   $vars["projects_endform"] = "<input type=\"hidden\" name=\"selectproj\" value=\"1\" /><input type=\"hidden\" name=\"op\" value=\"form\" /><input type=\"hidden\" name=\"sc\" value=\"$showchanged\" /></form>";   
}

// Create the form if we're not in MultiViews.  Otherwise wsvn must create the form once the current project has
// been found

if (!$config->multiViews)
{
   createProjectSelectionForm();
}

if ($rep)
{
   $vars["allowdownload"] = $rep->getAllowDownload();
   $vars["repname"] = $rep->getDisplayName();
}

// As of version 1.70 the output encoding is forced to be UTF-8, since this is the output
// encoding returned by svn log --xml.  This is good, since we are no longer reliant on PHP's
// rudimentary conversions.

$vars["charset"] = "UTF-8";
?>
