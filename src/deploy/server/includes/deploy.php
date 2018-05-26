<?php
include "show_error.php";
include "decode_json_body.php";
include "auth.php";
include "xcopy.php";
include "xrmdir.php";

function accept_deploy ($server, $request_body, $config) {
  $body = decode_json_body($server, $request_body);

  if (!auth($body, $config)) {
    show_error('Access denied.');
  }

  $repo = $body['repo']; // like "aetkinz/mechtron.ca"
  $branch = $body['branch']; // like "develop" or "stable"
  $src_dir = $body['src_dir']; // like a "dist" folder
  $dest_dir = $body['dest_dir']; // like a "mechtron/a" folder

  if ($src_dir[0] == "." || $src_dir[0] == "/") {
    show_error('Received a source directory outside the dev sandbox.');
  }

  if ($dest_dir[0] == "." || $dest_dir[0] == "/") {
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
