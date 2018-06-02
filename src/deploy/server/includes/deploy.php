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

  $name = basename($body['name']); // like "mechtron"
  $repo = $body['repo']; // like "aetkinz/mechtron.ca"
  $branch = $body['branch']; // like "develop" or "stable"
  $src_dir = $body['src_dir']; // like a "dist" folder
  $dest_dir = $body['dest_dir']; // like a "mechtron/a" folder

  if (!$name) {
    show_error('Missing name POST param.');
  }

  if (!$src_dir) {
    show_error('Missing src_dir POST param.');
  }

  if (!$dest_dir) {
    show_error('Missing dest_dir POST param.');
  }

  if ($src_dir[0] == "." || $src_dir[0] == "/") {
    show_error('Received a source directory outside the dev sandbox.');
  }

  if ($dest_dir[0] == "." || $dest_dir[0] == "/") {
    show_error('Received a destination directory outside the dev sandbox.');
  }

  $target = "targets/$name.enabled.php";

  if (file_exists($target)) {
    include $target;
  }
  else {
    show_error("Target folder \"$target\" either does not exist or is not enabled.");
  }

  $temp_file = "repos/temp_" . str_replace("/", "_", $repo) . ".zip";
  $source = "./repos/" . basename($repo) . "-$branch";
  $dest = $config['ROOT_DIR'] . "/$dest_dir";

  // add some keys to the body to pass to our hooks
  $body['source'] = $source;
  $body['dest'] = $dest;

  // call before deploy hook if defined for the target
  if (function_exists("before_deploy")) {
    before_deploy($body);
  }

  // Download source repo zip
  file_put_contents($temp_file, fopen("https://github.com/$repo/archive/$branch.zip", 'r'));

  if (!file_exists($temp_file)) {
    show_error("Could not download $temp_file");
  }

  // Extract zip
  $zip = new ZipArchive;
  $res = $zip->open($temp_file);
  if ($res) {
    $zip->extractTo("./repos");
    $zip->close();
  } else {
    show_error("Could not extract $temp_file");
  }

  // Copy files
  if (!xcopy("$source/$src_dir", $dest)) {
    show_error("Failed to copy contents from $source/$src_dir to $dest");
  }

  // Cleanup
  xrmdir($source);
  unlink($temp_file);

  // call after deploy hook if setup for server target
  if (function_exists("before_deploy")) {
    after_deploy($body);
  }

  // Return success response
  echo json_encode(array(
    'status' => 'success',
    'repo' => $repo,
    'branch' => $branch,
  ));
}
?>
