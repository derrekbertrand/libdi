<?php

define('DI_OKAY', 0);
define('DI_FORMAT', 1);
define('DI_BOT', 2);


//strips entities and submits xml
//input being an array or fields
//errors being an (empty) array of errors
//fake is the $key of the fake field that should not be filled out
function di_submit_xml(&$input, $ini_section='default')
{
//=============================================================================
//    FUNCTIONS
//=============================================================================
    function do_post_request($url, $data, $optional_headers = null)
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
    function email_valid($temp_email) { 
    //######## Three functions to HELP ######## 
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


//=============================================================================
//    DI SUBMIT CODE
//=============================================================================
    $errors = array(); //errors starts off empty
    $ini = parse_ini_file('diconfig.ini.php', true);
    if($ini === false)
    {
        echo 'Could not load DI\'s configuration!';
        die;
    }
    //get the section, as we are only interested in one
    $ini = $ini[$ini_section];
    if(!is_array($ini))
    {
        echo 'Config does not have a section called: '.$ini_section.'.';
        die;
    }

    //we can work with these things
    if(!is_array($input))
    {
        echo 'Input is not an array!';
        die;
    }

    //check and sanitize different things
    foreach($input as $key => $value):
        switch($key):
            case ($key==='first_name'):
            case ($key==='last_name'):
                if(strlen($value) < 2)
                {
                    $errors[] = 'Name fields need to be at least 2 characters long.';
                }
                break;
            case ($key==='email'):
                if(!email_valid($value))
                {
                    $errors[] = 'Please enter a valid email.';
                }
                break;
            //sanitize phone numbers
            case ($key==='phone_number'):
            case ($key==='alt_phone'):
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
                    $errors[] = 'Please make sure that phone numbers are filled out and include area codes.';
                }

                //add the new string
                $input[$key] = $new;
                break;
            //it is the fake field!
            case ($key===$ini['fake']):
                if(strlen($value))
                {
                    //there shouldn't be anything in the fake one!
                    $errors[] = 'Something bad happened.';
                }
                break;
        endswitch;
    endforeach;

    //input now contains a fully sanitized set of items if return is okay
    if(count($errors))
    {
        // everything was not okay, so return with errors
        return $errors;
    }

    $default_params = array(
        'dnc_check' => 'YES',
        'duplicate_check' => 'LIST',
        'gmt_lookup_method' => 'POSTAL',
        'add_to_hopper' => 'YES',
        'hopper_priority' => 0,
        'list_id' => $ini['list_id'],
        'phone_code' => 1,
        'external_key' => $ini['external_key'],
        'cost' => '1.23',
        'post_date' => date('Y-m-d\TH:i:s'),
        'agent' => $ini['agent']
    );

    //merge the default items into the input
    $input = array_merge($default_params, $input);

    //buffer the xml to use for submission
    ob_start();
    echo "<?xml version='1.0' standalone='yes'?>\n";
    ?>
    <api mode="admin" function="add_lead" user="<?php echo $ini['username']; ?>" pass="<?php echo $ini['password']; ?>" test="0" debug="0" vdcompat="0">
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

    $out = do_post_request($ini['post_url'], http_build_query(array( 'xml' => $xml)));

    //if we have debug set, send the response off
    if(strlen($ini['debug']))
        mail($ini['debug'], 'DI SUBMIT TEST', $out);

    return $errors;
}
?>