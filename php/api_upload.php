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
  serve_error_json("accessdenied","Access denied trying to upload",401);
}

function make_error($filename,$error) {
  return array("filename" => $filename, "result"=> "error", "error"=>$error);
}

$storage = $wiki->storage;
$successes = 0;
$failures = 0;
$files_results = array();
if( ! array_key_exists('location',$_POST) ) {
  serve_error_json("invalidupload","No location provided",400);
}
$location = parse_url($_POST['location']);
$path = urldecode($location['path']);
$subdir = dirname($path);

foreach ($_FILES['file']['name'] as $key=>$val) {
  $filename = $_FILES['file']['name'][$key];
  $filename = preg_replace('/[^a-zA-Z0-9_\-@\. ]+/',"_",$filename);
  $tmp_name = $_FILES['file']['tmp_name'][$key];
  $subdirt = trim($subdir,"/");
  if( $subdirt === "" ) {
    $target = $filename;
  } else {
    $target = $subdirt."/".$filename;
  }
  $wiki->log("Target: $target -- $subdir -- $filename -- $tmp_name");
  if( ! $wiki->valid_file_path($target) ) {
    array_push($files_results,make_error($filename,"Filename '$filename' is not acceptable"));
    $failures++;
  } else {
    // filename ok
    try {
      $storage->store_uploaded($tmp_name,$target);
      array_push($files_results,array("filename"=>$filename,"result"=>"success","error"=>null));
      $successes++;
    } catch( Exception $e ) {
      array_push($files_results,make_error($filename,"Failed to move '$filename' -- ".$e->getMessage()));
      $failures++;
    }
  }
}
$return_obj = array(
  "error"=> ($successes === 0 ? "No files uploaded" : null),
  "result"=> ($successes > 0 && $failures === 0 ? "success" : ($successes > 0 && $failures > 0 ? "partial" : "error" )),
  "files"=> $files_results
);
$json = json_encode($return_obj);
echo $json;
