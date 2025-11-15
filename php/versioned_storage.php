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
umask(0);
require_once("utils.php");

function get_dir_contents($storage,$subdir) {
  $pages = $storage->getglob($subdir."/*.".PAGE_EXT);
  $files = $storage->getglob($subdir."/*.*");
  $dirs = $storage->getglob($subdir."/*");
  $dirs = array_filter($dirs, function ($x) use($storage) { return $storage->isdir($x); } );
  $files = array_filter($files,function ($x) { return !preg_match('/\\.'.PAGE_EXT.'$/',$x); });
  $dirs = array_map(function($x) { return basename(trim($x,"/")); },$dirs);
  $dirs = array_filter($dirs,function($x) { return $x !== "" ; });
  $files = array_map(function($x) { return basename(trim($x,"/")); },$files);
  $pages = array_map(function($x) { return basename(trim($x,"/")); },$pages);
  $pages = array_filter($pages, function($x) { return preg_match('/^[^\.]+\.'.PAGE_EXT.'$/',$x); });
  $files = array_diff($files,$pages);
  return [ $dirs, $pages, $files ];
}

/**
 * Abstract base class for VersionedStorage
 *
 * This base class defines the interface that VersionedStorage implements.
 * It is largely for information to the programmer.
 *
 */
abstract class StorageBase extends stdclass {
  abstract function get_mime_type($path);
  abstract function fpath($path);
  abstract function vpath($path,$when=null);
  abstract function path_from_fpath($fpath);
  abstract function store_uploaded($tmp_name,$path,$when=null);
  abstract function log($msg);

  /**
   * store a file or page
   *
   * @param: $path
   * @param: $content
   * @param: $when = null -- time to use when storing, defaults to null meaning time()
   * @param: $storeversion -- whether to also write a version, defaults to true
   **/
  abstract function store(string $path, string $content, int|null $when = null, bool $storeversion = true): int ;
  abstract function del($path);
  abstract function has($path);
  abstract function isdir($path);
  abstract function has_versions($path);
  abstract function has_version($path,$when);
  abstract function getmtime($path);
  abstract function get($path);
  abstract function getglob($path);
  abstract function get_version($path,$when);
  abstract function get_version_times($path);
  abstract function find_leaf_to_root($dir,$filename);
  abstract function find_root_to_leaf($dir,$filename);
}

/**
 * Implementation of versioned storage.
 *
 * This is the only implementation of the StorageBase
 * interface we use. Extending the interface is largely
 * only so that we can give the interface and documentation
 * above.
 *
 */
class VersionedStorage extends StorageBase { 
  /**
   * Constructor
   *
   * Give the location where current files are to be stored.
   * Give the location where the versions are to be stored.
   *
   */
  function __construct($files_path, $versions_path) {
    $this->files_path = $files_path;
    $this->versions_path = $versions_path;
    $this->error = null;
  }
  function get_mime_type($path) {
    # from: 
    $fpath = $this->fpath($path);
    if( is_file($fpath) ) {
      return get_mime_type_for_fpath($fpath);
    }
    return null;
  }
  function fpath($path) {
    return $this->files_path."/".$path;
  }
  function vpath($path,$when=null) {
    if( is_null($when) ) $when = time();
    return $this->versions_path."/".$path.".".$when;
  }
  function path_from_fpath($fpath) {
    # credit: Fabio Mora's answer at https://stackoverflow.com/questions/4517067/remove-a-string-from-the-beginning-of-a-string
    $prefix = $this->files_path;
    if (substr($fpath, 0, strlen($prefix)) == $prefix) {
        $fpath = substr($fpath, strlen($prefix));
    } 
    return $fpath;
  }
  function store_uploaded($tmp_name,$path,$when=null) {
    if( is_null($when) ) $when = time();
    # write both file and version
    $path_components = explode("/",$path);
    $filename = array_pop($path_components);
    if( strpos($filename,".") == false ) {
      throw new Exception("Invalid filename -- must contain a .");
    }
    $fpath = $this->fpath($path);
    $vpath = $this->vpath($path,$when);
    $fdir = dirname($fpath);
    $vdir = dirname($vpath);
    $this->log("UPL path=$path");
    $this->log("UPL fpath=$fpath");
    $this->log("UPL vpath=$vpath");
    $this->log("UPL fdir=$fdir");
    $this->log("UPL vdir=$vdir");
    $this->log("UPL path=$path");
    if( !is_dir($fdir) ) {
      if( mkdir($fdir,0775,true) == false) {
        throw new Exception("Failed to create files directory $fdir");
      }
    }
    if( !is_dir($vdir) ) {
      if( mkdir($vdir,0775,true) == false) {
        throw new Exception("Failed to create versions directory $vdir");
      }
    }
    if( ! move_uploaded_file($tmp_name,$vpath) ) {
      throw new Exception("Failed to move tmp file [[$tmp_name]] to [[$vpath]].");
    }
    if( file_exists($fpath) && ! @unlink($fpath) ) {
      throw new Exception("Failed to unlink file $fpath.");
    }
    if( ! @link($vpath,$fpath) ) {
      throw new Exception("Failed to link to file $vpath.");
    }
    return true;
  }
  function log($msg) {
    @file_put_contents("log",$msg."\n",FILE_APPEND);
  }
  function store(string $path, string $content, int|null $when=null, bool $storeversion=true): int {
    if( is_null($when) ) $when = time();
    # write both file and version
    $path_components = explode("/",$path);
    $filename = array_pop($path_components);
    if( strpos($filename,".") === false ) {
      throw new Exception("Invalid filename -- must contain a .");
    }
    $fpath = $this->fpath($path);
    $vpath = $this->vpath($path,$when);
    $fdir = dirname($fpath);
    $vdir = dirname($vpath);
    # write the version first.
    if( $storeversion && !is_dir($vdir) ) {
      if( mkdir($vdir,0775,true) == false) {
        throw new Exception("Failed to create versions directory $vdir");
      }
    }
    if( $storeversion && @file_put_contents($vpath,$content) == false ) {
      throw new Exception("Failed to write version $vpath.");
    }
    # then write the actual file.
    if( !is_dir($fdir) ) {
      if( mkdir($fdir,0775,true) == false) {
        throw new Exception("Failed to create files directory $fdir");
      }
    }
    if( @file_put_contents($fpath,$content) == false ) {
      throw new Exception("Failed to write file $fpath.");
    }
    return $when;
  }
  function del($path) {
    # delete file (but not version)
    $fpath = $this->fpath($path);
    if( file_exists($fpath) ) {
      return unlink($fpath);
    }
    return false;
  }
  function has($path) {
    # does file at path exist
    $fpath = $this->fpath($path);
    return file_exists($fpath);
  }
  function isdir($path) {
    $fpath = $this->fpath($path);
    return is_dir($fpath);
  }
  function has_versions($path) {
    # do we have version for this path (e.g. if a file was deleted)
    $vpath = $this->vpath($path,"*");
    $versions = glob($vpath);
    if( count($versions) > 0 ) {
      return true;
    }
    return false;
  }
  function has_version($path,$when) {
    # do we have a particular version
    $vpath = $this->vpath($path,$when);
    return file_exists($vpath);
  }
  function getmtime($path) {
    # get last modified time
    $fpath = $this->fpath($path);
    return 0;
    return filemtime($fpath);
  }
  function get($path) {
    # get content
    $fpath = $this->fpath($path);
    return file_get_contents($fpath);
  }
  function getglob($path) {
    $fpath = $this->fpath($path);
    $files = glob($fpath);
    $paths = array_map([$this,"path_from_fpath"],$files);
    return $paths;
  }
  function getregex($subdir,$regex,$opts) {
    $fsubdir = $this->fpath($subdir);
    $files = glob($fsubdir."/*");
    $filenames = array_map(function($x) {
      $xs = explode("/",$x);
      $y = array_pop($xs);
      return $y;
    },$files);
    $filenames = array_filter($filenames,
      function($x) use($regex,$opts) {
        return preg_match("/$regex/$opts",$x,$m);
      });
    return $filenames;
  }
  function get_version($path,$when) {
    $vpath = $this->vpath($path,$when);
    if( file_exists($vpath) ) {
      return file_get_contents($vpath);
    }
    return false;
  }
  function get_version_times($path) {
    $vpath = $this->vpath($path,"*");
    $versions = glob($vpath);
    $version_times = array();
    foreach($versions as $v) {
      preg_match("/\\.(\\d+)$/",$v,$m);
      array_push($version_times,intval($m[1]));
    }
    return $version_times;
  }
  function find_leaf_to_root($dir,$filename) {
    $found = array();
    $dir_components = explode("/",$dir);
    #echo "Search $dir\n";
    if( $dir != "" ) {
      do {
        $path = implode("/",$dir_components)."/".$filename;
        #echo "find dir $path\n";
        if( $this->has($path) ) {
          array_push($found,$path);
        }
        array_pop($dir_components);
      } while( count($dir_components) > 0 );
    }
    if( $this->has($filename) ) {
      # check root
      #echo "in root $filename\n";
      array_push($found,$filename);
    }
    return $found;
  }
  function find_root_to_leaf($dir,$filename) {
    return array_reverse($this->find_leaf_to_root($dir,$filename));
  }
  # later add facilities for finding versions between given dates
}
