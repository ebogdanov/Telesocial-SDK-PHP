<?php
// 2. Page that lets users put in a facebook ID, register, and record media, then display the URL.

require_once('config.php');

require_once('../telesocial.class.php');

if (!empty($_POST)) {
    try {
        $result = $reply = '';
        $telesocial = new Telesocial_API_Connect($ServerHost, $APIkey);
        $telesocial->setByPassSSLCertCheck();
        $done = false;

        if ($_POST['action'] == 'register') {
            $result = $telesocial->registerUser($_POST['facebookid'], $_POST['phone']);
        }elseif($_POST['action'] == 'record') {
            $media = $telesocial->createMedia($_POST['facebookid']);
            if (is_array($media)) {
                $result = $telesocial->record($_POST['facebookid'], $media['mediaId']);
                if (is_array($result)) {
                    $reply = 'Answer phone call and tell something';
                }
            }
            // $telesocial->removeMedia();
            // $result = $telesocial->createConference(array($_POST['facebookid'], $_POST['facebookid2']), false);
        }elseif($_POST['action'] == 'status') {
            $media = $telesocial->getMediaStatus($_POST['mediaid']);
            if ($media) {
                if ($media['fileSize'] != 0) {
                    $reply = $media['downloadUrl'];
                    $done = true;
                }
            } else {
                $reply = 'Recording text...';
            }
        }
        if (is_array($result)) {
            $reply = $telesocial->getLastMessage();
            echo json_encode(array('type' => 'ok', 'message' => $reply, 'mediaid' => $media['mediaid'], 'done' => $done));
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
        <title>Telesocial API record call example</title>
        <script type="text/javascript" src="http://code.jquery.com/jquery-1.6.2.min.js"></script>
    </head>
    <body>
        <form action="" method="POST" id="form"/>
        <input type="hidden" name="action" id="action" value="register"/>
        <input type="hidden" name="mediaid" id="mediaid" value=""/>
        <fieldset id="register">
            <span id="message"></span>
            <legend>Register new user</legend>
            <label for="facebookid">Facebook Id:</label> <input type="text" name="facebookid" id="facebookid"/><br/>
            <label for="phone"/>Phone number:</label>    <input type="text" name="phone" id="phone"/><br/>
            <br/>
            <input type="submit" name="Register" id="submit" rel="button" />
            <input type="reset" name="Clear" rel="button" />
        </fieldset>
        </form>
        <script>
        $(document).ready(function() {
            function check_status() {
                $.ajax({
                    url: '<?=$_SERVER['PHP_SELF']?>',
                    dataType: 'json',
                    type: 'POST',
                    data: $('#form').serializeArray(),
                    async: true,
                    success: function(data) {
                        if (data.type == 'ok') {
                            $('#message').html('Success: ' + data.message + '<br/>' ).css('color', 'black');
                            if (data.done) {
                                clearInterval(intervalID);
                            }
                        } else {
                            $('#message').html('Error: ' + data.message + '<br/>' ).css('color', 'red');
                        }
                    }
                });
            }

            var action = 'register';
            $('#action').val(action);
            var intervalID;
            $('#submit').click(function() {
                $.ajax({
                    url: '<?=$_SERVER['PHP_SELF']?>',
                    dataType: 'json',
                    type: 'POST',
                    data: $('#form').serializeArray(),
                    async: true,
                    success: function(data) {
                        if (data.type == 'ok') {
                            if (data.mediaid) {
                                intervalId = setInterval(check_status, 1500);
                            }
                            $('#message').html('Success: ' + data.message + '<br/>' ).css('color', 'black');
                        } else {
                            $('#message').html('Error: ' + data.message + '<br/>' ).css('color', 'red');
                        }
                        if (action == 'register') {
                            $('#mediaid').val(data.mediaid);
                            action = 'record';
                        }else if (action == 'record') {
                            action = 'status';
                        }
                        $('#action').val(action);
                    }
                });
                return false;
            });
        });
        </script>
    </body>    
</html>