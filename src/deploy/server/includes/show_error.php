<?php
function show_error ($msg) {
  echo json_encode(array(
    'status' => 'fail',
    'message' => $msg,
  ));
  die(0);
}
?>
