<?php
/*
* Source File: ApiRequest.php
* Create Date: 08/31/2015 13:33
* Last Updated: 09/30/2026 11:16
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

 /**
 * Class for encapsulating query string api parameters
 * @property string $Author The author of the quote being requested.
 * @property string $QueryString The raw query string from the request.
 * @property string $Quote The text of the quote being requested.
 * @property string $Search The search term used to filter quotes.
 * @property string $SortBy The field to sort by ('author', 'date', or 'random').
 * @property string $SortOrder The sort order ('asc' or 'desc').
 * @property string $Format The response format ('text' or 'json').
 * @property int $Page The page number for paginated results.
 * @property int $Limit The maximum number of quotes to return.
 * @property bool $IsPostBack Indicates if the request is a POST request.
 * @property string $OriginalAdded The original added date of the quote being edited.
 * @property string $OriginalAuthor The original author of the quote being edited.
 */
  class ApiRequest {
    public $Author;
    public $QueryString;
    public $Quote;
    public $Search;
    public $SortBy;
    public $SortOrder;
    public $Format;
    public $Page;
    public $Limit;
    public $IsPostBack;
    public $OriginalAdded;
    public $OriginalAuthor;

    /**
    * Default Constructor
    */
    function __construct()
    {
      $this->Limit = 1;
      $this->Page = 1;
      $this->Format = 'text';
      $this->SortOrder = 'asc';
      $this->IsPostBack = false;
      $this->Process();
    }
    
    /**
    * @static
    * Performs inout validation to prevent known injection attacks.
    * @param string $value The user provided string to clean
    * @returns string The sanitized string.
    */
    public static function SanitizeString($value)
    {
      return trim(strip_tags((string) $value));
    }
    
    /**
    * Populates this object based on the _GET request query string.
    */
    function Process()
    {
      $this->QueryString = $_SERVER['QUERY_STRING'] ?? '';
      
      if (!empty($_POST)) {
        $this->IsPostBack = true;
      }
      if (isset($_REQUEST['author'])) {
        $this->Author = ApiRequest::SanitizeString($_REQUEST['author']);
      }
      if (isset($_REQUEST['limit'])) {
        $this->Limit = max(1, min(100, (int) $_REQUEST['limit']));
      }
      if (isset($_REQUEST['page'])) {
        $this->Page = max(1, (int) $_REQUEST['page']);
      }
      if (isset($_REQUEST['quote'])) {
        $this->Quote = ApiRequest::SanitizeString($_REQUEST['quote']);
      }
      if (isset($_POST['original_added'], $_POST['original_author'])) {
        $this->OriginalAdded = ApiRequest::SanitizeString($_POST['original_added']);
        $this->OriginalAuthor = ApiRequest::SanitizeString($_POST['original_author']);
      }
      if (isset($_REQUEST['search'])) {
        $this->Search = ApiRequest::SanitizeString($_REQUEST['search']);
      }
      if (isset($_REQUEST['format']) && strtolower($_REQUEST['format']) == 'json') {
        $this->Format = 'json';
      }
      if (isset($_REQUEST['sortby'])) {
        $sortBy = strtolower(ApiRequest::SanitizeString($_REQUEST['sortby']));
        if (in_array($sortBy, array('author', 'date', 'random'), true)) {
          $this->SortBy = $sortBy;
        }
      }
      if (isset($_REQUEST['sortorder'])) {
        $sortOrder = strtolower(ApiRequest::SanitizeString($_REQUEST['sortorder']));
        if (in_array($sortOrder, array('asc', 'desc'), true)) {
          $this->SortOrder = $sortOrder;
        }
      }
    }  
  }

?>