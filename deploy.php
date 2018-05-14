<?
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$expected_key = "FxWf7diXWfcp=nVEbC6jlNT^D;9;EqyLcZQYty]=aCJ:X<6CU;ttSACm<=";

function xcopy ($source, $dest, $permissions = 0755)
{
  // Simple copy for a file
  if (is_file($source)) {
    return copy($source, $dest);
  }

  // Make destination directory
  if (!is_dir($dest)) {
    mkdir($dest, $permissions);
  }

  // Loop through the folder
  $dir = dir($source);
  while (false !== $entry = $dir->read()) {
    // Skip pointers
    if ($entry == '.' || $entry == '..') {
      continue;
    }

    // Deep copy directories
    xcopy("$source/$entry", "$dest/$entry", $permissions);
  }

  // Clean up
  $dir->close();
  return true;
}

// recursively
function xrmdir ($dir) {
  $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
  $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

  foreach ($files as $file) {
    if ($file->isDir()) {
      rmdir($file->getRealPath());
    } else {
      unlink($file->getRealPath());
    }
  }

  rmdir($dir);
}

function show_error ($msg) {
  echo json_encode(array(
    'status' => 'fail',
    'message' => $msg,
  ));
  die(0);
}

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
$content = trim(file_get_contents("php://input"));

// Attempt to decode the incoming RAW post data from JSON.
$body = json_decode($content, true);

// If json_decode failed, the JSON is invalid.
if (!is_array($body)) {
  show_error('Received content contained invalid JSON!');
}

if ($body['key'] !== $key) {
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
file_put_contents("temp_{$repo}.zip", fopen("https://github.com/{$repo}/archive/{$branch}.zip", 'r'));

// Extract zip
$zip = new ZipArchive;
$res = $zip->open("temp_{$repo}.zip");
if ($res) {
  $zip->extractTo('./temp_{$repo}');
  $zip->close();
} else {
  show_error("Could not extract temp_{$repo}.zip");
}

// Copy files
if (!xcopy("./temp_{$repo}/{$src_dir}", "./{$dest_dir}")) {
  show_error("Failed to copy contents from ./temp_{$repo}/{$src_dir} to ./{$dest_dir}");
}

// Cleanup
xrmdir("./temp_{$repo}");
unlink("./temp_{$repo}.zip");

// Return success response
echo json_encode(array(
  'status' => 'success',
));
?>
