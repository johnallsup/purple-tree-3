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
if( !is_auth("edit") ) {
  serve_error_json("accessdenied","Access denied trying to preview",401);
}
if( !isset($postdata["path"]) ) {
  serve_error_json("invalidstore","No path provided for preview",400);
}
if( !isset($postdata["source"]) ) {
  serve_error_json("invalidstore","No source provided for preview",400);
}

require_once("utils.php");
require_once("ptmd.php");
require_once("page_source.php");

$path = $postdata["path"];
$source = $postdata["source"];

$page_source_parsed = new PageSource($source);
$meta = $page_source_parsed->meta;
$options = $page_source_parsed->options;
$tags = $page_source_parsed->tags;

$ptmd = new PTMD($wiki);
$page_rendered = $ptmd->render($source,$options);
$uses = $ptmd->uses;

$response_data = [ 
  "path" => $path,
  "source" => $source,
  "rendered" => $page_rendered,
  "uses" => $uses,
  "debug_received" => $postdata 
];
serve_json($response_data,200);

