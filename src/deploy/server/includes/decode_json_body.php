<?php
function decode_json_body ($server, $request_body) {
  // Make sure that it is a POST request.
  if (strcasecmp($server['REQUEST_METHOD'], 'POST') != 0) {
    show_error('Request method must be POST!');
  }

  // Make sure that the content type of the POST request has been set to application/json
  $contentType = isset($server["CONTENT_TYPE"]) ? trim($server["CONTENT_TYPE"]) : '';

  if (strcasecmp($contentType, 'application/json') != 0) {
    show_error('Content type must be: application/json');
  }

  // Receive the RAW post data.
  $content = trim($request_body);

  // Attempt to decode the incoming RAW post data from JSON.
  $json_body = json_decode($content, true);

  // If json_decode failed, the JSON is invalid.
  if (!is_array($json_body)) {
    show_error('Received content contained invalid JSON!');
  }

  return $json_body;
}
?>
