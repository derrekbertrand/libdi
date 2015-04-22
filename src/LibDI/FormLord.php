<?php

namespace LibDI;

class FormLord
{
	private $config = array();
	private $data = array();
	private $form_serfs = array();
	private $error_messages = array();
	private $validated = false;


	public function __construct($data_array = null, $config_profile = 'default')
	{
		;
	}

	public function fails()
	{
		return count($error_messages) ? true : false;
	}

	public function messages()
	{
		;
	}

	public function toArray()
	{
		;
	}

	public function toJSON()
	{
		;
	}
}

