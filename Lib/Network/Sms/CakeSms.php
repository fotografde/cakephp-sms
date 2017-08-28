<?php

App::uses('AbstractSmsTransport', 'Sms.Network/Sms');
App::uses('View', 'View');

class CakeSms {

	/**
	 * Holds the regex pattern for phone number validation
	 *
	 * @var string
	 */
	const NUMBER_PATTERN = '/^\+\d+$/ui';

	/**
	 * Configuration to transport
	 *
	 * @var string|array
	 */
	protected $_config = [];

	/**
	 * The class name used for SMS configuration.
	 *
	 * @var string
	 */
	protected $_configClass = 'SmsConfig';

	/**
	 * Recipient of the SMS
	 *
	 * @var array
	 */
	protected $_to = [];

	/**
	 * The phone number which the SMS is sent from
	 *
	 * @var array
	 */
	protected $_from = '';

	/**
	 * Final message to send
	 *
	 * @var array
	 */
	protected $_message = '';

	/**
	 * What method should the email be sent
	 *
	 * @var string
	 */
	protected $_transportName = 'Sms';

	/**
	 * Instance of transport class
	 *
	 * @var AbstractTransport
	 */
	protected $_transportClass = null;

	/**
	 * Regex for phone number validation
	 *
	 * If null, filter_var() will be used. Use the numberPattern() method
	 * to set a custom pattern.'
	 *
	 * @var string
	 */
	protected $_numberPattern = self::NUMBER_PATTERN;

	/**
	 * Layout for the View
	 *
	 * @var string
	 */
	protected $_layout = 'default';

	/**
	 * Template for the view
	 *
	 * @var string
	 */
	protected $_template = '';

	/**
	 * View for render
	 *
	 * @var string
	 */
	protected $_viewRender = 'View';

	/**
	 * Vars to sent to render
	 *
	 * @var array
	 */
	protected $_viewVars = [];

	/**
	 * Theme for the View
	 *
	 * @var array
	 */
	protected $_theme = null;

	/**
	 * Helpers to be used in the render
	 *
	 * @var array
	 */
	protected $_helpers = [];

	/**
	 * Constructor
	 *
	 * @param array|string $config Array of configs, or string to load configs from sms.php
	 */
	public function __construct($config = null) {
		if ($config) {
			$this->config($config);
		}
	}

	/**
	 * Configuration to use when sending SMS
	 *
	 * ### Usage
	 *
	 * Load configuration from `app/Config/sms.php`:
	 *
	 * `$sms->config('default');`
	 *
	 * Merge an array of configuration into the instance:
	 *
	 * `$sms->config(array('to' => '00123456789'));`
	 *
	 * @param string|array $config String with configuration name (from sms.php), array with config or null to return current config
	 * @return string|array|$this
	 */
	public function config($config = null) {
		if ($config === null) {
			return $this->_config;
		}
		if (!is_array($config)) {
			$config = (string)$config;
		}

		$this->_applyConfig($config);
		return $this;
	}

	/**
	 * Apply the config to an instance
	 *
	 * @param array $config Configuration options.
	 * @return void
	 * @throws ConfigureException When configuration file cannot be found, or is missing
	 *   the named config.
	 */
	protected function _applyConfig($config) {
		if (is_string($config)) {
			if (!class_exists($this->_configClass) && !config('sms')) {
				throw new ConfigureException(__d('cake_dev', '%s not found.', APP . 'Config' . DS . 'sms.php'));
			}
			$configs = new $this->_configClass();
			if (!isset($configs->{$config})) {
				throw new ConfigureException(__d('cake_dev', 'Unknown email configuration "%s".', $config));
			}
			$config = $configs->{$config};
		}
		$this->_config = $config + $this->_config;
		$simpleMethods = [
			'from', 'to', 'transport'
		];
		foreach ($simpleMethods as $method) {
			if (isset($config[$method])) {
				$this->$method($config[$method]);
				unset($config[$method]);
			}
		}

		$this->transportClass()->config($config);
	}

	/**
	 * To
	 *
	 * @param string|array $phoneNumber Null to get, String with phone number,
	 *   Array of phone numbers
	 * @return array|Sms
	 */
	public function to($phoneNumber = null) {
		if ($phoneNumber === null) {
			return $this->_to;
		}
		$phoneNumber = (array) $phoneNumber;
		return $this->_setPhoneNumber('_to', $phoneNumber);
	}

	/**
	 * From
	 *
	 * @param string $phoneNumber Null to get, String with phone number
	 * @return array|Sms
	 * @throws SocketException
	 */
	public function from($phoneNumber = null) {
		if ($phoneNumber === null) {
			return $this->_from;
		}
		if (empty($phoneNumber)) {
			return $this;
		}
		return $this->_setPhoneNumber('_from', $phoneNumber);
	}

	/**
	 * Add To
	 *
	 * @param string|array $phoneNumber String with phone number,
	 *   Array of phone numbers
	 * @return $this
	 */
	public function addTo($phoneNumber) {
		return $this->_addPhoneNumber('_to', $phoneNumber);
	}

	/**
	 * Set phone number
	 *
	 * @param string $varName Property name
	 * @param string|array $phoneNumber String with phone number,
	 *   Array of phone numbers
	 * @return $this
	 */
	protected function _setPhoneNumber($varName, $phoneNumber) {
		if (!is_array($phoneNumber)) {
			$this->_validatePhoneNumber($phoneNumber);
			$this->{$varName} = $phoneNumber;
			return $this;
		}
		$list = [];
		foreach ($phoneNumber as $value) {
			$this->_validatePhoneNumber($value);
			$list[] = $value;
		}
		$this->{$varName} = $list;
		return $this;
	}

	/**
	 * NumberPattern setter/getter
	 *
	 * @param string|bool|null $regex The pattern to use for phone number validation,
	 *   null to unset the pattern and make use of filter_var() instead, false or
	 *   nothing to return the current value
	 * @return string|$this
	 */
	public function numberPattern($regex = false) {
		if ($regex === false) {
			return $this->_numberPattern;
		}
		$this->_numberPattern = $regex;
		return $this;
	}

	/**
	 * Validate a phone number
	 *
	 * @param string $phoneNumber Phone number
	 * @return void
	 * @throws SocketException If email address does not validate
	 */
	protected function _validatePhoneNumber($phoneNumber) {

		if ($this->_numberPattern === null) {
			if (filter_var($phoneNumber, FILTER_VALIDATE_INT)) {
				return;
			}
		} elseif (preg_match($this->_numberPattern, $phoneNumber)) {
			return;
		}
		
		throw new SocketException(__d('cake_dev', 'Invalid phone number: "%s"', $phoneNumber));
	}

	/**
	 * Add phone number
	 *
	 * @param string $varName Property name
	 * @param string|array $phoneNumber String with phone number,
	 *   Array of phone numbers
	 * @return $this
	 * @throws SocketException
	 */
	protected function _addPhoneNumber($varName, $phoneNumber) {
		if (!is_array($phoneNumber)) {
			$this->_validatePhoneNumber($phoneNumber);
			$this->{$varName}[] = $phoneNumber;
			return $this;
		}
		$list = [];
		foreach ($phoneNumber as $value) {
			$this->_validatePhoneNumber($value);
			$list[] = $value;
		}
		$this->{$varName} = array_merge($this->{$varName}, $list);
		return $this;
	}

	/**
	 * Get generated message (used by transport classes)
	 *
	 * @return string
	 */
	public function message() {
		return $this->_message;
	}

	/**
	 * Transport name
	 *
	 * @param string $name Transport name.
	 * @return string|$this
	 */
	public function transport($name = null) {
		if ($name === null) {
			return $this->_transportName;
		}
		$this->_transportName = (string)$name;
		$this->_transportClass = null;
		return $this;
	}

	/**
	 * Return the transport class
	 *
	 * @return AbstractTransport
	 * @throws SocketException
	 */
	public function transportClass() {
		if ($this->_transportClass) {
			return $this->_transportClass;
		}
		list($plugin, $transportClassname) = pluginSplit($this->_transportName, true);
		$transportClassname .= 'SmsTransport';
		App::uses($transportClassname, $plugin . 'Network/Sms');
		if (!class_exists($transportClassname)) {
			throw new SocketException(__d('cake_dev', 'Class "%s" not found.', $transportClassname));
		} elseif (!method_exists($transportClassname, 'send')) {
			throw new SocketException(__d('cake_dev', 'The "%s" does not have a %s method.', $transportClassname, 'send()'));
		}

		return $this->_transportClass = new $transportClassname();
	}

	/**
	 * Send an email using the specified content, template and layout
	 *
	 * @param string|array $content String with message or array with messages
	 * @return array
	 * @throws SocketException
	 */
	public function send($content = null) {
		if (empty($this->_to)) {
			throw new SocketException(__d('cake_dev', 'You need to specify at least one destination for to.'));
		}

		$this->_message = $this->_render($content);

		$contents = $this->transportClass()->send($this);
		if (!empty($this->_config['log'])) {
			$config = [
				'level' => LOG_DEBUG,
				'scope' => 'email'
			];
			if ($this->_config['log'] !== true) {
				if (!is_array($this->_config['log'])) {
					$this->_config['log'] = ['level' => $this->_config['log']];
				}
				$config = $this->_config['log'] + $config;
			}
			CakeLog::write(
				$config['level'],
				PHP_EOL . $contents['headers'] . PHP_EOL . $contents['message'],
				$config['scope']
			);
		}
		return $contents;
	}

	/**
	 * Render the body of the SMS.
	 *
	 * @param array $content Content to render
	 * @return array SMS body ready to be sent
	 */
	protected function _render($content) {
		$this->_message = '';
		$this->_message = $this->_renderTemplates($content);
		return $this->_message;
	}

	/**
	 * Build and set all the view properties needed to render the templated SMS.
	 * If there is no template set, the $content will be returned in a hash
	 * of the text content types for the email.
	 *
	 * @param string $content The content passed in from send() in most cases.
	 * @return string The rendered content.
	 */
	protected function _renderTemplates($content) {
		if (empty($this->_template)) {
			return $content;
		}

		$View = $this->viewRenderClass();

		$View->viewVars = $this->_viewVars;
		$View->helpers = $this->_helpers;

		if ($this->_theme) {
			$View->theme = $this->_theme; // @todo unit test
		}

		$View->loadHelpers();

		list($templatePlugin, $template) = pluginSplit($this->_template);
		list($layoutPlugin, $layout) = pluginSplit($this->_layout);
		if ($templatePlugin) {
			$View->plugin = $templatePlugin;
		} elseif ($layoutPlugin) {
			$View->plugin = $layoutPlugin;
		}

		if ($View->get('content') === null) {
			$View->set('content', $content);
		}

		// Convert null to false, as View needs false to disable
		// the layout.
		if ($this->_layout === null) {
			$this->_layout = false; // @todo unit test
		}

		$View->hasRendered = false;
		$View->viewPath = $View->layoutPath = 'Sms';

		$rendered = $View->render($this->_template, $this->_layout);
		return $rendered;
	}

	/**
	 * Template and layout
	 *
	 * @param bool|string $template Template name or null to not use
	 * @param bool|string $layout Layout name or null to not use
	 * @return array|$this
	 */
	public function template($template = false, $layout = false) {
		if ($template === false) {
			return [
				'template' => $this->_template,
				'layout' => $this->_layout
			];
		}
		$this->_template = $template;
		if ($layout !== false) {
			$this->_layout = $layout;
		}
		return $this;
	}

	/**
	 * View class for render
	 *
	 * @param string $viewClass View class name.
	 * @return string|$this
	 */
	public function viewRender($viewClass = null) {
		if ($viewClass === null) {
			return $this->_viewRender;
		}
		$this->_viewRender = $viewClass;
		return $this;
	}

	/**
	 * Return the View class
	 *
	 * @return View
	 */
	public function viewRenderClass() {
		$viewClass = $this->_viewRender;
		if ($viewClass !== 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass, true);
			$viewClass .= 'View';
			App::uses($viewClass, $plugin . 'View');
		}

		return new $viewClass(null);
	}

	/**
	 * Variables to be set on render
	 *
	 * @param array $viewVars Variables to set for view.
	 * @return array|$this
	 */
	public function viewVars($viewVars = null) {
		if ($viewVars === null) {
			return $this->_viewVars;
		}
		$this->_viewVars = array_merge($this->_viewVars, (array)$viewVars);
		return $this;
	}

	/**
	 * Theme to use when rendering
	 *
	 * @param string $theme Theme name.
	 * @return string|$this
	 */
	public function theme($theme = null) {
		if ($theme === null) {
			return $this->_theme;
		}
		$this->_theme = $theme;
		return $this;
	}

	/**
	 * Helpers to be used in render
	 *
	 * @param array $helpers Helpers list.
	 * @return array|$this
	 */
	public function helpers($helpers = null) {
		if ($helpers === null) {
			return $this->_helpers;
		}
		$this->_helpers = (array)$helpers;
		return $this;
	}

	/**
	 * Reset all CakeEmail internal variables to be able to send out a new email.
	 *
	 * @return $this
	 */
	public function reset() {
		$this->_to = [];
		$this->_from = '';
		$this->_message = '';
		$this->_config = [];
		return $this;
	}

}
