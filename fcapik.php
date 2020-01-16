<?php
$apik = '0f0cc597e5d9af240b80b35489b22347c519ad81'; //k por key
$apis = '66c19d0dd8dbabf175a206b2ef442cf2258650d6'; //s por secreto
$apit = time(); //t por tiempo
$hash = hash_hmac('sha1', $apik . $apit, $apis);

$ch = curl_init();
$url = "https://freedcamp.com/api/v1/sessions/current?api_key=$apik&timestamp=$apit&hash=$hash";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$r = curl_exec($ch);
curl_close($ch);

//echo $url;
echo $r;
?>
