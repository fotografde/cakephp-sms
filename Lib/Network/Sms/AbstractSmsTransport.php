<?php

/**
 * Abstract transport for sending Sms
 */
abstract class AbstractSmsTransport {

	/**
	 * Configurations
	 *
	 * @var array
	 */
	protected $_config = [];

	/**
	 * Send SMS
	 *
	 * @param CakeSms $sms CakeSms instance.
	 * @return array
	 */
	abstract public function send(CakeSms $sms);

	/**
	 * Set the config
	 *
	 * @param array $config Configuration options.
	 * @return array Returns configs
	 */
	public function config($config = null) {
		if (is_array($config)) {
			$this->_config = $config + $this->_config;
		}
		return $this->_config;
	}

}
