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
function truthy(string $x, bool $default=false ) : bool {
  $truthy = array( 
    "1" => true, "0" => false,
    "true" => true, "false" => false, 
    "yes" => true, "no" => false);
  $x = strtolower($x);
  if( array_key_exists($x,$truthy) ) {
    return $truthy[$x];
  }
  if( is_bool($x) ) { return $x; }
  if( is_int($x) ) { return $x; }
  return $default;
}
  
