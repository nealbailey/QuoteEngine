<?php
/*
* Source File: submit.php
* Create Date: 09/15/2015 10:19
* Last Updated: 09/15/2015 10:19
* Author: Neal T. Bailey <nealbailey@hotmail.com>
*
* ----------------------------------------------------------------------
* GNU GENERAL PUBLIC LICENSE
* ----------------------------------------------------------------------
* Version 2, June 1991 
* Copyright (C) 1989, 1991 Free Software Foundation, Inc.  
* 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*
* Everyone is permitted to copy and distribute verbatim copies
* of this license document, but changing it is not allowed.
*
* https://www.gnu.org/licenses/gpl-2.0.html
*-----------------------------------------------------------------------
* Copyright (c) 2010-2015 Baileysoft Solutions
*-----------------------------------------------------------------------
*/

require_once "classes/ApiRequest.php";
require_once "classes/QuoteEntities.php";

$apiRequest = new ApiRequest();
$quoteModel = new QuoteEntities();

header('Content-Type: application/json; charset=utf-8');

if (!$apiRequest->IsPostBack) {
  http_response_code(405);
  echo json_encode(array('success' => false, 'message' => 'POST is required.'));
  exit;
}

if (empty($apiRequest->Author) || empty($apiRequest->Quote)) {
  http_response_code(422);
  echo json_encode(array('success' => false, 'message' => 'Author and quote text are required.'));
  exit;
}

try {
  if (!$quoteModel->InsertQuote($apiRequest->Author, $apiRequest->Quote)) {
    http_response_code(409);
    echo json_encode(array('success' => false, 'message' => 'That quote already exists.'));
    exit;
  }

  http_response_code(201);
  echo json_encode(array('success' => true, 'message' => 'Quote added to the collection.'));
} catch (RuntimeException $exception) {
  http_response_code(500);
  echo json_encode(array('success' => false, 'message' => $exception->getMessage()));
}