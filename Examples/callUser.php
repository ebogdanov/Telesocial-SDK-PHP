<?php
/**
 * 1. Page that lets users put in a facebook ID, register, and place a call to another facebook ID.
 */
require_once('config.php');

// include
require_once('../telesocial.class.php');

if (!empty($_POST)) {
    try {
        $result = $reply = '';
        $telesocial = new Telesocal_API_Connect($ServerHost, $APIkey);
        $telesocial->setByPassSSLCertCheck();

        if ($_POST['action'] == 'register') {
            $result = $telesocial->registerUser($_POST['facebookid'], $_POST['phone']);
        }elseif($_POST['action'] == 'call') {
            $result = $telesocial->createConference($_POST['facebookid']);
        }
        if (is_array($result)) {
            $reply = $telesocial->getLastMessage();
        }
        if (is_array($reply)) {
            echo json_encode(array('type' => 'ok', 'message' => $reply));
        }
    } catch (TelesocialApiException $e) {
        echo json_encode(array('type' => 'error', 'message' => $e->getMessage()));
    }
    exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />   
        <title>Telesocial API call peers example</title>
        <script type="text/javascript" src="http://code.jquery.com/jquery-1.6.2.min.js"></script>
    </head>
    <body>
        <form action="" method="POST" id="form"/>
        <input type="hidden" name="action" id="action" value="register"/>
        <fieldset id="register">
            <span id="message"></span>
            <legend>Register new user</legend>
            <label for="facebookid">Facebook Id:</label> <input type="text" name="facebookid" id="facebookid"/><br/>
            <label for="phone"/>Phone number:</label>    <input type="text" name="phone" id="phone"/><br/>
            <label for="facebookid1" rel="2call" style="display:none"> Facebook id to be called: </label> <input type="text" rel="2call" name="facebookid1" id="facebookid1" style="display:none"/><br/>
            <br/>
            <input type="submit" name="Register" id="submit" rel="button" />
            <input type="reset" name="Clear" rel="button" />
        </fieldset>
        </form>
        <script>
        $(document).ready(function() {
            var action = 'register';
            $('#submit').click(function() {
                $.ajax({
                    url: '<?=$_SERVER['PHP_SELF']?>',
                    dataType: 'json',
                    type: 'POST',
                    data: $('#form').serializeArray(),
                    async: true,
                    success: function(data) {
                        if (data.type == 'ok') {
                            $('#message').html('Success: ' + data.message + '<br/>' ).css('color', 'black');
                            if (action == 'register') {
                                action = 'call';
                                $('#action').val(action);
                                $('[rel=2call]').show();
                            }
                        } else {
                            $('#message').html('Error: ' + data.message + '<br/>' ).css('color', 'red');
                        }
                    }
                });
                return false;
            });
        });
        </script>
    </body>    
</html>