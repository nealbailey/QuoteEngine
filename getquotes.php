<?php
/*
* Source File: getquotes.php
* Create Date: 08/31/2015 11:07
* Last Updated: 08/31/2015 11:07
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
require_once "classes/Quote.php";
require_once "classes/QuoteEntities.php";

$apiRequest = new ApiRequest();
$quoteModel = new QuoteEntities();

//$quoteModel->InsertQuote('Neal Bailey','Quotes are for bitches');
//exit;

if ($apiRequest->SortBy == 'random')
  $quotes = $quoteModel->GetRandom($apiRequest->Author, $apiRequest->Limit);
else if ($apiRequest->Format == 'json') {
  $allQuotes = $quoteModel->Find(
    $apiRequest->Author,
    $apiRequest->Search,
    $apiRequest->SortBy,
    $apiRequest->SortOrder
  );
  $total = count($allQuotes);
  $offset = ($apiRequest->Page - 1) * $apiRequest->Limit;
  $quotes = array_slice($allQuotes, $offset, $apiRequest->Limit);
} else
  $quotes = $quoteModel->Get(
    $apiRequest->Author,
    $apiRequest->Limit,
    $apiRequest->SortBy,
    $apiRequest->SortOrder
  );

if ($apiRequest->Format == 'json') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array(
    'quotes' => $quotes,
    'authors' => $quoteModel->GetAuthors(),
    'page' => $apiRequest->Page,
    'pageSize' => $apiRequest->Limit,
    'total' => isset($total) ? $total : count($quotes),
    'totalPages' => isset($total) ? max(1, (int) ceil($total / $apiRequest->Limit)) : 1
  ));
  exit;
}

header('Content-Type: text/plain; charset=utf-8', true);

$str = '';
foreach($quotes as $quote) {
  $str .= "$quote->Value --$quote->Author\r\n";
}

echo $str;
?>