<?php
/*
* Source File:  QuoteEntities.php
* Create Date:  08/31/2015 11:00
* Last Updated: 09/30/2026 11:16
* Author:       Neal T. Bailey <nealbailey@hotmail.com>
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

    /**
     * Finds quotes based on the specified criteria.
     * @param string $author An optional author to filter on
     * @param string $search An optional search term to filter quotes by their content
     * @param string $sortBy The field to sort by ('author' or 'date')
     * @param string $sortOrder The sort order ('asc' or 'desc')
     * @return array A collection of Quote objects matching the criteria
     */
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

    /**
    * @public
    * Retrieves a collection of unique authors from the datasource.
    * @return array A collection of unique author names.
    */
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
    * @return array A collection of random Quote objects matching the criteria.
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
    * @return array A collection of all Quote objects matching the criteria.
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
    * @return Quote The stored quote.
    * @throws QuoteConflictException When the quote or its key already exists.
    */
    public function InsertQuote($author, $value) {
      return $this->SaveQuote($author, $value);
    }

    /**
    * @public
    * Replace the quote identified by its added+author key with a new quote stamped with the current time.
    * @param string $originalAdded The added attribute of the quote being edited.
    * @param string $originalAuthor The author attribute of the quote being edited.
    * @param string $author The new quote author.
    * @param string $value The new quote text.
    * @return Quote The stored quote.
    * @throws QuoteNotFoundException When the original quote no longer exists.
    * @throws QuoteConflictException When the new quote or its key already exists.
    */
    public function UpdateQuote($originalAdded, $originalAuthor, $author, $value) {
      return $this->SaveQuote($author, $value, $originalAdded, $originalAuthor);
    }

    /**
    * @protected
    * Saves a quote to the datastore, either inserting a new one or updating an existing one.
    * @param string $author The quote author.
    * @param string $value The quote text.
    * @param string|null $originalAdded The added attribute of the quote being edited, or null for new quotes.
    * @param string|null $originalAuthor The author attribute of the quote being edited, or null for new quotes.
    * @return Quote The stored quote.
    * @throws QuoteNotFoundException When the original quote no longer exists.
    * @throws QuoteConflictException When the new quote or its key already exists.
    */
    protected function SaveQuote($author, $value, $originalAdded = null, $originalAuthor = null) {
      $added = date("Y-m-d H:i:s");
      $handle = fopen(__DIR__ . '/../data/quotes.xml', 'c+');
      if ($handle === false || !flock($handle, LOCK_EX)) {
        if ($handle !== false) fclose($handle);
        throw new RuntimeException('The quote database could not be locked.');
      }

      try {
        $doc = new DOMDocument();
        if (!$doc->loadXML(stream_get_contents($handle))) {
          throw new RuntimeException('The quote database could not be read.');
        }

        $target = null;
        foreach ($doc->getElementsByTagName('quote') as $existingQuote) {
          $existingAdded = $existingQuote->getAttribute('added');
          $existingAuthor = $existingQuote->getAttribute('author');
          if ($originalAdded !== null && $existingAdded === $originalAdded && $existingAuthor === $originalAuthor) {
            $target = $existingQuote;
            continue;
          }

          $sameAuthor = strcasecmp(trim($existingAuthor), trim($author)) == 0;
          if ($sameAuthor && strcasecmp($this->CleanXmlString($existingQuote->nodeValue), trim($value)) == 0) {
            throw new QuoteConflictException('That quote already exists.');
          }
          if ($sameAuthor && $existingAdded === $added) {
            throw new QuoteConflictException('This author already has a quote saved at this exact time. Please try again.');
          }
        }

        if ($originalAdded !== null && $target === null) {
          throw new QuoteNotFoundException('The quote being edited no longer exists.');
        }

        $root = $doc->documentElement;
        if ($target !== null) {
          $root->removeChild($target);
        }

        $xmQuote = $doc->createElement('quote');
        $xmQuote->setAttribute('added', $added);
        $xmQuote->setAttribute('author', $author);
        $xmQuote->appendChild($doc->createCDATASection($value));
        $root->appendChild($xmQuote);

        $xml = $this->Serialize($doc);
        rewind($handle);
        ftruncate($handle, 0);
        if (fwrite($handle, $xml) !== strlen($xml)) {
          throw new RuntimeException('The quote database could not be written.');
        }
        fflush($handle);
      } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
      }

      $quote = new Quote();
      $quote->Added = $added;
      $quote->Author = $author;
      $quote->Value = $this->CleanXmlString($value);
      return $quote;
    }

    /**
    * Writes the document with one indented element per line so administrators can hand-edit the file.
    * @param DOMDocument $doc The DOMDocument to serialize
    * @return string The serialized XML string
    */
    protected function Serialize(DOMDocument $doc) {
      $root = $doc->documentElement;
      $xml = "<?xml version=\"1.0\"?>\n<" . $root->nodeName;
      foreach ($root->attributes as $attribute) {
        $xml .= ' ' . $attribute->name . '="' . htmlspecialchars($attribute->value, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
      }
      $xml .= ">\n";

      foreach ($root->getElementsByTagName('quote') as $quote) {
        $added = htmlspecialchars($quote->getAttribute('added'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $author = htmlspecialchars($quote->getAttribute('author'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        // "]]>" cannot appear inside a CDATA section, so split it across two sections.
        $value = str_replace(']]>', ']]]]><![CDATA[>', $this->CleanXmlString($quote->nodeValue));
        $xml .= "    <quote added=\"$added\" author=\"$author\">\n";
        $xml .= "        <![CDATA[$value]]>\n";
        $xml .= "    </quote>\n";
      }

      return $xml . '</' . $root->nodeName . ">\n";
    }
  }

  class QuoteConflictException extends RuntimeException {}
  class QuoteNotFoundException extends RuntimeException {}

?>