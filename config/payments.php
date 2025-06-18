<?php

use Illuminate\Support\Str;

return [
    'inventory_account_code'  => '1500', // Voorraad toename
    'vat_account_code'        => '1700', // BTW ingekochte goederen
    'payables_account_code'   => '1600', // Schuld aan leverancier
    'default_vat_rate'        => 0.21,   // Standaard BTW percentage
    'default_bank_account_id' => 1, // Dit is het ID van het standaard bankaccount, bv. het record met code 1100.

];