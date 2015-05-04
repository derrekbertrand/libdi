<?php

namespace \LibDI;

/**
 * 
 */
class FormLord
{
	private $magic = '';
	private $config = array();
	private $data = array();
	private $form_serfs = array();
	private $error_messages = array();
	private $validated = false;


	public function __construct($data_array = array(), $config_profile = 'default')
	{
		//try to start a session if it hasn't been done
		if(session_status() === PHP_SESSION_NONE) session_start();

		//set a magic cookie if it has not been done
		//we use this to identify a user accessing a particular type of form
		//the same site could serve any number of configurations of forms
		$this->magic = hash('sha256', 'FormLord'.$config_profile);

		if(!isset($_SESSION[$this->magic]))
		{
			//set some info that we may need on future requests
			$_SESSION[$this->magic]['profile'] = $config_profile; //what is the name of this profile?
		}

		//set and clean our configuration
		$this->_cleanConfig($config_profile);

		//add the data array as our data list
		$this->data = $data_array;
		//TODO: possibly cleanse the data here
		//if there is info in our data array...
		if(count($data_array))
			$this->_createSerfs();
	}

	public function embedJS()
	{
		echo $this->getEmbedJS();
	}

	/**
	 * @return true if there is an error false otherwise
	 * @see messages
	 */
	public function fails()
	{
		return count($this->error_messages) ? true : false;
	}

	/**
	 * @return the embed code for our AJAX handler
	 * @see getJSPath, embedJS	
	 */
	public function getEmbedJS()
	{
		$path_to_js = $this->getJSPath();
		return '<script type="text/javascript" src="'.$path_to_js.'"></script>';
	}

	/**
	 * @return the path to the php file that will generate a JS include
	 * @see getEmbedJS
	 */
	public function getJSPath()
	{
		
		return $this->config['ajax_path'].'?form='.$this->magic;
	}

	public function handleSubmission($profile = 'default')
	{
		;
	}

	/**
	 * @return array of error messages
	 * @see fails
	 */
	public function messages()
	{
		return $this->error_messages;
	}

	public function toArray()
	{
		;
	}

	public function toJSON()
	{
		;
	}


	// returns a default json which we merge with the user supplied one
	private function _getDefaultJSON()
	{
		ob_start();
?>
{
	"form" : "form",
	"ajax_path" : "/jformlord.php",
	"csrf" : true,
	"errors" : {
		"debug" : false,
		"style" : "paragraph",
		"selector" : "form"
	},
	"submissions" : {
		"default" : {
			"type" : "insert"
		}
	},
	"validations" : {

	},
	"serfs" : {

	}
}
<?php
		return ob_get_clean();
	}

	// loads and cleans the config array
	private function _cleanConfig($filename)
	{
		//lets try to get the config profile
		$json_str = @file_get_contents('./libdi.'.$filename.'.json.php');
		if($json_str === false)
		{
			$this->error_messages[] = 'Could not open the configuration file ./libdi.'.$filename.'.json.php';
			return false;
		}
		$user_cfg = json_decode($json_str);
		if($user_cfg === null)
		{
			$this->error_messages[] = 'Could not decode JSON in file ./libdi.'.$filename.'.json.php';
			return false;
		}

		//get the default config
		$default_cfg = json_decode($this->_getDefaultJSON());
		if($default_cfg === null)
		{
			$this->error_messages[] = 'Could not decode default JSON.';
			return false;
		}

		//merge the default with the overrides
		$this->config = self::merge_distinct($default_cfg, $user_cfg);

		//make sure we merge submission defaults each time
		foreach($this->config['submissions'] as $user_sub_key => $user_sub_val)
		{
			$this->config['submissions'][$user_sub_key] = self::merge_distinct($default_cfg['submissions']['default'], $user_sub_val);
		}

		return true;
	}

	private function _createSerfs()
	{
		//create our serfs...
		foreach($this->config['serfs'] as $key => $val)
		{
			//TODO: make more flexible
			$serfclass = '\\LibDI\\'.ucfirst($key).'FS';
			$submit_profile = isset($this->data['_libdi_submission_profile']) ? $this->data['_libdi_submission_profile'] : 'default';
			$tmp_cfg = array();
			$tmp_cfg['debug'] = $this->config['errors']['debug']; //whether to debug
			$tmp_cfg['cfg'] = $val; //send over the config info for this serf

			$type = $this->config['submissions'][$submit_profile]['type']; //is this an update or insert?
			//TODO: filter data?
			if(class_exists($serfclass))
			{
				$serf = new $serfclass($this->data, $tmp_cfg);
			
				if($type == 'update')
					$serf->update();
				else
					$serf->insert();
			}
			else
				throw new Exception('Class \\'.$serfclass.' not found.');
		}
	}

	//algorithm from here: http://php.net/manual/en/function.array-merge-recursive.php
	//the regular merge recursive has some... issues.
	private static function merge_distinct(array &$array1, array &$array2)
	{
		$merged = $array1;

		foreach($array2 as $key => &$val)
		{
			if(is_array($val) && isset($merged[$key]) && is_array($merged[$key]))
				$merged[$key] = self::merge_distinct($merged[$key], $val);
			else
				$merged[$key] = $val;
		}

		return $merged;
	}
}

