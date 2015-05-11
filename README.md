# cakephp-sms
Send SMS with CakePHP

## Usage

``` php
App::uses('CakeSms', 'Sms.Network/Sms');
$CakeSms = new CakeSms('default');
$CakeSms->to('+491234567890');
$CakeSms->from('+841234567890');
$CakeSms->send('Hello world!');
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

App::uses('AbstractSmsTransport', 'Sms.Network/Sms');

use Xi\Sms\Gateway\ClickatellGateway;

class ClickatellSmsTransport extends AbstractSmsTransport {

	const CLICKATELL_API_ID = 'XXXX';
	const CLICKATELL_USER = 'YYYY';
	const CLICKATELL_PASSWORD = 'ZZZZ';
	const CLICKATELL_API_URL = 'http://api.clickatell.com';

	/**
	 * Sends an SMS Through Clickatell
	 * We could also consider using this library:: http://github.com/arcturial/clickatell
	 * @param CakeSms $sms
	 * @return bool
	 */
	public function send(CakeSms $sms) {

		$gw = new ClickatellGateway(
			self::CLICKATELL_API_ID,
			self::CLICKATELL_USER,
			self::CLICKATELL_PASSWORD,
			self::CLICKATELL_API_URL
		);

		$service = new Xi\Sms\SmsService($gw);

		$msg = new Xi\Sms\SmsMessage(
			$sms->message(),
			$sms->from(),
			$sms->to()
		);

		$response = $service->send($msg);

		return !empty($response);
	}
}
```
