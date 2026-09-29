<?php
/*
* Source File: ApiRequest.php
* Create Date: 08/31/2015 13:33
* Last Updated: 08/31/2015 13:33
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
    * @returns string
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
      $this->QueryString = $_SERVER['QUERY_STRING'];
      
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