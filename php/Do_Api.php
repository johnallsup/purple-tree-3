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
/**
 * API
 *
 * upload file
 * preview page -- take source and run through PTMD.
 * store page
 * fetch page source
 *
 * fetching rendered page is harder as there are things like optional
 * scripts and styles that we can't easily unload.
 */
require_once("cors.php");
require_once("defs.php");
require_once(CONFIG_DIR."/auth.php");
require_once("wiki.php");
require_once("versioned_storage.php");

// We need a Wiki object to pass to PTMD if rendering a preview.
// In such circumstances, we take the path from the JSON
$wiki = new Wiki();

$url = $_SERVER['REQUEST_URI'];
$url = explode("?",$url,2)[0];
$url = preg_replace("@^/+@","",$url);

$storage = new VersionedStorage(FILES_DIR,VERSIONS_DIR);

$wiki->url = $url;
$wiki->storage = $storage;

function serve_error_json($type,$message,$response_code,$additional_data = null) {
  $message = str_replace("\\","\\\\",$message);
  $message = str_replace('"',"\\\"",$message);
  $data = [ "status" => "error", "errorType" => $type, "error" => $message ];
  if( ! is_null($additional_data) ) {
    $data = array_merge($data,$additional_data);
  }
  serve_json($data, $response_code);
}
function serve_json($data, $response_code) {
  http_response_code($response_code);
  header("Content-type: application/json");
  echo json_encode($data);
  exit();
}
function access_denied_json() {
  serve_error_json("accessdenied","Access denied",401);
}
if( !is_auth("view") ) {
  access_denied_json();
  exit();
}
function must_post($endpoint, $response_code = 400) {
  if( $_SERVER["REQUEST_METHOD"] !== "POST" ) {
    serve_error_json("mustpost","Must POST for $endpoint",400);
    exit();
  }
}

function get_json_from_post() {
  $body = file_get_contents('php://input');
  try {
    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
  } catch( Exception $e ) {
    serve_error_json("invalidjson","Invalid request JSON",400,["invalidJson" => $body]);
  }
}

function endpoint_upload($wiki) {
  must_post("upload");
  require("api_upload.php");
}
function endpoint_preview($wiki) {
  must_post("preview");
  $postdata = get_json_from_post();
  require("api_preview.php");
}
function endpoint_store($wiki) {
  must_post("store");
  $postdata = get_json_from_post();
  require("api_store.php");
}
function endpoint_source($wiki) {
  must_post("source");
  $postdata = get_json_from_post();
  require("api_source.php");
}
function endpoint_mtime($wiki) {
  must_post("mtime");
  $postdata = get_json_from_post();
  require("api_mtime.php");
}
function endpoint_versions($wiki) {
  must_post("versions");
  $postdata = get_json_from_post();
  require("api_versions.php");
}
function endpoint_dir($wiki) {
  must_post("dir");
  $postdata = get_json_from_post();
  require("api_dir.php");
}
function endpoint_ddir($wiki) {
  must_post("ddir");
  $postdata = get_json_from_post();
  require("api_ddir.php");
}


if( ! preg_match('@\.api/([a-z]+)(/|$)@',$url,$m) ) {
  http_response_code(400);
  echo "Invalid URL: $url";
  exit();
}
$endpoint = $m[1];
$endpoint_fn = "endpoint_".$endpoint;

if( ! function_exists($endpoint_fn) ) {
  http_response_code(400);
  echo "Invalid endpoint: $endpoint";
  exit();
}
$endpoint_fn($wiki);

