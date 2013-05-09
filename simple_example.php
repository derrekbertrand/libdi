<?php
//remember to rename the template config to:
// diconfig.ini.php
//and remember to set your values
//include the DI library
require_once('libdi.inc.php');

//if we are processing a submit
if(isset($_POST['submit']))
{
    //gather the fields into an array
    $fields = array(
        'first_name' => $_POST['fname'],
        'last_name' => $_POST['lname'],
        'phone_number' => $_POST['number']
    );
    
    //try sending to DI's dialer API
    $dis = new DISubmission();

    //attempt to submit the fields
    $errors = $dis->try_submit($fields);

    //if we have errors
    if(count($errors))
    {
        ?>
        <h1>Sorry!</h1>
        <p>Please correct the following: </p>
        <?php
        //display the errors
        foreach($errors as $err)
            echo $err."<br />\n";
    }
    else
    {
        ?>
        <h1>Thank You</h1>
        <?php
        //we're done
        exit;
    }
}
//we're just showing a form
?>
<html>
    <head>
        <title>Simple Example</title>
    </head>
    <body>
        <form method="POST">
            First Name: </br>
            <input type="text" name="fname" /><br /> <br />
            Last Name: </br>
            <input type="text" name="lname" /><br /> <br />
            Phone: </br>
            <input type="text" name="number" /><br /> <br />
            <input type="submit" name="submit"/>
        </form>
    </body>
</html>