<?php

define('DI_OKAY', 0);
define('DI_FORMAT', 1);
define('DI_BOT', 2);


//strips entities and submits xml
//input being an array or fields
//errors being an (empty) array of errors
//fake is the $key of the fake field that should not be filled out
class DISubmission
{  
    private $ini = false;
    private $errors = array();
    private $submission = array();
    //a list of standard api values and their textual equivalents
    private $api_to_text = array(
        'phone_code' => 'Phone Country Code',
        'phone_number' => 'Phone Number',
        'title' => 'Title',
        'first_name' => 'First Name',
        'middle_initial' => 'Middle Initial',
        'last_name' => 'Last Name',
        'address1' => 'Street Address',
        'address2' => 'Address Line 2',
        'address3' => 'Address Line 3',
        'city' => 'City',
        'state' => 'State',
        'province' => 'Province',
        'postal_code' => 'Postal Code',
        'country_code' => 'Country Code',
        'gender' => 'Gender',
        'date_of_birth' => 'Date of Birth',
        'alt_phone' => 'Alt Phone',
        'email' => 'E-Mail',
        'comments' => 'Comments'
        );

    //-------------------------------------------------------------------------
    //    HELPER FUNCTIONS
    //-------------------------------------------------------------------------
    public function do_post_request($url, $data, $optional_headers = null)
    {
        $params = array('http' => array(
                  'method' => 'POST',
                  'content' => $data
                ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            echo "Problem with $url, $php_errormsg";
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            echo "Problem reading data from $url, $php_errormsg";
        }
        return $response;
    }

    //returns true on error
    //looks in the ini for required fields and checks that they are both submitted and have content
    //adds errors to error list
    private function _check_required()
    {
        $ret = false;

        $required_fields = explode(',',$this->ini['required']);
        foreach($required_fields as $value):
            $value = trim($value);
            //if the value has a bracket AND a name
            if((strpos($value, '[') !== false) AND (strpos($value, '=>') !== false))
            {
                //get the actual value and text
                $value = explode('[', $value);
                //section is: SECTION[...]
                $section = trim($value[0]);
                //KEY => 'VALUE' pair
                $pair = explode('=>', $value[1]);
                //trim any white space from the key
                $key = trim($pair[0]);
                //get rid of the other bracket, the quotes and trim
                $value = trim(str_replace(array(']', '\''), '', $pair[1]));

                //now we have the section, key, and value
                //checking its existence is not that different from below
                if((!isset($this->submission[$section][$key])) OR (!strlen($this->submission[$section][$key])))
                {
                    //if we don't already have an error, set one
                    //unless... we don't have a value like that, then we have to ignore it
                    if(!isset($this->error[$section.'=>'.$key]))
                    {
                        //in this case, value is the text, and key is the 'value' of the field's name
                        //perhaps a little confusing
                        $this->error[$section.'=>'.$key] = $value.' is a required field.';
                        $ret = true;
                    }
                }
            }
            else
            {
                //check to see if it was submitted
                if((!isset($this->submission[$value])) OR (!strlen($this->submission[$value])))
                {
                    //if we don't already have an error, set one
                    //unless... we don't have a value like that, then we have to ignore it
                    if(!isset($this->error[$value]))
                    {
                        if(isset($this->api_to_text[$value]))
                        {
                            $this->error[$value] = $this->api_to_text[$value].' is a required field.';
                            $ret = true;
                        }
                        //we probably shouldn't be able to skip the above if block TODO: add else with error log
                    }
                }
            }
        endforeach;

        return $ret;
    }


    private function _check_formatting()
    {
        //check the formatting of known fields
        foreach($input as $key => $value):
            switch($key):
                case ($key==='first_name'):
                    if(strlen($value) < 2)
                    {
                        $this->errors[] = 'Please enter your first name.';
                    }
                    break;
                case ($key==='last_name'):
                    if(strlen($value) < 2)
                    {
                        $this->errors[] = 'Please enter your last name.';
                    }
                    break;
                case ($key==='address1'):
                    if(strlen($value) == '')
                    {
                        $this->errors[] = 'Please enter your address.';
                    }
                    break;
                case ($key==='city'):
                    if(strlen($value) == '')
                    {
                        $this->errors[] = 'Please enter your city.';
                    }
                    break;
                case ($key==='state'):
                    if(strlen($value) == '')
                    {
                        $this->errors[] = 'Please enter your state.';
                    }
                    break;
                case ($key==='postal_code'):
                    if(strlen($value) == '')
                    {
                        $this->errors[] = 'Please enter your zip code.';
                    }
                    break;
                //sanitize phone numbers
                case ($key==='phone_number'):
                //case ($key==='alt_phone'):
                    //loop through it and maul anything that is not a digit
                    $old = str_split($value);
                    $new = '';
                    foreach($old as $char):
                        //if is within the range of numbers
                        if((ord($char) >= 0x30) && (ord($char) <= 0x39))
                            $new .= $char;
                    endforeach;

                    //now, check that length is at least 10
                    if(strlen($new) < 10)
                    {
                        $this->errors[] = 'Please enter a valid phone number, with area code.';
                    }

                    //add the new string
                    $input[$key] = $new;
                    break;
                case ($key==='email'):
                    if(!$this->_email_valid($value))
                    {
                        $this->errors[] = 'Please enter a valid email.';
                    }
                    break;
                //it is the fake field!
                case ($key===$this->ini['fake']):
                    if(strlen($value))
                    {
                        //there shouldn't be anything in the fake one!
                        $this->errors[] = 'Something bad happened.';
                    }
                    break;
            endswitch;
        endforeach;
    }

    private function _email_valid($temp_email)
    {
        function valid_dot_pos($email) { 
            $str_len = strlen($email); 
            for($i=0; $i<$str_len; $i++) { 
                $current_element = $email[$i]; 
                if($current_element == "." && ($email[$i+1] == ".")) { 
                    return false; 
                    break; 
                } 
                else { 

                } 
            } 
            return true; 
        } 
        function valid_local_part($local_part) { 
            if(preg_match("/[^a-zA-Z0-9-_@.!#$%&'*\/+=?^`{\|}~]/", $local_part)) { 
                return false; 
            } 
            else { 
                return true; 
            } 
        } 
        function valid_domain_part($domain_part) { 
            if(preg_match("/[^a-zA-Z0-9@#\[\].]/", $domain_part)) { 
                return false; 
            } 
            elseif(preg_match("/[@]/", $domain_part) && preg_match("/[#]/", $domain_part)) { 
                return false; 
            } 
            elseif(preg_match("/[\[]/", $domain_part) || preg_match("/[\]]/", $domain_part)) { 
                $dot_pos = strrpos($domain_part, "."); 
                if(($dot_pos < strrpos($domain_part, "]")) || (strrpos($domain_part, "]") < strrpos($domain_part, "["))) { 
                    return true; 
                } 
                elseif(preg_match("/[^0-9.]/", $domain_part)) { 
                    return false; 
                } 
                else { 
                    return false; 
                } 
            } 
            else { 
                return true; 
            } 
        } 
        // trim() the entered E-Mail 
        $str_trimmed = trim($temp_email); 
        // find the @ position 
        $at_pos = strrpos($str_trimmed, "@"); 
        // find the . position 
        $dot_pos = strrpos($str_trimmed, "."); 
        // this will cut the local part and return it in $local_part 
        $local_part = substr($str_trimmed, 0, $at_pos); 
        // this will cut the domain part and return it in $domain_part 
        $domain_part = substr($str_trimmed, $at_pos); 
        if(!isset($str_trimmed) || is_null($str_trimmed) || empty($str_trimmed) || $str_trimmed == "") { 
            return false; 
        } 
        elseif(!valid_local_part($local_part)) { 
            return false; 
        } 
        elseif(!valid_domain_part($domain_part)) { 
            return false; 
        } 
        elseif($at_pos > $dot_pos) { 
            return false; 
        } 
        elseif(!valid_local_part($local_part)) { 
            return false; 
        } 
        elseif(($str_trimmed[$at_pos + 1]) == ".") { 
            return false; 
        } 
        elseif(!preg_match("/[(@)]/", $str_trimmed) || !preg_match("/[(.)]/", $str_trimmed)) { 
            return false; 
        } 
        else {  
            return true; 
        } 
    }

    //-------------------------------------------------------------------------
    //    Main functions
    //-------------------------------------------------------------------------

    //creates new submission with the specified ini section
    //normally just call it like:
    // $dis = new DISubmission();
    // that usually does what you want
    public function DISubmission($ini_section = 'default')
    {
        $this->ini = parse_ini_file('diconfig.ini.php', true);
        if($this->ini === false)
        {
            echo 'Could not load DI\'s configuration!';
            die;
        }
        //get the section, as we are only interested in one
        $this->ini = $this->ini[$ini_section];
        if(!is_array($this->ini))
        {
            echo 'Config does not have a section called: '.$ini_section.'.';
            die;
        }

        $this->errors = array(); //errors starts off empty
    }

    public function try_submit(&$input)
    {
        //we can work with these things
        if(!is_array($input))
        {
            echo 'Input is not an array!';
            die;
        }

        //get the input for other functions' use
        $this->submission = $input;

        //add any errors regarding required submissions
        $this->_check_required();

        //check the formatting

        //input now contains a fully sanitized set of items if return is okay
        if(count($this->errors))
        {
            // everything was not okay, so return with errors
            return $this->errors;
        }

        $default_params = array(
            'dnc_check' => 'YES',
            'duplicate_check' => 'LIST',
            'gmt_lookup_method' => 'POSTAL',
            'add_to_hopper' => 'YES',
            'hopper_priority' => 0,
            'list_id' => $this->ini['list_id'],
            'phone_code' => 1,
            'external_key' => $this->ini['external_key'],
            'cost' => '1.23',
            'post_date' => date('Y-m-d\TH:i:s'),
            'agent' => $this->ini['agent']
        );

        //merge the default items into the input
        $input = array_merge($default_params, $input);

        //buffer the xml to use for submission
        ob_start();
        echo "<?xml version='1.0' standalone='yes'?>\n";
        ?>
        <api mode="admin" function="add_lead" user="<?php echo $this->ini['username']; ?>" pass="<?php echo $this->ini['password']; ?>" test="0" debug="0" vdcompat="0">
            <params>
                <?php
                foreach($input as $key => $value)
                {
                    if(!is_array($value))
                        echo '<'.$key.'>'.htmlentities($value).'</'.$key.">\n";
                    else
                    {
                        //nested arrays are interpreted as additional fields
                        echo "<additional_fields>\n";
                        foreach($value as $k => $v)
                        {
                            echo '<additional_field form="'.$key.'" field="'.$k.'">'.htmlentities($v)."</additional_field>\n";
                        }
                        echo "</additional_fields>\n";
                    }
                } ?>
            </params>
        </api>
        <?php
        $xml = ob_get_clean();

        $out = $this->do_post_request($this->ini['post_url'], http_build_query(array( 'xml' => $xml)));

        //if we have debug set, send the response off
        if(strlen($this->ini['debug']))
            mail($this->ini['debug'], 'DI SUBMIT DEBUG', $out);

        return $this->errors;
    }

    public function get($field = null, $subfield = null)
    {
        if($field != null)
        {
            if($subfield === null)
            {
                return htmlentities($this->submission[$field]);
            }
            else
            {
                return htmlentities($this->submission[$field][$subfield]);
            }
        }
    }
}
?>