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
// setup.inc
//
// Global setup

// --- DON'T CHANGE THIS FILE ---
//
// User changes should be done in config.ini

// Include the configuration class
require("include/configclass.inc");

// Define the language array
$lang = array();

// Include a default language file.
require("languages/english.inc");

// Set up svnlocwebsvn
if (empty($locwebsvnhttp))
   $locwebsvnhttp = ".";

if (empty($locwebsvnreal))
   $locwebsvnreal = ".";

$vars["locwebsvnhttp"] = $locwebsvnhttp;

// Make sure that the input locale is set up correctly
setlocale(LC_ALL, "");

// Create the config
$config = new Config;

// Set up the default character encodings
if (function_exists("iconv_get_encoding"))
{
  $config->setInputEncoding(iconv_get_encoding("input_encoding"));
  $config->setOutputEncoding(iconv_get_encoding("input_encoding"));   
}

// Set up the default content-type extension handling

$contentType = array (

".dwg" => "application/acad", // AutoCAD Drawing files
".DWG" => "application/acad", // AutoCAD Drawing files
".arj" => "application/arj", //  
".ccAD" => "application/clariscad", // ClarisCAD files
".DRW" => "application/drafting", // MATRA Prelude drafting
".dxf" => "application/dxf", // DXF (AutoCAD)
".DXF" => "application/dxf", // DXF (AutoCAD)
".xl" => "application/excel", // Microsoft Excel
".unv" => "application/i-deas", //SDRC I-DEAS files
".UNV" => "application/i-deas", //SDRC I-DEAS files
".igs" => "application/iges", // IGES graphics format
".iges" => "application/iges", // IGES graphics format
".IGS" => "application/iges", // IGES graphics format
".IGES" => "application/iges", // IGES graphics format
".hqx" => "application/mac-binhex40", // Macintosh BinHex format
".word" => "application/msword", // Microsoft Word
".w6w" => "application/msword", // Microsoft Word
".doc" => "application/msword", // Microsoft Word
".wri" => "application/mswrite", // Microsoft Write
".bin" => "application/octet-stream", // Uninterpreted binary
".exe" => "application/x-msdownload", // Windows EXE
".EXE" => "application/x-msdownload", // Windows EXE
".oda" => "application/oda", //  
".pdf" => "application/pdf", // PDF (Adobe Acrobat)
".ai" => "application/postscript", // PostScript
".PS" => "application/postscript", // PostScript
".ps" => "application/postscript", // PostScript
".eps" => "application/postscript", // PostScript
".prt" => "application/pro_eng", // PTC Pro/ENGINEER
".PRT" => "application/pro_eng", // PTC Pro/ENGINEER
".part" => "application/pro_eng", // PTC Pro/ENGINEER
".rtf" => "application/rtf", // Rich Text Format
".set" => "application/set", // SET (French CAD standard)
".SET" => "application/set", // SET (French CAD standard)
".stl" => "application/sla", // Stereolithography
".STL" => "application/sla", // Stereolithography
".SOL" => "application/solids", // MATRA Prelude Solids
".stp" => "application/STEP", // ISO-10303 STEP data files
".STP" => "application/STEP", // ISO-10303 STEP data files
".step" => "application/STEP", // ISO-10303 STEP data files
".STEP" => "application/STEP", // ISO-10303 STEP data files
".vda" => "application/vda", // VDA-FS Surface data
".VDA" => "application/vda", // VDA-FS Surface data
".dir" => "application/x-director", // Macromedia Director
".dcr" => "application/x-director", // Macromedia Director
".dxr" => "application/x-director", // Macromedia Director
".mif" => "application/x-mif", // FrameMaker MIF Format
".csh" => "application/x-csh", // C-shell script
".dvi" => "application/x-dvi", // TeX DVI
".gz" => "application/x-gzip", // GNU Zip
".gzip" => "application/x-gzip", // GNU Zip
".hdf" => "application/x-hdf", // ncSA HDF Data File
".latex" => "application/x-latex", // LaTeX source
".nc" => "application/x-netcdf", // Unidata netCDF
".cdf" => "application/x-netcdf", // Unidata netCDF
".sh" => "application/x-sh", // Bourne shell script
".sit" => "application/x-stuffit", // Stiffut Archive
".tcl" => "application/x-tcl", // TCL script
".tex" => "application/x-tex", // TeX source
".texinfo" => "application/x-texinfo", // Texinfo (Emacs)
".texi" => "application/x-texinfo", // Texinfo (Emacs)
".t" => "application/x-troff", // Troff
".tr" => "application/x-troff", // Troff
".roff" => "application/x-troff", // Troff
".man" => "application/x-troff-man", // Troff with MAN macros
".me" => "application/x-troff-me", // Troff with ME macros
".ms" => "application/x-troff-ms", // Troff with MS macros
".src" => "application/x-wais-source", // WAIS source
".bcpio" => "application/x-bcpio", // Old binary CPIO
".cpio" => "application/x-cpio", // POSIX CPIO
".gtar" => "application/x-gtar", // GNU tar
".shar" => "application/x-shar", // Shell archive
".sv4cpio" => "application/x-sv4cpio", // SVR4 CPIO
".sv4crc" => "application/x-sv4crc", // SVR4 CPIO with CRC
".tar" => "application/x-tar", // 4.3BSD tar format
".ustar" => "application/x-ustar", // POSIX tar format
".hlp" => "application/x-winhelp", // Windows Help
".zip" => "application/zip", // ZIP archive
".au" => "audio/basic", // Basic audio (usually m-law)
".snd" => "audio/basic", // Basic audio (usually m-law)
".aif" => "audio/x-aiff", // AIFF audio
".aiff" => "audio/x-aiff", // AIFF audio
".aifc" => "audio/x-aiff", // AIFF audio
".ra" => "audio/x-pn-realaudio", // RealAudio
".ram" => "audio/x-pn-realaudio", // RealAudio
".rpm" => "audio/x-pn-realaudio-plugin", // RealAudio (plug-in)
".wav" => "audio/x-wav", // Windows WAVE audio
".mp3" => "audio/x-mp3", // MP3 files
".gif" => "image/gif", // gif image
".ief" => "image/ief", // Image Exchange Format
".jpg" => "image/jpeg", // JPEG image
".JPG" => "image/jpeg", // JPEG image
".JPE" => "image/jpeg", // JPEG image
".jpe" => "image/jpeg", // JPEG image
".JPEG" => "image/jpeg", // JPEG image
".jpeg" => "image/jpeg", // JPEG image
".pict" => "image/pict", // Macintosh PICT
".tiff" => "image/tiff", // TIFF image
".tif" => "image/tiff", // TIFF image
".ras" => "image/x-cmu-raster", // CMU raster
".pnm" => "image/x-portable-anymap", // PBM Anymap format
".pbm" => "image/x-portable-bitmap", // PBM Bitmap format
".pgm" => "image/x-portable-graymap", // PBM Graymap format
".ppm" => "image/x-portable-pixmap", // PBM Pixmap format
".rgb" => "image/x-rgb", // RGB Image
".xbm" => "image/x-xbitmap", // X Bitmap
".xpm" => "image/x-xpixmap", // X Pixmap
".xwd" => "image/x-xwindowdump", // X Windows dump (xwd) format
".zip" => "multipart/x-zip", // PKZIP Archive
".gzip" => "multipart/x-gzip", // GNU ZIP Archive
".mpeg" => "video/mpeg", // MPEG video
".mpg" => "video/mpeg", // MPEG video
".MPG" => "video/mpeg", // MPEG video
".MPE" => "video/mpeg", // MPEG video
".mpe" => "video/mpeg", // MPEG video
".MPEG" => "video/mpeg", // MPEG video
".mpeg" => "video/mpeg", // MPEG video
".qt" => "video/quicktime", // QuickTime Video
".mov" => "video/quicktime", // QuickTime Video
".avi" => "video/msvideo", // Microsoft Windows Video
".movie" => "video/x-sgi-movie", // SGI Movieplayer format
".wrl" => "x-world/x-vrml" // VRML Worlds

);

// List of extensions recognised by enscript.

$extEnscript = array
(
  ".ada"     => "ada",
  ".adb"     => "ada",
  ".ads"     => "ada",
  ".awk"     => "awk",
  ".c"       => "c",
  ".c++"     => "cpp",
  ".cc"      => "cpp",
  ".cpp"     => "cpp",
  ".csh"     => "csh",
  ".cxx"     => "cpp",
  ".diff"    => "diffu",
  ".dpr"     => "delphi",
  ".el"      => "elisp",
  ".eps"     => "postscript",
  ".f"       => "fortran",
  ".for"     => "fortran",
  ".gs"      => "haskell",
  ".h"       => "c",
  ".hpp"     => "cpp",
  ".hs"      => "haskell",
  ".htm"     => "html",
  ".html"    => "html",
  ".idl"     => "idl",
  ".java"    => "java",
  ".js"      => "javascript",
  ".lgs"     => "haskell",
  ".lhs"     => "haskell",
  ".m"       => "objc",
  ".m4"      => "m4",
  ".man"     => "nroff",
  ".nr"      => "nroff",
  ".p"       => "pascal",
  ".pas"     => "delphi",
  ".patch"   => "diffu",
  ".pkg"     => "sql", 
  ".pl"      => "perl",
  ".pm"      => "perl",
  ".pp"      => "pascal",
  ".ps"      => "postscript",
  ".s"       => "asm",
  ".scheme"  => "scheme",
  ".scm"     => "scheme",
  ".scr"     => "synopsys",
  ".sh"      => "sh",
  ".shtml"   => "html",
  ".sql"     => "sql",
  ".st"      => "states",
  ".syn"     => "synopsys",
  ".synth"   => "synopsys",
  ".tcl"     => "tcl",
  ".tex"     => "tex",
  ".texi"    => "tex",
  ".texinfo" => "tex",
  ".v"       => "verilog",
  ".vba"     => "vba",
  ".vh"      => "verilog",
  ".vhd"     => "vhdl",
  ".vhdl"    => "vhdl",
  ".py"      => "python",
  
  // The following are handled internally by WebSVN, since there's no
  // support for them in Enscript
  
  ".php"     => "php",
  ".inc"     => "php"  
);

// Default 'zipped' array

$zipped = array ();

// Get the user's personalised config

require("config.inc");

// Create the project change combo box
 
$url = $config->getURL(-1, "", "form");
$vars["projects_form"] = "<form action=\"$url\" method=\"post\" name=\"projectform\">";

$reps = $config->getRepositories();
$vars["projects_select"] = "<select name=\"rep\">";
foreach ($reps as $index => $rep)
{
   $vars["projects_select"] .= "<option value=\"$index\">${rep["name"]}</option>";
}
$vars["projects_select"] .= "</select>";

$vars["projects_submit"] = "<input type=\"submit\" value=\"${lang["GO"]}\">";
$vars["projects_endform"] = "<input type=\"hidden\" name=\"selectproj\" value=\"1\"><input type=\"hidden\" name=\"op\" value=\"form\"></form>";   

?>
