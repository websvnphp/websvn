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
// auth.inc
//
// Handle reading and interpretation of an SVN auth file

require_once("include/inifile.inc");

define("UNDEFINED", 0);
define("ALLOW", 1);
define("DENY", 2);

class Authentication
{
   var $rights;
   var $user;
   
   function Authentication($accessfile)
   {
      $this->rights = new IniFile();
      $this->rights->readIniFile($accessfile);
      $this->setUsername("");
   }
   
	// setUsername($username)
	//
	// Set the username if it is given, or
	// get the user of the current http session

	function setUsername($username)
	{
		if ($username == "") // Use the current user
		{
			$this->user = @$_SERVER["REMOTE_USER"];
		}
		else
		{
			$this->user = $username; 	
		}
	}

   // inList
   //
   // Check if the user is in the given list and return their read status
   // if they are (UNDEFINED, ALLOW or DENY)
   
   function inList($accessors, $user)
   {
      foreach($accessors As $key => $rights)
      {
         $keymatch = false;
    
         if ($key == "*")
            $keymatch = true;
         else if (!strcmp($key, strtolower($user)))
            $keymatch = true;
         else if ($key{0} == '@')
         {
            $group = substr($key, 1);
            $members = $this->rights->getValue("groups", $group);
            $members = ",".$members.",";
            if (strpos($members, ",".strtolower($user).",") !== false)
               $keymatch = true;
         }
      
         if ($keymatch)
         {
            if (strpos($rights, "r") !== false)
               return ALLOW;
            else
               return DENY;
         }
      }
      
      return UNDEFINED;
   }
   
   // hasReadAccess
   //
   // Returns true if the user has read access to the given path
   
   function hasReadAccess($repos, $path, $checkSubFolders = false)
   {
      $access = UNDEFINED;
  
      // If were told to, we should check sub folders of the path to see if there's
      // a read access below this level.  This is used to display the folders needed
      // to get to the folder to which read access is granted.
      
      if ($checkSubFolders)
      {
         $sections = $this->rights->getSections();
         
         foreach($sections As $section => $accessers)
         {
            $qualified = $repos.":".$path;
            $len = strlen($qualified);
            
            if ($len < strlen($section) && strncmp($section, $qualified, strlen($qualified)) == 0)
            {
               $access = $this->inList($accessers, $this->user);
            }

            if ($access != ALLOW)
            {
               $len = strlen($path);
               if ($len < strlen($section) && strncmp($section, $path, strlen($qualified)) == 0)
               {
                  $access = $this->inList($accessers, $this->user);
               }
            }
            
            if ($access == ALLOW)
               break;
         }     
      }
      
      // If we still don't have access, check each subpath of the path until we find an
      // access level...
      
      if ($access != ALLOW)
      {
         $access = UNDEFINED;
         
         do
         {
            $accessers = $this->rights->getValues($repos.":".$path);
            if (!empty($accessers))
               $access = $this->inList($accessers, $this->user);
            
            if ($access == UNDEFINED)
            {
               $accessers = $this->rights->getValues($path);
               if (!empty($accessers))
                  $access = $this->inList($accessers, $this->user);
            }
             
            // If we've not got a match, remove the sub directory and start again
            if ($access != ALLOW)
            {
               if ($path == "/") break;
               $path = substr($path, 0, strrpos(substr($path, 0, -1), "/") + 1);
            }
                                          
         } while ($access != ALLOW && $path != "");
      }
      
      return $access == ALLOW;
   }
}

?>

