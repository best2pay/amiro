<?php
/**
 * Best2Pay payment system driver
 * Copyright (c) 2016 Dennis Prochko <wolfsoft@mail.ru>
 */
class Best2pay_PaymentSystemDriver extends AMI_PaymentSystemDriver {

	protected $driverName = 'best2pay';

	private $requiredParams = array(
		'order',
		'process_url',
		'b2p_sector_id',
		'b2p_password',
		'b2p_test_mode'
	);

	public function __construct(GUI_Template $oGUI){
		parent::__construct($oGUI);
		$this->currencies = $this->parseCurrencies('#USD RUR EUR');
		$this->initialCurrencies = $this->currencies;
	}

	public function getPayButton(&$aRes, $aData, $bAutoRedirect = false) {
		$res = true;
		$aRes['error'] = 'Success';
		$aRes['errno'] = 0;

		foreach ($this->requiredParams as $key) {
			if (empty($aData[$key])) {
				$aRes['errno'] = 1;
				$aRes['error'] = "Required parameter {$key} is missed";
				$res = false;
			}
		}

		if (empty($aData['button_name']) && empty($aData['button'])) {
			$aRes['errno'] = 1;
			$aRes['error'] = 'button_name or button is missed';
			$res = false;
		}

		unset($aData['b2p_sector_id']);
		unset($aData['b2p_password']);
		unset($aData['b2p_test_mode']);

		$aData['hiddens'] = $this->getScopeAsFormHiddenFields($aData);

		$aData['button'] = trim($aData['button']);

		if (!empty($aData['button'])) {
			$aData['_button_html'] = 1;
		}

		if (!$res) {
			$aData['disabled'] = 'disabled';
		}

		return parent::getPayButton($aRes, $aData, $bAutoRedirect);
	}

	public function getPayButtonParams($aData, &$aRes) {
		switch ($aData['currency']) {
			case 'EUR':
				$currency = '978';
				break;
			case 'USD':
				$currency = '840';
				break;
			default:
				$currency = '643';
				break;
		}

		$best2pay_url = "https://test.best2pay.net";
		if ($aData['b2p_test_mode'] == '0')
			$best2pay_url = "https://pay.best2pay.net";

		$price = intval($aData['amount'] * 100);
		$signature = base64_encode(md5($aData['b2p_sector_id'] . $price . $currency . $aData['b2p_password']));

		$fiscalPositions = '';
		$fiscalAmount = 0;
		$TAX = 6;
		if (isset($aData['products'])) {
			foreach ($aData['products'] as $item) {
				$fiscalPositions.=$item['quantity'].';';
	            $elementPrice = $item['price'];
	            $elementPrice = $elementPrice * 100;
	            $fiscalPositions.=$elementPrice.';';
	            $fiscalPositions.=$TAX.';';
	            $fiscalPositions.=$item['name'].'|';

	            $fiscalAmount += $item['quantity'] * $elementPrice;
			}
	        if ($aData['shipping'] > 0) {
	            $fiscalPositions.='1;';
	            $fiscalPositions.=($aData['shipping']['amount']*100).';';
	            $fiscalPositions.=$TAX.';';
	            $fiscalPositions.='Доставка'.'|';

	            $fiscalAmount += $aData['shipping']['amount']*100;
	        }
	        $amountDiff = abs($fiscalAmount - $price);
	        if ($amountDiff) {
	        	$fiscalPositions.='1;'.$amountDiff.';6;coupon;14'.'|';
	        }
	        $fiscalPositions = substr($fiscalPositions, 0, -1);
	    }

		$context  = stream_context_create(array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query(array(
					'sector' => $aData['b2p_sector_id'],
					'reference' => $aData['order_id'],
					'fiscal_positions' => $fiscalPositions,
					'amount' => $price,
					'description' => $aData['description'],
					'email' => $aData['email'],
					'currency' => $currency,
					'mode' => 1,
					'url' => $aData['return'],
					'signature' => $signature
				)),
			)
		));
		$b2p_order_id = file_get_contents($best2pay_url . '/webapi/Register', false, $context);

		if (intval($b2p_order_id) == 0) {
			$aRes['errno'] = 2;
			$aRes['error'] = "Can't register the order {$b2p_order_id}";
			return false;
		}

		$signature = base64_encode(md5($aData['b2p_sector_id'] . $b2p_order_id . $aData['b2p_password']));

		$aData['url'] = $best2pay_url . '/webapi/Purchase';
		$aData['b2p_order_id'] = $b2p_order_id;
		$aData['b2p_signature'] = $signature;

		$aRes['errno'] = 0;
		$aRes['error'] = 'Success';

		return parent::getPayButtonParams($aData, $aRes);
	}

	public function payProcess(array $aGet, array $aPost, array &$aRes, array $aData, array $aOrderData) {
		if ($this->checkPaymentState($aGet, $aData))
			return parent::payProcess($aGet, $aPost, $aRes, $aCheckData, $aOrderData);
		else
			return false;
	}

	public function payCallback($aGet, $aPost, &$aRes, $aData, $aOrderData) {
		if ($this->checkPaymentState($aGet, $aData))
			return 1;
		else
			return 0;
	}

	public function getProcessOrder($aGet, $aPost, &$aRes, $aAdditionalParams) {
		return intval($aGet['reference']);
	}

	private function checkPaymentState($aGet, $aData) {
		$best2pay_url = "https://test.best2pay.net";
		if ($aData['b2p_test_mode'] == '0')
			$best2pay_url = "https://pay.best2pay.net";
		$url = $best2pay_url . '/webapi/Operation';

		$signature = base64_encode(md5($aData['b2p_sector_id'] . intval($aGet['id']) . intval($aGet['operation']) . $aData['b2p_password']));

		$context = stream_context_create(array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query(array(
					'sector' => $aData['b2p_sector_id'],
					'id' => intval($aGet['id']),
					'operation' => intval($aGet['operation']),
					'signature' => $signature
				)),
			)
		));

		$repeat = 3;
		try {
			while ($repeat) {
				$repeat--;

				$xml = file_get_contents($url, false, $context);
				if (!$xml)
					throw new Exception("Empty data");
				$xml = simplexml_load_string($xml);
				if (!$xml)
					throw new Exception("Non valid XML was received");
				$response = json_decode(json_encode($xml), true);
				if (!$response)
					throw new Exception("Non valid XML was received");

				$tmp_response = (array)$response;
				unset($tmp_response['signature'], $tmp_response['ofd_state']);
				$signature = base64_encode(md5(implode('', $tmp_response) . $aData['b2p_password']));
				if ($signature !== $response['signature'])
					throw new Exception("Invalid signature");

				if (($response['type'] != 'PURCHASE' && $response['type'] != 'PURCHASE_BY_QR' && $response['type'] != 'AUTHORIZE') || $response['state'] != 'APPROVED') {
					sleep(2);
					continue;
				}

				return true;
			}

			throw new Exception('Unknown error');

		} catch (Exception $ex) {
			error_log($ex->getMessage());
		}

		return false;
	}

}
