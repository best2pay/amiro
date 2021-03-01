<?php
/**
 * Best2Pay payment system driver
 * Copyright (c) 2016 Dennis Prochko <wolfsoft@mail.ru>
 */
error_reporting(0);
header('Content-type: text/plain');

$xml = file_get_contents('php://input');
if (!$xml)
	die('ok');

$xml = simplexml_load_string($xml);
if (!$xml)
	die('ok');

$response = json_decode(json_encode($xml), true);
if (!$response || count($response) == 0)
	die('ok');

$url =
	(empty($_SERVER['HTTPS']) ? 'http://' : 'https://')
	. $_SERVER['HTTP_HOST'] . '/eshop_final.php'
	. '?item_number=' . urlencode($response['reference'])
	. '&reference=' . urlencode($response['reference'])
	. '&id=' . urlencode($response['order_id'])
	. '&operation=' . urlencode($response['id']);

file_get_contents($url);

die('ok');
