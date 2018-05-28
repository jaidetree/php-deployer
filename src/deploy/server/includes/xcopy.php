<?php

function xcopy ($source, $dest, $permissions = 0755)
{
  // Simple copy for a file
  if (is_file($source)) {
    return copy($source, $dest);
  }

  $dest_dir = dirname($dest);

  // Ensure dest directory exists
  if (!is_dir($dest_dir)) {
    mkdir($dest_dir, $permissions, true);
  }

  // Make destination directory
  if (!is_dir($dest)) {
    mkdir($dest, $permissions, true);
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

?>
