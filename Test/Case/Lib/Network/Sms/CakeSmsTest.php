<?php
App::uses('CakeSms', 'Sms.Network/Sms');
App::uses('View', 'View');

/**
 * Class CakeSmsTest
 * @property CakeSms $CakeSms
 */
class CakeSmsTest extends CakeTestCase {

	public function setUp()
	{
		parent::setUp();
		$this->CakeSms = new CakeSms();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	public function testSend4() {
		$Transport = $this->getMock('SmsTransport', ['send']);
		$Transport->expects($this->once())
			->method('send');

		$View = $this->getMock('TestView', ['render']);
		$View->expects($this->once())
			->method('render')
			->with('you_got_mail', 'my_layout');

		$this->CakeSms = $this->getMock('CakeSms', ['transportClass', 'viewRenderClass']);
		$this->CakeSms->expects($this->once())
			->method('transportClass')
			->will($this->returnValue($Transport));
		$this->CakeSms->expects($this->once())
			->method('viewRenderClass')
			->will($this->returnValue($View));

		$this->CakeSms->to('+49123456789');
		$this->CakeSms->from('+491745764587');
		$this->CakeSms->template('you_got_mail', 'my_layout');
		$this->CakeSms->send('hello hello');

		$this->assertEquals($View->layoutPath, 'Sms');
		$this->assertEquals($View->viewPath, 'Sms');
		$this->setExpectedException('MissingViewException', 'View file "Sms' . DS . 'you_got_mail.ctp" is missing.');
		$View->getViewFileName('you_got_mail');
	}

	public function testSend3() {
		$Transport = $this->getMock('SmsTransport', ['send']);
		$Transport->expects($this->once())
			->method('send')
			->with($this->callback(function($actual) {
				return $actual->message() === 'hello hello';
			}));

		$this->CakeSms = $this->getMock('CakeSms', ['transportClass']);
		$this->CakeSms->expects($this->once())
			->method('transportClass')
			->will($this->returnValue($Transport));

		$this->CakeSms->to('+49123456789');
		$this->CakeSms->from('+491745764587');
		$this->CakeSms->send('hello hello');
	}

	public function testSend2() {
		$this->CakeSms->to('+49123456789');
		$this->setExpectedException('SocketException');
		$this->CakeSms->send();
	}

	public function testSend1() {
		$this->setExpectedException('SocketException');
		$this->CakeSms->send();
	}

	public function testReset() {
		$this->CakeSms->reset();
	}

	public function testValidatePhoneNumber4() {
		$this->setExpectedException('SocketException');
		$this->CakeSms->to('123456789');
	}

	public function testValidatePhoneNumber3() {
		$this->CakeSms->numberPattern(null);
		$this->CakeSms->to('123456789');
	}

	public function testValidatePhoneNumber2() {
		$this->CakeSms->to('+49123456789');
	}

	public function testValidatePhoneNumber1() {
		$this->setExpectedException('SocketException');
		$this->CakeSms->to('123456789');
	}

	public function testFromSetEmpty() {
		$this->CakeSms->from('');
	}

	public function testFromGetEmpty() {
		$this->assertSame('', $this->CakeSms->from());
	}

	public function testFromGet() {
		$this->CakeSms->from('+49123456789');
		$this->assertSame('+49123456789', $this->CakeSms->from());
	}

	public function testTo() {
		$this->assertSame([], $this->CakeSms->to());

		$result = $this->CakeSms->to('+491234567890');
		$expected = ['+491234567890'];
		$this->assertSame($expected, $this->CakeSms->to());
		$this->assertSame($this->CakeSms, $result);

		$list = [
			'+491234567890',
			'+49987654321',
		];
		$this->CakeSms->to($list);
		$expected = [
			'+491234567890',
			'+49987654321',
		];
		$this->assertSame($expected, $this->CakeSms->to());

		$this->CakeSms->addTo('+4924682468');
		$result = $this->CakeSms->addTo(['+493690369', '+491357913579']);
		$expected = [
			'+491234567890',
			'+49987654321',
			'+4924682468',
			'+493690369',
			'+491357913579',
		];
		$this->assertSame($expected, $this->CakeSms->to());
		$this->assertSame($this->CakeSms, $result);
	}

	public function testTransport() {
		$result = $this->CakeSms->transport('Debug');
		$this->assertSame($this->CakeSms, $result);
		$this->assertSame('Debug', $this->CakeSms->transport());

		$result = $this->CakeSms->transportClass();
		$this->assertInstanceOf('DebugSmsTransport', $result);

		$this->setExpectedException('SocketException');
		$this->CakeSms->transport('Invalid');
		$result = $this->CakeSms->transportClass();
	}

}

class DebugSmsTransport {

	/**
	 * Send mail
	 *
	 * @param CakeSms $sms CakeSms
	 * @return bool
	 */
	public function send(CakeSms $sms) {
		return true;
	}

}

class TestView extends View {

	/**
	 * Need to access View::_getViewFileName() in unit tests
	 * @param null $name
	 * @return string
	 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}
}
