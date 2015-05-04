<?php

namespace LibDI;

abstract class FormSerf
{
	private $config = array();
	private $data = array();
	private $response_raw = null;
	private $response_array = array();

	abstract public function __construct($data, $config);

	abstract public function fails();

	abstract public function insert();

	public function toArray()
	{
		return $response_array;
	}

	public function toJSON()
	{
		return json_encode($response_array);
	}

	abstract public function update();

}

