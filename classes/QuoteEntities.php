<?php
/*
* Source File: QuoteEntries.php
* Create Date: 08/31/2015 11:00
* Last Updated: 08/31/2015 11:00
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

require_once "classes/Quote.php";

/**
 * Class for encapsulating a quote objects
 */
  class QuoteEntities {
  
    /**
    * @static
    * Strips out excess whitespace and line-breaks from Xml data.
    * @param string $value The string to clean.
    * @returns string
    */
    public static function CleanXmlString($value)
    {
      $value = preg_replace(array('/\r/', '/\n/'), '', $value);
      $value = ltrim($value);
      $value = rtrim($value);
      return $value;
    }
  
    /**
    * @public
    * Retrieves a collection of Quote objects from the datasource
    * @param string $author An optional author to filter on
    * @return array 
    */
    public function Get($author='', $limit=1, $sortBy='', $sortOrder='asc') {
      $quotes = $this->Find($author, '', $sortBy, $sortOrder);
      $quotes = array_slice($quotes, 0, $limit);
      return $quotes;
    }

    public function Find($author='', $search='', $sortBy='', $sortOrder='asc') {
      $quotes = $this->GetAllQuotes($author);

      if ($search != '') {
        $quotes = array_values(array_filter($quotes, function($quote) use ($search) {
          return stripos($quote->Value, $search) !== false;
        }));
      }

      if ($sortBy == 'author' || $sortBy == 'date') {
        usort($quotes, function($first, $second) use ($sortBy, $sortOrder) {
          $firstValue = $sortBy == 'author' ? $first->Author : $first->Added;
          $secondValue = $sortBy == 'author' ? $second->Author : $second->Added;
          $comparison = strcasecmp($firstValue, $secondValue);
          return $sortOrder == 'desc' ? -$comparison : $comparison;
        });
      }

      return $quotes;
    }

    public function GetAuthors() {
      $authors = array_map(function($quote) { return $quote->Author; }, $this->GetAllQuotes());
      $authors = array_values(array_unique($authors));
      natcasesort($authors);
      return array_values($authors);
    }
    
    /**
    * @public
    * Retrieves a collection of Quote objects from the datasource
    * @param string $author An optional author to filter on
    * @return array 
    */
    public function GetRandom($author='', $limit=1) {
      $quotes = $this->getAllQuotes($author);
      shuffle($quotes);
      
      $quotes = array_slice($quotes, 0, $limit);
      return $quotes;
    }
    
    /**
    * @protected
    * Retrieves a collection of Quote objects from the datasource
    * @param string $author An optional author to filter on
    * @return array 
    */
    protected function GetAllQuotes($author='') {
      $quotes = array();
      $doc = new DOMDocument();
      $doc->load(__DIR__ . '/../data/quotes.xml');
      $destinations = $doc->getElementsByTagName('quote');
      foreach ($destinations as $xmQuote) {       
        if ($author != '') {
          if (stripos($xmQuote->getAttribute('author'), $author) === false) {
            continue;          
          }
        }
      
        $quote = new Quote();
        $quote->Added = $xmQuote->getAttribute('added');
        $quote->Author = $xmQuote->getAttribute('author');
        // Strip out extra linebreaks
        $nodeValue = $this->CleanXmlString($xmQuote->nodeValue);
        $quote->Value = $nodeValue;
        $quotes[] = $quote;
     }
      
      return $quotes;
    }
    
    /**
    * @public
    * Insert a new quote into the datastore.
    * @param string $author The quote author.
    * @param string $value The quote text.
    */
    public function InsertQuote($author, $value) {
     $date_rfc = date("Y-m-d H:i");
     $dataPath = __DIR__ . '/../data/quotes.xml';
     $handle = fopen($dataPath, 'c+');
     if ($handle === false || !flock($handle, LOCK_EX)) {
       if ($handle !== false) fclose($handle);
       throw new RuntimeException('The quote database could not be locked.');
     }

     $xml = stream_get_contents($handle);
     $doc = new DOMDocument();
     $doc->loadXML($xml);
     $doc->formatOutput = true;

     foreach ($doc->getElementsByTagName('quote') as $existingQuote) {
       $sameAuthor = strcasecmp(trim($existingQuote->getAttribute('author')), trim($author)) == 0;
       $sameValue = strcasecmp($this->CleanXmlString($existingQuote->nodeValue), trim($value)) == 0;
       if ($sameAuthor && $sameValue) {
         flock($handle, LOCK_UN);
         fclose($handle);
         return false;
       }
     }
     
     // Get the root element "quotes"
     $root = $doc->documentElement;
     
     // Create new quote element
     $xmQuote = $doc->createElement("quote");
     $xmQuote->appendChild($doc->createCDATASection("$value"));
     
     // Create the attributes
     $auth_attr = $doc->createAttribute('author');
     $auth_attr->value = $author;
     $date_attr = $doc->createAttribute('added');
     $date_attr->value = $date_rfc;
     
     // Append attribs to element
     $xmQuote->appendChild($date_attr);
     $xmQuote->appendChild($auth_attr);
     
     // Append element into quotes
     $root->appendChild($xmQuote);
     
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, $doc->saveXML());
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return true;
    }
  }

?>