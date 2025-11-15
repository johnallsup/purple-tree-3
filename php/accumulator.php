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

class Accumulator extends stdclass {
  function __construct($wiki) {
    $this->wiki = $wiki;
    $this->xs = [];
  }
  function add($x) {
    array_push($this->xs,$x);
  }
  function join($y) {
    return implode($y,$this->xs);
  }
  function addscr($x,$opts=null) {
    $opts = is_null($opts) ? "": " $opts";
    $this->add("<script$opts>\n$x\n</script>");
  }
  function addscs($x,$opts=null) {
    $opts = is_null($opts) ? "": " $opts";
    if( INLINE ) {
      $storage = $this->wiki->storage;
      if( is_file($fn = STATIC_DIR.$x) ) {
        $this->addscr(file_get_contents($fn));
      } else if( $storage->has($x) ) {
        $this->addscr($storage->get($x));
      } else {
        $this->add("<!-- Missing script: $x == $fn in ".STATIC_DIR." -->");
      }
    } else {
      $this->add("<script src='$x'$opts></script>");
    }
  }
  function addscsni($x,$opts=null) {
    # This is for scripts that are never inlined, e.g. mathjax or abc
    $opts = is_null($opts) ? "": " $opts";
    $this->add("<script src='$x'$opts></script>");
  }
  function addscrs($xs,$opts=null) {
    foreach($xs as $x) {
      $this->addscs($x,$opts);
    }
  }
  function addsty($x,$media=null) {
    $m = is_null($media) ? "" : "media='$media' ";
    $this->add("<style{$m}>\n$x\n</style>");
  }
  function addsts($x,$media=null) {
    $m = is_null($media) ? "" : "media='$media' ";
    if( INLINE ) {
      $storage = $this->wiki->storage;
      if( is_file($fn=STATIC_DIR.$x) ) {
        $this->addsty(file_get_contents($fn));
      } else if( $storage->has($x) ) {
        $this->addsty($storage->get($x));
      } else {
        $this->add("<!-- Missing style: $x -->");
      }
    } else {
      $this->add("<link rel='stylesheet' {$m}href='$x'/>");
    }
  }
  function addstsni($x,$media=null) {
    $m = is_null($media) ? "" : "media='$media' ";
    # never inline
    $this->add("<link rel='stylesheet' {$m}href='$x'/>");
  }
  function addstss($xs,$media=null) {
    foreach($xs as $x) {
      $this->addsts($x,$media);
    }
  }
}
