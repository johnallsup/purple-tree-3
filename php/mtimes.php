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

function fmt_time($time) {
  if( $time === 0 ) {
    return [ null, null, null ];
  }
  if( $time === "null" || is_null($time) ) {
    return [ null, null, null ];
  }
  $now = time();
  $d = new DateTime('@'.$time);
  $dt = $now - $time;
  $mtime_fmt_short = $d->format('m-d H:i:s');
  if( $dt < 24*60*60 ) {
    $h = intval( $dt / 3600 );
    $m = intval( ($dt % 3600) / 60);
    $s = $dt % 60;
    $mt = "";
    if( $h > 0 ) {
      $mt .= $h."h ";
    }
    if( $m > 0 ) {
      $mt .= $m."m ";
    }
    if( $s > 0 ) {
      $mt .= $s."s ";
    }
    if( $mt === "" ) {
      $mt = "right now";
    } else {
      $mt .= "ago";
    }
    $mtime_fmt_short_ago = $mt;
    $mtime_fmt_ago = $mt;
  } else {
    $days = intval($dt / (24*60*60));
    $mtime_fmt_short_ago = $mtime_fmt_short;
    $mtime_fmt_ago = "$days day".($days == 1 ? "" : "s")." ago";
  }
  $mtime_fmt_long = $d->format('l Y-m-d H:i:s');
  return [ $mtime_fmt_long, $mtime_fmt_short, $mtime_fmt_short_ago, $mtime_fmt_ago ];
}
function fmt_pagemtime($pagemtime) { 
  global $wiki;
  global $mtime_fmt_long, $mtime_fmt_short, $mtime_fmt_short_ago;
  [ $mtime_fmt_long, $mtime_fmt_short, $mtime_fmt_short_ago ] = fmt_time($pagemtime);
  if( $mtime_fmt_long === null ) {
    $mtime_fmt_long = "Page '<span class='pagename'>".$wiki->pagename."</span>' does not exist.";
    $mtime_fmt_short = "does not exist";
    $mtime_fmt_ago = "does not exist";
  }
}
