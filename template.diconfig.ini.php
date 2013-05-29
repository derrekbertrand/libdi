;<?php die; ?>
;This stops the server from displaying the file

;------------------------------------------------------------------------------
;    This is the default section, more sections can be added if needed
;------------------------------------------------------------------------------
[default]
username="diuser"
password="password"

;the url of our api
post_url="http://127.0.0.1/admin/api.php"

;list information
list_id="0000000000"
external_key="45454545"
agent="1001"

;the fake field is supposed to be submitted as blank
fake="gender"

;debug info:
;if you need to debug, put your email address here and you'll
;be copied on a response from the API
;debug="email@site.com"

;You'll be using the names the API uses. Makes the code cleaner.
;for special fields we can use an array-ish syntax. I think it makes what is required a lot clearer to the coder.
required="first_name, last_name, phone1, ADDITIONAL[FIELDNAME => 'Field Name']"

//-----------------------------------------------------------------------------
//    End of default
//-----------------------------------------------------------------------------
