<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$chapa_secret = "CHASECK_TEST-0fI7PN5i5pzI6f9enXibB1CfmGOolY7C";

$data = [
    'amount' => '100',
    'currency' => 'ETB',
    'email' => 'habtamucherinet40@gmail.com', // 🛑 FIXED: Missing '@'
    'first_name' => 'Cherinet',
    'last_name' => 'Habtamu',
    'tx_ref' => 'tx-' . time(),
    'callback_url' => 'http://localhost/bgis/view/sd.php?msg=pay_success', 
    'return_url' => 'http://localhost/bgis/view/sd.php?msg=pay_success',  
'customization' => [
    'title' => 'Project Support', // ✅ 15 characters
    'description' => 'Tuition fee or other payment',
]
];

$ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $chapa_secret,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);

if (isset($responseData['data']['checkout_url'])) {
    header('Location: ' . $responseData['data']['checkout_url']); // ✅ FIXED typo (was `Loaction`)
    exit;
} else {
    echo "Failed to initiate payment. Response: ";
    print_r($responseData); // 👈 Useful for debugging
}
