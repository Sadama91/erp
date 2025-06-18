<?php
// config/invoices.php
return [
    'default_purchase_account_code'   => '1300', // Inkoopkosten: boek de goederen als kosten (direct verbruikt)
    'default_sales_account_code'      => '4000', // Omzet
    'default_expense_account_code'    => '7000', // Kosten (voor overige uitgaven)

    'purchase_vat_account_code'       => '1700', // BTW te vorderen (inkoop) – actief
    'sales_vat_account_code'          => '1710', // BTW te betalen (verkoop) – passief
    'expense_vat_account_code'        => '1700', // BTW (kosten)

    'default_counter_account_code'    => '1600', // Crediteuren: tegenrekening voor inkoopfacturen
    'sales_counter_account_code'      => '1400', // Debiteuren: tegenrekening voor verkoopfacturen
];
