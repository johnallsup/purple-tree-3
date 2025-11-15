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
if( ! isset($postdata["path"]) ) {
  serve_error_json("nopath","No path specified",400);
}
$path = $postdata["path"].".".PAGE_EXT;
$storage = $wiki->storage;
$source = $storage->has($path) ? $storage->get($path) : null;
$response_data = [ "path" => $path, "source" => $source, "debug_received" => $postdata ];
serve_json($response_data,200);
