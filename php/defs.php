<?php
/*
Purple Tree 3
Copyright (C) 2023-2025

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, version 3.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see https://www.gnu.org/licenses/.
*/
# DEFAULT DIRS
if(!defined('DATA_DIR')) { define('DATA_DIR',SITE_DIR."/data"); }
if(!defined('ROOT_DIR')) { define('ROOT_DIR',SITE_DIR."/root"); }
if(!defined('FILES_DIR')) { define('FILES_DIR',SITE_DIR."/files"); }
if(!defined('VERSIONS_DIR')) { define('VERSIONS_DIR',SITE_DIR."/versions"); }
if(!defined('STATIC_DIR')) { define('STATIC_DIR',SITE_DIR."/static"); }
if(!defined('LOG_DIR')) { define('LOG_DIR',SITE_DIR."/log"); }
if(!defined('TEMPLATES_DIR')) { define('TEMPLATES_DIR',SITE_DIR."/templates"); }
if(!defined('PTMD_CLASS')) { define('PTMD_CLASS','PTMD'); }

define('MAX_FILENAME_LENGTH',64);
# note that cwd is where root/index.php is, not relative to the location of the .php file doing the require

define("RECENT_WRITES_FILE","recent_writes.log");
define("PAGE_EXT","ptmd");
define("DIR_PERMS",0775);
define("FILE_PERMS",0664);
// usage:
// %b Luke 12:25 :- And which of you by being anxious can add a single hour to his span of life?

require_once("detect_mobile.php");
