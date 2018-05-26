<?php
include "../config.secret.php";
include "show_error.php";

include "auth.php";
include "xcopy.php";
include "xrmdir.php";

function decode_json_body ($server, $request_body) {
  // Make sure that it is a POST request.
  if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0) {
    show_error('Request method must be POST!');
  }

  // Make sure that the content type of the POST request has been set to application/json
  $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

  if (strcasecmp($contentType, 'application/json') != 0) {
    show_error('Content type must be: application/json');
  }

  // Receive the RAW post data.
  $content = trim($request_body);

  // Attempt to decode the incoming RAW post data from JSON.
  $json_body = json_decode($content, true);

  // If json_decode failed, the JSON is invalid.
  if (!is_array($body)) {
    show_error('Received content contained invalid JSON!');
  }

  return $body;
}


	throw new Exception("Unable to use backdoor: Signature could not be verified.");
}

function accept_deploy ($server, $request_body) {
  $body = decode_json_body($server, $request_body);

  if (!auth($body, $CONFIG)) {
    header("HTTP/1.0 400 Bad Request");
    show_error('Access denied.');
  }

  $repo = $body['repo']; // like "aetkinz/mechtron.ca"
  $branch = $body['branch']; // like "develop" or "stable"
  $src_dir = $body['src_dir']; // like a "dist" folder
  $dest_dir = $body['dest_dir']; // like a "mechtron/a" folder

  if ($src_dir[0] == "." || $src_dir[0] == "/") {
    header("HTTP/1.0 400 Bad Request");
    show_error('Received a source directory outside the dev sandbox.');
  }

  if ($dest_dir[0] == "." || $dest_dir[0] == "/") {
    header("HTTP/1.0 400 Bad Request");
    show_error('Received a destination directory outside the dev sandbox.');
  }

  // Download source repo zip
  file_put_contents("temp_$repo.zip", fopen("https://github.com/$repo/archive/$branch.zip", 'r'));

  // Extract zip
  $zip = new ZipArchive;
  $res = $zip->open("temp_$repo.zip");
  if ($res) {
    $zip->extractTo('./temp_$repo');
    $zip->close();
  } else {
    show_error("Could not extract temp_$repo.zip");
  }

  // Copy files
  if (!xcopy("./temp_$repo/$src_dir", "./$dest_dir")) {
    show_error("Failed to copy contents from ./temp_$repo/$src_dir to ./$dest_dir");
  }

  // Cleanup
  xrmdir("./temp_$repo");
  unlink("./temp_$repo.zip");

  // Return success response
  echo json_encode(array(
    'status' => 'success',
    'repo' => $repo,
    'branch' => $branch,
  ));
}
?>
