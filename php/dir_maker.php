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

class DirMaker extends stdclass {
  function __construct($wiki) {
    $this->wiki = $wiki;
  }
  // TODO: Remove
  function generate_content() {
    $filename = $this->wiki->filename;
    switch($filename) {
      case ".home":
      case ".dir":
      case ".d":
        return $this->generate_dir();
      case ".dirs":
      case ".ds":
        return $this->generate_dirs();
      case ".pages":
      case ".ps":
      case ".p":
        return $this->generate_pages();
      case ".files":
      case ".fs":
      case ".f":
        return $this->generate_files();
      case ".images":
      case ".imgs":
      case ".img":
      case ".i":
        return $this->generate_images();
      default:
        return $this->generate_other();
    }
  }

  function generate_other() {
    $wikivars = &$this->wikivars;
    $wikivars["Sources"]["Page"] = ".dir other";
  }
  function get_dir_contents() {
    $subdir = $this->wiki->subdir;
    $storage = $this->wiki->storage;

    $pages = $storage->getglob($subdir."/*.".PAGE_EXT);
    $files = $storage->getglob($subdir."/*.*");
    $dirs = $storage->getglob($subdir."/*");
    $dirs = array_filter($dirs, function ($x) use($storage) { return $storage->isdir($x); } );
    $dirs = array_filter($dirs, function ($x) { return !preg_match('@^/+(js|css|bugs)$@',$x); } );
    $files = array_filter($files,function ($x) { return !preg_match('/\\.'.PAGE_EXT.'$/',$x); });
    $dirs = array_map(function($x) { return basename(trim($x,"/")); },$dirs);
    $dirs = array_filter($dirs,function($x) { return $x !== "" ; });
    $files = array_map(function($x) { return basename(trim($x,"/")); },$files);
    $pages = array_map(function($x) { return basename(trim($x,"/")); },$pages);
    $pages = array_filter($pages, function($x) { return preg_match('/^[^\.]+\.'.PAGE_EXT.'$/',$x); });
    $files = array_diff($files,$pages);
    $this->dirs = $dirs;
    $this->pages = $pages;
    $this->files = $files;
  }
  function generate_output() {
    $path = $this->wiki->path;
    $subdir = $this->wiki->subdir;
    $filename = $this->wiki->filename;
    $storage = $this->wiki->storage;
    $action = $this->wiki->action;
    $wikivars = &$this->wikivars;

    $pages = array_map(function($x) { $a = basename($x,".".PAGE_EXT); return "[[$a]]";; },$this->pages);
    $dirs = array_map(function($x) { return "[[$x]]"; },$this->dirs);
    $files = array_map(function($x) { return "[[$x]]"; },$this->files);
    $t = "";
    if( $filename === ".home" || $filename === "home" ) {
      $about_path = preg_replace("/\\.?home$/",".about",$path).".".PAGE_EXT;
      if( $storage->has($about_path) ) {
        $t = $storage->get($about_path)."\n\n";
      }
    }
    if( count($pages) > 0 ) {
      $t .= "## Pages\n";
      $t .= implode(" ",$pages);
      $t .= "\n\n";
    }
    if( count($dirs) > 0 ) {
      $t .= "## Subdirectories\n";
      $t .= implode(" ",$dirs);
      $t .= "\n\n";
    }
    if( count($files) > 0 ) {
      $t .= "## Files\n";
      $t .= implode(" ",$files);
      $t .= "\n\n";
    }
    $wikivars["Sources"]["Page"] = $t == "" ? $this->empty_dir_content() : $t;
  }
  function generate_dirs() {
    $this->get_dir_contents();
    $this->files = [];
    $this->pages = [];
    return $this->generate_output();
  }
  function generate_files() {
    $this->get_dir_contents();
    $this->dirs = [];
    $this->pages = [];
    return $this->generate_output();
  }
  function generate_pages() {
    $this->get_dir_contents();
    $this->dirs = [];
    $this->files = [];
    return $this->generate_output();
  }
  function generate_images() {
    $this->get_dir_contents();
    $this->dirs = [];
    $this->pages = [];
    $this->files = array_filter($this->files,function ($x) { return preg_match(IMAGE_REGEX,$x); });
    return $this->generate_output();
  }
  function empty_dir_content() {
    return "Directory ".$this->wiki->subdir." is empty.";
  }
}
