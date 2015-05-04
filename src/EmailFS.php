<?php

namespace LibDI;

class EmailFS extends FormSerf
{
	private $errors = false;

	public function __construct($data, $config)
	{
		//set our data to member properties
		$this->data = $data;
		$this->config = $config;
	}

	public function fails()
	{
		return !$errors;
	}

	public function insert()
	{
		$this->send();
	}

	public function update()
	{
		$this->send();
	}

	private function send()
	{
		//if we have mailgun installed and we have mailgun configured
		if(class_exists('\\Mailgun\\Mailgun') && isset($this->config['cfg']['mailgun']))
		{
			$this->_mailgun();
		}
		else
		{
			$this->_sendmail();
		}
	}

	//sendmail version of send
	private function _sendmail()
	{
		$msg = '';
		foreach($this->data as $key => $val)
		{
			$msg .= $key.': '.$val."\n";
		}

		//mail to each recipient
		foreach(is_array($this->config['cfg']['to']) ? $this->config['cfg']['to'] : array($this->config['cfg']['to']) as $to)
			mail($to, $this->config['cfg']['subject'], $msg, $this->config['cfg']['from']."\r\nX-Mailer: PHP");
	}

	//mailgun version of send
	private function _mailgun()
	{
		throw new Exception('Oh snap, haven\'t written MAILGUN yet.');
	}
}
