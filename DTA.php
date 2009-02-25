<?php
/**
 * DTA
 *
 * DTA is a class that provides functions to create DTA files used in
 * Germany to exchange informations about money transactions with banks
 * or online banking programs.
 *
 * PHP versions 4 and 5
 *
 * This LICENSE is in the BSD license style.
 *
 * Copyright (c) 2003-2005 Hermann Stainer, Web-Gear
 * http://www.web-gear.com/
 * Copyright (c) 2008 Martin Schütte
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * Neither the name of Hermann Stainer, Web-Gear nor the names of his
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE
 * REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Payment
 * @package   Payment_DTA
 * @author    Hermann Stainer <hs@web-gear.com>
 * @author    Martin Schütte <info@mschuette.name>
 * @copyright 2003-2005 Hermann Stainer, Web-Gear
 * @copyright 2008 Martin Schütte
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_DTA
 */

/**
 * needs base class
 */
require_once 'DTABase.php';

/**
* Determines the type of the DTA file:
* DTA file contains credit payments.
*
* @const DTA_CREDIT
*/
define("DTA_CREDIT", 0);

/**
* Determines the type of the DTA file:
* DTA file contains debit payments (default).
*
* @const DTA_DEBIT
*/
define("DTA_DEBIT", 1);


/**
* Dta class provides functions to create and handle with DTA files
* used in Germany to exchange informations about money transactions with
* banks or online banking programs.
*
* @category Payment
* @package  Payment_DTA
* @author   Hermann Stainer <hs@web-gear.com>
* @license  http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
* @version  Release: @package_version@
* @link     http://pear.php.net/package/Payment_DTA
*/
class DTA extends DTABase
{
    /**
    * Type of DTA file, DTA_CREDIT or DTA_DEBIT.
    *
    * @var integer $type
    */
    var $type;

    /**
    * Constructor. The type of the DTA file must be set. One file can
    * only contain credits (DTA_CREDIT) OR debits (DTA_DEBIT).
    * This is a definement of the DTA format.
    *
    * @param integer $type Determines the type of the DTA file.
    *                       Either DTA_CREDIT or DTA_DEBIT. Must be set.
    *
    * @access public
    */
    function DTA($type)
    {
        $this->DTABase();
        $this->type = $type;
    }

    /**
    * Set the sender of the DTA file. Must be set for valid DTA file.
    * The given account data is also used as default sender's account.
    * Account data contains
    *  name            Sender's name. Maximally 27 chars are allowed.
    *  bank_code       Sender's bank code.
    *  account_number  Sender's account number.
    *  additional_name If necessary, additional line for sender's name
    *                  (maximally 27 chars).
    *
    * @param array $account Account data fot file sender.
    *
    * @access public
    * @return boolean
    */
    function setAccountFileSender($account)
    {
        if (strlen($account['name']) > 0
            && strlen($account['bank_code']) <= 8
            && ctype_digit($account['bank_code'])
            && strlen($account['account_number']) <= 10
            && ctype_digit($account['account_number'])) {

            if (empty($account['additional_name'])) {
                $account['additional_name'] = "";
            }

            $this->account_file_sender = array(
                "name"            => substr($this->makeValidString($account['name']), 0, 27),
                "bank_code"       => $account['bank_code'],
                "account_number"  => $account['account_number'],
                "additional_name" => substr($this->makeValidString($account['additional_name']), 0, 27)
            );

            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
    * Adds an exchange. First the account data for the receiver of the exchange is
    * set. In the case the DTA file contains credits, this is the payment receiver.
    * In the other case (the DTA file contains debits), this is the account, from
    * which money is taken away. If the sender is not specified, values of the
    * file sender are used by default.
    *
    * Account data for receiver and sender contain
    *  name            Name. Maximally 27 chars are allowed.
    *  bank_code       Bank code.
    *  account_number  Account number.
    *  additional_name If necessary, additional line for name (maximally 27 chars).
    *
    * @param array  $account_receiver Receiver's account data.
    * @param double $amount           Amount of money in this exchange.
    *                                 Currency: EURO
    * @param array  $purposes         Array of up to 15 lines
    *                                 (maximally 27 chars each) for
    *                                 description of the exchange.
    * @param array  $account_sender   Sender's account data.
    *
    * @access public
    * @return boolean
    */
    function addExchange($account_receiver, $amount, $purposes, $account_sender = array())
    {
        if (empty($account_receiver['additional_name'])) {
            $account_receiver['additional_name'] = "";
        }
        if (empty($account_sender['name'])) {
            $account_sender['name'] = $this->account_file_sender['name'];
        }
        if (empty($account_sender['bank_code'])) {
            $account_sender['bank_code'] = $this->account_file_sender['bank_code'];
        }
        if (empty($account_sender['account_number'])) {
            $account_sender['account_number'] =
                $this->account_file_sender['account_number'];
        }
        if (empty($account_sender['additional_name'])) {
            $account_sender['additional_name'] =
                $this->account_file_sender['additional_name'];
        }

        if (strlen($account_sender['name']) > 0
            && strlen($account_sender['bank_code']) <= 8
            && ctype_digit($account_sender['bank_code'])
            && strlen($account_sender['account_number']) <= 10
            && ctype_digit($account_sender['account_number'])
            && strlen($account_receiver['name']) > 0
            && strlen($account_receiver['bank_code']) <= 8
            && ctype_digit($account_receiver['bank_code'])
            && strlen($account_receiver['account_number']) <= 10
            && ctype_digit($account_receiver['account_number'])
            && is_numeric($amount) && $amount > 0 && $amount <= PHP_INT_MAX/100
            && $this->sum_amounts/100 <= PHP_INT_MAX/100 - $amount
            && ((is_array($purposes) && count($purposes) >= 1)
                || (is_string($purposes) && strlen($purposes) > 0))) {

            $amount = intval(round($amount * 100));

            $this->sum_amounts += $amount;

            if (is_string($purposes)) {
                $purposes = array($purposes, "");
            }

            $purposes_data = $purposes;
            $purposes      = array();

            foreach ($purposes_data as $purpose) {
                $purposes[] = substr($this->makeValidString($purpose), 0, 27);
            }

            $this->exchanges[] = array(
                "sender_name"              => substr($this->makeValidString($account_sender['name']), 0, 27),
                "sender_bank_code"         => $account_sender['bank_code'],
                "sender_account_number"    => $account_sender['account_number'],
                "sender_additional_name"   => substr($this->makeValidString($account_sender['additional_name']), 0, 27),
                "receiver_name"            => substr($this->makeValidString($account_receiver['name']), 0, 27),
                "receiver_bank_code"       => $account_receiver['bank_code'],
                "receiver_account_number"  => $account_receiver['account_number'],
                "receiver_additional_name" => substr($this->makeValidString($account_receiver['additional_name']), 0, 27),
                "amount"                   => $amount,
                "purposes"                 => $purposes
            );

            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
    * Returns the full content of the generated DTA file.
    * All added exchanges are processed.
    *
    * @access public
    * @return string
    */
    function getFileContent()
    {
        $content = "";

        $sum_account_numbers = 0;
        $sum_bank_codes      = 0;
        $sum_amounts         = 0;

        /**
         * data record A
         */

        // record length (128 Bytes)
        $content .= str_pad("128", 4, "0", STR_PAD_LEFT);
        // record type
        $content .= "A";
        // file mode (credit or debit)
        $content .= ($this->type == DTA_CREDIT) ? "G" : "L";
        // Customer File ("K") / Bank File ("B")
        $content .= "K";
        // sender's bank code
        $content .= str_pad($this->account_file_sender['bank_code'],
                        8, "0", STR_PAD_LEFT);
        // only used if Bank File, otherwise NULL
        $content .= str_repeat("0", 8);
        // sender's name
        $content .= str_pad($this->account_file_sender['name'],
                        27, " ", STR_PAD_RIGHT);
        // date of file creation
        $content .= strftime("%d%m%y", $this->timestamp);
        // free (bank internal)
        $content .= str_repeat(" ", 4);
        // sender's account number
        $content .= str_pad($this->account_file_sender['account_number'],
                        10, "0", STR_PAD_LEFT);
        // sender's reference number (optional)
        $content .= str_repeat("0", 10);
        // free (reserve)
        $content .= str_repeat(" ", 15);
        // execution date ("DDMMYYYY", optional)
        $content .= str_repeat(" ", 8);
        // free (reserve)
        $content .= str_repeat(" ", 24);
        // currency (1 = Euro)
        $content .= "1";

        assert(strlen($content) == 128);

        /**
         * data record(s) C
         */

        foreach ($this->exchanges as $exchange) {
            $sum_account_numbers += $exchange['receiver_account_number'];
            $sum_bank_codes      += (int) $exchange['receiver_bank_code'];
            $sum_amounts         += (int) $exchange['amount'];

            $additional_purposes = $exchange['purposes'];
            $first_purpose       = array_shift($additional_purposes);

            $additional_parts = array();

            if (strlen($exchange['receiver_additional_name']) > 0) {
                $additional_parts[] = array("type" => "01",
                    "content" => $exchange['receiver_additional_name']
                    );
            }

            foreach ($additional_purposes as $additional_purpose) {
                $additional_parts[] = array("type" => "02",
                    "content" => $additional_purpose
                    );
            }

            if (strlen($exchange['sender_additional_name']) > 0) {
                $additional_parts[] = array("type" => "03",
                    "content" => $exchange['sender_additional_name']
                    );
            }

            $additional_parts_number = count($additional_parts);
            // record length (187 Bytes + 29 Bytes for each additional part)
            $content .= str_pad(187 + $additional_parts_number * 29,
                            4, "0", STR_PAD_LEFT);
            // record type
            $content .= "C";
            // first involved bank
            $content .= str_pad($exchange['sender_bank_code'],
                            8, "0", STR_PAD_LEFT);
            // receiver's bank code
            $content .= str_pad($exchange['receiver_bank_code'],
                            8, "0", STR_PAD_LEFT);
            // receiver's account number
            $content .= str_pad($exchange['receiver_account_number'],
                            10, "0", STR_PAD_LEFT);
            // internal customer number (11 chars) or NULL
            $content .= "0" . str_repeat("0", 11) . "0";
            // payment mode (text key)
            $content .= ($this->type == DTA_CREDIT) ? "51" : "05";
            // additional text key
            $content .= "000";
            // bank internal
            $content .= " ";
            // free (reserve)
            $content .= str_repeat("0", 11);
            // sender's bank code
            $content .= str_pad($exchange['sender_bank_code'],
                            8, "0", STR_PAD_LEFT);
            // sender's account number
            $content .= str_pad($exchange['sender_account_number'],
                            10, "0", STR_PAD_LEFT);
            // amount
            $content .= str_pad($exchange['amount'],
                            11, "0", STR_PAD_LEFT);
            // free (reserve)
            $content .= str_repeat(" ", 3);
            // receiver's name
            $content .= str_pad($exchange['receiver_name'],
                            27, " ", STR_PAD_RIGHT);
            // delimitation
            $content .= str_repeat(" ", 8);
            // sender's name
            $content .= str_pad($exchange['sender_name'],
                            27, " ", STR_PAD_RIGHT);
            // first line of purposes
            $content .= str_pad($first_purpose, 27, " ", STR_PAD_RIGHT);
            // currency (1 = Euro)
            $content .= "1";
            // free (reserve)
            $content .= str_repeat(" ", 2);
            // amount of additional parts
            $content .= str_pad($additional_parts_number, 2, "0", STR_PAD_LEFT);

            if (count($additional_parts) > 0) {
                for ($index = 1;$index <= 2;$index++) {
                    if (count($additional_parts) > 0) {
                        $additional_part = array_shift($additional_parts);
                    } else {
                        $additional_part = array("type" => "  ",
                            "content" => ""
                            );
                    }
                    // type of addional part
                    $content .= $additional_part['type'];
                    // additional part content
                    $content .= str_pad($additional_part['content'],
                                    27, " ", STR_PAD_RIGHT);
                }
                // delimitation
                $content .= str_repeat(" ", 11);
            }

            for ($part = 3;$part <= 5;$part++) {
                if (count($additional_parts) > 0) {
                    for ($index = 1;$index <= 4;$index++) {
                        if (count($additional_parts) > 0) {
                            $additional_part = array_shift($additional_parts);
                        } else {
                            $additional_part = array("type" => "  ",
                                "content" => ""
                                );
                        }
                        // type of addional part
                        $content .= $additional_part['type'];
                        // additional part content
                        $content .= str_pad($additional_part['content'],
                                        27, " ", STR_PAD_RIGHT);
                    }
                    // delimitation
                    $content .= str_repeat(" ", 12);
                }
            }
        }

        assert(strlen($content) % 128 == 0);

        /**
         * data record E
         */

        // record length (128 bytes)
        $content .= str_pad("128", 4, "0", STR_PAD_LEFT);
        // record type
        $content .= "E";
        // free (reserve)
        $content .= str_repeat(" ", 5);
        // number of records type C
        $content .= str_pad(count($this->exchanges), 7, "0", STR_PAD_LEFT);
        // free (reserve)
        $content .= str_repeat("0", 13);
        // use number_format() to ensure proper integer formatting
        // sum of account numbers
        $content .= str_pad(number_format($sum_account_numbers, 0, "", ""),
            17, "0", STR_PAD_LEFT);
        // sum of bank codes
        $content .= str_pad(number_format($sum_bank_codes, 0, "", ""),
            17, "0", STR_PAD_LEFT);
        // sum of amounts
        assert($sum_amounts == $this->sum_amounts);
        $content .= str_pad(number_format($sum_amounts, 0, "", ""),
            13, "0", STR_PAD_LEFT);
        // delimitation
        $content .= str_repeat(" ", 51);

        assert(strlen($content) % 128 == 0);

        return $content;
    }
}
