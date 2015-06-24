# CakePHP Sms Plugin
[![Build Status](https://api.travis-ci.org/fotografde/cakephp-sms.svg)](https://travis-ci.org/fotografde/cakephp-sms)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/fotografde/cakephp-sms/license)](https://packagist.org/packages/fotografde/cakephp-sms)
[![Total Downloads](https://poser.pugx.org/fotografde/cakephp-sms/d/total)](https://packagist.org/packages/fotografde/cakephp-sms)

Send SMS with CakePHP.

## Usage

``` php
App::uses('CakeSms', 'Sms.Network/Sms');

$CakeSms = new CakeSms('default');
$CakeSms->to('+491234567890');
$CakeSms->from('+841234567890');
$CakeSms->send('Hello world!');
```

## Installation via Composer
``` javascript
"require": {
	"fotografde/cakephp-sms": ">=1.0.0"
}
```

## Configuration

Load plugin in Config/bootstrap.php

``` php
CakePlugin::load('Sms');
```

Create Config/sms.php

``` php
class SmsConfig {
	public $default = array(
		'transport' => 'Clickatell', // will use class ClickatellSmsTransport
	);
}
```

Implement a transport class under Lib/Network/Sms/. We recommend implementing [Xi SMS](https://github.com/xi-project/xi-sms), this way for example:

``` php
/**
 * Send SMS through SMS provider Clickatell
 */

use Xi\Sms\Gateway\ClickatellGateway;

App::uses('AbstractSmsTransport', 'Sms.Network/Sms');

class ClickatellSmsTransport extends AbstractSmsTransport {

	const CLICKATELL_API_ID = 'XXXX';
	const CLICKATELL_USER = 'YYYY';
	const CLICKATELL_PASSWORD = 'ZZZZ';

	/**
	 * Sends an SMS Through Clickatell
	 * We could also consider using this library: http://github.com/arcturial/clickatell
	 *
	 * @param CakeSms $sms
	 * @return bool Success
	 */
	public function send(CakeSms $sms) {
		$gw = new ClickatellGateway(
			self::CLICKATELL_API_ID,
			self::CLICKATELL_USER,
			self::CLICKATELL_PASSWORD
		);

		$service = new Xi\Sms\SmsService($gw);

		$msg = new Xi\Sms\SmsMessage(
			$sms->message(),
			self::parsePhoneNumber($sms->from()),
			self::parsePhoneNumber($sms->to())
		);

		$response = $service->send($msg);

		return !empty($response);
	}
	
	/**
	 * Parses a phone number to fit Clickatell requirements
	 * from +49123[...] to 49123[...]
	 *
	 * @param array|string $phoneNumber
	 * @return array|string|bool
	 */
	public static function parsePhoneNumber($phoneNumber) {
		if (is_array($phoneNumber)) {
			return array_map('self::parsePhoneNumber', $phoneNumber);
		}
		if (preg_match('/^\+([0-9]+)$/', (string) $phoneNumber, $matches)) {
			return $matches[1];
		}
		return false;
	}
}
```
