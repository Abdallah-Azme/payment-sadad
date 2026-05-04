<?php

$secretKey = "YOUR_TEST_SECRET_KEY";
$params = [
  "CALLBACK_URL"=>"https://yoursite.com/callback",
  "MOBILE_NO"=>"97412345678",
  "ORDER_ID"=>"ORD-20251216-001",
  "TXN_AMOUNT"=>"150.00",
  "WEBSITE"=>"MYSHOP",
  "email"=>"user@example.com",
  "merchant_id"=>"123456",
  "txnDate"=>"2025-12-16"
];
ksort($params);

echo "Sorted keys:\n";
print_r(array_keys($params));

$string=$secretKey;
foreach($params as $v){$string.=$v;}
echo "\nConcatenated string:\n";
echo $string . "\n";
echo "\nHash:\n";
echo strtoupper(hash('sha256',$string)) . "\n";
