<?php


/**
 * Write code on Method
 *
 * @return response()
 */
if (! function_exists('generateReferralCode')) {
    function generateReferralCode($str)
    {
        // get random number min & max 4 digit
        $randomNumber = rand(10000, 99999);
        // get the first 4 character and trim whitespace
        $strName = trim(substr($str, 0, 4));
        // get str length
        $strLen = strlen($strName);
        // If str length less then 4 add missing x character by random str
        $substrName = $strLen < 4 ? $strName . Str::random(4 - $strLen) : $strName;
        // make name uppercase and concat with the random number
        $referral = strtoupper($substrName) . $randomNumber;
        return $referral;
    }
}


