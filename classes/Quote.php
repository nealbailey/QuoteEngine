<?php
/*
* Source File: Quote.php
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

 /**
 * Class for encapsulating a quote object
 */
  class Quote {
    public $Added;
    public $Author;
    public $Value;
    
    /**
    * Default Constructor
    */
    function __construct()
    {
      $date_rfc = date(DATE_RFC2822);
      $this->Added = $date_rfc;
    }
  }

?>