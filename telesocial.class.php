<?php

/**
 * PHP class which interacts with Telesocial API
 * 
 * Current version of library is developed to support version 01.02.06
 * 
 * Error codes:
 *  100-199: Fatal errors:   Need to check query parameters
 *  200-299: Network errors: Try to re-send query again later
 *  300-399: Server errors:  At this time this feature is not accessible.
 * 
 * @link http://bitmouth.com/Bitmouth-API.html
 * @author Evgeniy Bogdanov
 * @copyright Telesocial.com
 * @since 2011-07-18
 * @version 0.3.0.1
 */
class Telesocial_API_Connect {

    /**
     * Server host
     *
     * @var string
     */
    private $serverHost;

    /**
     * API Key
     *
     * @var string
     */
    private $APIKey;

    /**
     * Proxy host address
     *
     * @var string
     */
    private $proxyHost;

    /**
     * Proxy host port
     *
     * @var integer
     */
    private $proxyPort;

    /**
     * Proxy user authentication
     *
     * @var string
     */
    private $proxyUser;

    /**
     * Proxy password authentication
     *
     * @var string
     */
    private $proxyPassword;

    /**
     * Proxy type
     *
     * @var string (http or socks5)
     */
    private $proxyType;

    /**
     * Flag, which indicates to by pass SSL certificate checks
     *
     * @var string
     */
    private $byPassSSLCertVerification;

    /**
     * Stores last message from API
     *
     * @var string
     */
    private $lastMessage;
    
    /**
     * Constructor
     * 
     * $serverHost and $apiKey ahould be provided to you by support 
     * of telesocial.com
     *
     * @param string $serverHost
     * @param string $APIKey
     */
    public function __construct($serverHost, $APIKey) {
        if (empty($serverHost) || !strpos($serverHost, '.')) {
            throw new TelesocialApiException(
                        'Server Host is not specified', 
                        100);
        } else {
            // Check if server is with URI scheme or not
            $scheme = '';
            $info = parse_url($serverHost);

            if (empty($info['scheme'])) {
                if (empty($info['port']) || ($info['port'] == 443)) {
                    $scheme = 'https://';
                } else {
                    $scheme = 'http://';
                }
                $serverHost = 
                    $scheme . (substr($serverHost, 0, -1) != '/' ? '/' : '');
            }

            $this->serverHost = $serverHost;
        }

        if (empty($APIKey)) {
            throw new TelesocialApiException('API Key is not specified', 100);
        } else {
            $this->APIKey = urlencode($APIKey);
        }
    }

    /**
     * Saves server's host
     *
     * @param string $serverName
     */
    public function setServerName($serverName) {
        $this->serverHost = $serverName;
    }

    /**
     * Saves API (in other words application) key
     *
     * @param string $APIkey
     * @return string
     */
    public function setAPIKey($APIkey) {
        $this->APIKey = urlencode($APIkey);
        return $APIkey;
    }

    /**
     * This is small hack to bypass cURL checks of SSL certs
     *
     * @param bool $checkFlag
     */
    public function setByPassSSLCertCheck($checkFlag = false) {
        $this->byPassSSLCertVerification = (bool) $checkFlag;
    }

    /**
     * Saves proxy information
     * @todo Proxy support in sockets operation
     *
     * @param string $proxyHost
     * @param string $proxyPort
     * @param string $proxyType
     * @param string $proxyUser
     * @param strung $proxyPassword
     */
    public function setProxyCredentials($proxyHost,
                                        $proxyPort,
                                        $proxyType = 'HTTP',
                                        $proxyUser = false,
                                        $proxyPassword = '') {
        $this->proxyHost = $proxyHost;
        if ($proxyPort) {
            $this->proxyPort = $proxyPort;
        }
        $this->proxyType = $proxyType ? strtolower($proxyType) : 'http';

        if ($proxyUser) {
            $this->proxyUser     = $this->proxyUser;
            $this->proxyPassword = $proxyPassword;
        }
    }
    
    /**
     * Returns last error (or success) message from Telesocial API
     *
     * @return unknown
     */
    public function getLastMessage() {
        // Delete our API key from message
        return str_ireplace($this->APIKey, 
                            str_repeat('*', strlen($this->APIKey)), 
                            $this->lastMessage);
    }

    /**
     * Get API Version
     * 
     * Returns the API version in xx.yy.zz format. 
     *
     * @throws Exception if reply from server is not in XX.YY.ZZ format
     * @return string
     */
    public function getVersion() {
        $parts = array();
        // Query server
        $return = $this->sendQuery('api/rest/version', false, false);
        // Analyze reply
        $parts = explode('.', $return);

        if (count($parts) != 3) {
            throw new TelesocialApiException(
                        'Unexpected answer from Telesocial server', 
                        101
                      );
        } else {
            return $return;
        }
    }

    /**
     * Get registration status
     * 
     * Method can be called to determine
     *   - if a network ID has been registered with the Telesocial system
     *   - if a network ID has been registered and is associated with a 
     *     particular application in the Telesocial system.
     * 
     * @param string $networkId
     * @param bool $checkAssociated
     * @return bool or array
     */
    public function checkUserRegistration($networkId, $checkAssociated = true) {
        // Build query URL
        $query  = 'api/rest/registrant/' . urlencode($networkId);
        
        $params  = 'query=' . (!$checkAssociated ? 'exists' : 'related');
        $params .= '&appkey=' . $this->APIKey;
        // Send query to server
        $return = $this->sendQuery($query, $params);
        // Parse reply
        $return = $this->checkResponse($return, 'RegistrantResponse');
        // Return 
        switch ($return['status']) {
            case 200:
                return true;
            case 404:
                return false;
            case 401:
                throw new TelesocialApiException(
                            'Network ID exists but it not associated with the'.  
                            'specified application', 300
                          );
        }
        return false;
    }

    /**
     * User registration.
     * 
     * This method registers a network ID and phone number 
     * pair and relates them to the indicated application. 
     *
     * Returns:
     *      - false:  Any error happened
     *      - array:  User successfully registered - array with user's URI
     * 
     * @param string $networkId
     * @param string $phoneNumber
     * @return bool or array
     */
    public function registerUser($networkId, $phoneNumber) {
        // If we don't get on of our parameters - return false
        if (empty($networkId) || empty($phoneNumber)) {
            throw new TelesocialApiException(
                            'Expected parameters are empty', 100
                      );
        }
        // Delete all not digit symbols in phonenumber
        $phoneNumber = preg_replace('#[^0-9]#', '', $phoneNumber);
        // Build query URL
        $query  = 'appkey=' . $this->APIKey . '&networkid=';
        $query .= urlencode($networkId) . '&phone=' . urlencode($phoneNumber);
        // Send query to server
        $return = $this->sendQuery('api/rest/registrant', $query);
        // Parse reply
        $return = $this->checkResponse($return, 'RegistrationResponse');
        // If we got 201 code - return array for new user, otherwise - false
        if (!is_array($return) || $return['status'] != 201) {
            throw new TelesocialApiException($this->getLastMessage(), 200);
        }
        return  ($return['status'] == 201) ? $return : false;
    }

    /**
     * Creates Conference
     * 
     * Creates a conference call with two or more participants. 
     * Returns:
     *      - false: Query failed
     *      - array: Conference sucessfully created
     * 
     * @param string $networkId The network ID of the conference "leader".
     * @param bool $recordingId The media ID to which the 
     *                          conference audio is to be recorded.
     * @return bool or array
     */
    public function createConference($networkId, 
                                     $recordingId = '', 
                                     $greetingId = '') {
        // Check that we have at least 2 network ids
        if (!empty($networkId)) {
            // Build query
            $query  = 'appkey=' . $this->APIKey;
            // Network id of conference leader
            $query .= '&networkid=' . urlencode($networkId);
            // Specify media ID to which we need record this conference
            if (!empty($recordingId)) {
                $query .= '&recordingid=' . urlencode($recordingId);
            }
            // Specify greeting id if we need to play some greeting
            if (!empty($greetingId)) {
                $query .= '&greetingid=' . urlencode($greetingId);
            }
            
            // Send query to server
            $return = $this->sendQuery('api/rest/conference', $query);
            // Parse reply
            $return = $this->checkResponse($return, 'ConferenceResponse');
            // If we got 201 result code - our results array
            return  ($return['status'] == 201) ? $return : false;
        } else {
            throw new TelesocialApiException('networkId should be set', 102);
        }
    }

    /**
     * Create Media
     * 
     * Returns a Media ID that can be used with 
     * subsequent "record" and "blast" methods. 
     * Returns:    
     *      - false: Any parameters missing
     *      - array: Media ID is successfully created
     *
     * @param $networkId
     * @return bool or array
     */
    public function createMedia($networkId) {
        // Check out network Id for empty value
        if (empty($networkId)) {
            throw new TelesocialApiException(
                        'Network ID should not be blank', 
                        102);
        }
        // Build query
        $query  = 'appkey=' . $this->APIKey;
        // Send query to server
        $return = $this->sendQuery('api/rest/media', $query);
        // Parse reply
        $return = $this->checkResponse($return, 'MediaResponse');
        // Now we need to understand what to do with this result
        switch ($return['status']) {
            case 201: // Return server reply
                return $return;
            case 400: // Return false - some parameters are missing
                return false;
        }
        return false;
    }

    /**
     * Record
     * 
     * Causes the specified networkid to be called and played a 
     * "record greeting" prompt. The status of the recording can subsequently 
     * be determined by calling the "media status" method and supplying 
     * the appropriate Media ID. 
     * Returns:    
     *      - false: if at this time record can't be initiated
     *      - array: if all were good
     *
     * @param string $networkId
     * @param string $mediaId
     * @return bool or array
     */
    public function recordCall($networkId, $mediaId) {
        // Build query
        $query  = 'appkey=' . $this->APIKey;
        $query .= '&action=record&networkid=' . urlencode($networkId);
        // Send query to server
        $return = $this->sendQuery(
                            'api/rest/media/' . urlencode($mediaId), 
                            $query
                        );
        // Parse reply
        $return = $this->checkResponse($return, 'MediaResponse');
        // Now we need to understand that to do and return with this result
        switch ($return['status']) {
            case 201:
                return $return;
            case 400:
                throw new TelesocialApiException('Missing parameter(s)', 102);
            case 500:
                return false;
            case 502:
                if (!empty($return['message'])) {
                    throw new TelesocialApiException($return['message'], 102);
                }
        }
        return false;
    }

    /**
     * Blast
     * 
     * Causes the specified networkid to be called 
     * and played a previously-recorded greeting. 
     * Returns:    
     *      - false: At this time record can't be initiated
     *      - array: All were good
     * 
     * @param string $networkId
     * @param string $mediaId
     * @return bool or array
     */
    public function blastCall($networkId, $mediaId) {
        // If $networkId or $mediaId is blank - return false
        if (empty($networkId) || empty($mediaId)) {
            return false;
        }
        // Build query
        $query  = 'appkey=' . $this->APIKey;
        $query .= '&action=blast&networkid=' . urlencode($networkId);
        // Send query to server
        $return = $this->sendQuery(
                            'api/rest/media/' . urlencode($mediaId), 
                            $query
                         );
        // Parse reply
        $return = $this->checkResponse($return, 'MediaResponse');
        // Now we need to understand that to do and return with this result
        switch ($return['status']) {
            case 201:
                return $return;
            case 400:
                throw new TelesocialApiException('Missing parameter(s)', 102);
            case 500:
                return false;
        }
        return false;
    }

    /**
     * Media Status
     * 
     * Retrieves status information about the Media ID 
     * and the operation in progress, if any. 
     * Returns:
     *      - array: Media have content
     *      - false: Media is blank
     *
     * @param string $mediaId
     * @return bool or array
     */
    public function getMediaStatus($mediaId) {
        if (!empty($mediaId)) {
            throw new TelesocialApiException('Missing parameter(s)', 102);
        }
        // Send query to server
        $return = $this->sendQuery('api/rest/media/' . urlencode($mediaId));
        // Parse reply
        $return = $this->checkResponse($return, 'MediaResponse');
        // Now we need to understand that to do and return with this result
        switch ($return['status']) {
            case 200:
                return $return;
            case 204:
                return false;
        }
        return false;
    }

    /**
     * Add to Conference
     * 
     * Adds one or more additional network 
     * IDs (call legs) to the conference.
     * Returns:
     *      - false: Phone call can't be initiated
     *      - array: The networkid(s) have been added to the conference
     * 
     * 
     * @param mixed $networkIds
     * @param string $conferenceId 
     * @param string $greetingId
     * @return bool or array
     */
    public function addToConference($networkIds, 
                                    $conferenceId, 
                                    $greetingId = '') {
        // Build query
        $query = 'appkey=' . $this->APIKey . '&action=add';

        // If this is array - build out array of network Ids
        if (!is_array($networkIds)) {
            $query .= '&networkid=' . $networkIds;
        } else {
            foreach ($networkIds as $networkId) {
                $query .= '&networkid=' . $networkId;
            }
        }
        if (!empty($greetingId)) {
            $query .= '&greetingid=' . $greetingId;
        }
        // Send query to the server
        $return = $this->sendQuery(
                            'api/rest/conference/' . urlencode($conferenceId), 
                            $query
                         );
        // Parse reply
        $return = $this->checkResponse($return, 'ConferenceResponse');
        // Permorm actions based on code
        switch($return['status']) {
            case 202:
                return $return;
            case 502:
                return false;
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameters.'
                                : $return['message'], 
                            102
                          );
        }
        return false;
    }

    /**
     * Upload Grant Request
     * 
     * Used to request permission to upload a file. To use this method, 
     * the application must first obtain a media ID. When successful, 
     * this method returns a grant code that may be used to perform a 
     * single file upload. The grant code is valid for twenty-four hours 
     * after issuance. 
     * Returns:
     *      - false: The media ID is invalid or is not associated with 
     *               the application identified by the appkey parameter. 
     *      - array: The grant code has been allocated
     * 
     *
     * @param $mediaId
     * @return bool or array
     */
    public function uploadGrantRequest($mediaId) {
        // Check if required parameter is empty
        if (empty($mediaId)) {
            throw new TelesocialApiException(
                            'Missing or invalid parameter', 
                            102);
        }
        // Build query
        $query = 'appkey=' . $this->APIKey . '&action=upload_grant';
        // Send query to the server
        $return = $this->sendQuery(
                            'api/rest/media/' . urlencode($mediaId), 
                            $query
                         );
        // Parse reply
        $return = $this->checkResponse($return, 'UploadResponse');
        // Return value based on response code
        switch ($return['status']) {
            case 201:
                return $return;
            case 400:
                throw new TelesocialApiException(
                            'Missing or invalid parameter', 
                            102
                          );
            case 401:
                return false;
        }
        return false;
    }

    /**
     * Remove Media
     * 
     * Used to request remove a media instance.
     * Returns:
     *      - false: The media ID is invalid.
     *      - array: The media has been removed.
     *
     * @param string $mediaId The ID of the media to be removed
     * @return bool or array
     */
    public function removeMedia($mediaId) {
        // Check if required parameter is empty
        if (empty($mediaId)) {
            throw new TelesocialApiException(
                            'Missing or invalid parameter', 
                            102);
        }
        // Build query
        $query = 'appkey=' . $this->APIKey . '&action=remove';
        // Send query to server
        $return = $this->sendQuery(
                            'api/rest/media/' . urlencode($mediaId), 
                            $query
                         );
        // Parse reply
        $return = $this->checkResponse($return, 'MediaResponse');
        // Return value depending on status code
        switch ($return['status']) {
            case 200:
                return $return;
            case 404:
                return false;
            case 401:
                throw new TelesocialApiException(
                            'The content associated with the media ID cannot ' .
                            'be removed.', 
                            103
                          );
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'], 
                            102
                          );
        }
        return false;
    }

    /**
     * Close Conference
     * 
     * Closes (removes) a conference and 
     * terminates any call legs in progress.
     * Returns:
     *      - false: The conference ID is invalid.
     *      - array: The conference has been closed.
     * 
     * @param string $conferenceID
     * @return bool or array
     */
    public function closeConference($conferenceID) {
        // Check if required variable is empty
        if (empty($conferenceID)) {
            throw new TelesocialApiException(
                        'Missing or invalid parameter', 
                        102);
        }
        // Build query
        $query = 'appkey=' . $this->APIKey . '&action=close';
        // Send query to server
        $return = $this->sendQuery(
                            'api/rest/conference/' . urlencode($conferenceID), 
                            $query
                         );
        // Parse reply
        $return = $this->checkResponse($return, 'ConferenceResponse');
        // Return value
        switch($return['status']) {
            case 200:
                return $return;
            case 404:
                return false;
            case 502:
                throw new TelesocialApiException(
                            'Unable to terminate conference calls.', 200
                           );
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'], 
                            102
                          );
        }
        return false;
    }

    /**
     * Hangup
     * 
     * Terminates the specified conference leg.
     * 
     * @todo To complete
     * 
     * @param string $networkId
     * @param string $conferenceId
     */
    public function hangupCall($networkId, $conferenceId) {
        // Check if one of required params is empty
        if (empty($networkId) || empty($conferenceId)) {
            throw new TelesocialApiException(
                            'Missing parameters.', 
                             102);
        }
        // Build query
        $query = 'appkey=' . $this->APIKey . '&action=hangup';
        // Send query to the server
        $return = $this->sendQuery(
                            'api/rest/conference/' . urlencode($conferenceId) .
                            '/' . urlencode($networkId), 
                            $query
                         );
        // Parse reply
        $return = $this->checkResponse($return, 'ConferenceResponse');
        // Return value based on status code
        switch ($return['status']) {
            case 200:
                return $return;
            case 502:
                return false;
            case 401:
                throw new TelesocialApiException(
                           'The specified network ID is not associated with '.
                           'the application identified by the application key.', 
                           200
                          );
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'], 
                            102
                          );
        }
        return false;
    }

    /**
     * Move
     * 
     * Moves a call leg from one conference to another. 
     *
     * @param $fromConferenceId
     * @param $toConferenceId
     * @param $networkId
     * @return bool or array
     */
    public function moveCall($fromConferenceId, $toConferenceId, $networkId) {
        // Check if one of required params is empty
        if (empty($fromConferenceId) || 
            empty($toConferenceId)   || 
            empty($networkId)) {
            throw new TelesocialApiException(
                            'Missing or invalid parameter', 
                            102);
        }
        // Build query
        $query  = 'appkey=' . $this->APIKey;
        $query .= '&toconferenceid=' . urlencode($toConferenceId);
        $query .= '&action=move';
        // Send query to server
        $return = $this->sendQuery(
                           'api/rest/conference/' .urlencode($fromConferenceId)
                           .'/' . urlencode($networkId), $query
                         );
        // Parse and check reply
        $return = $this->checkResponse($return, 'ConferenceResponse');
        // Return value based on status code
        switch ($return['status']) {
            case 200:
                return $return;
            case 502:
                return false;
            case 401:
                throw new TelesocialApiException(
                           'The specified network ID is not associated with '.
                           'the application identified by the application key.', 
                           200
                          );
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'], 
                            102
                          );
        }
        return false;
    }

    /**
     * Mute
     * 
     * Mutes the specified call leg. 
     * Returns:
     *      - false: Unable to mute call(s). 
     *      - array: The call legs have been muted.
     * 
     * @param string $conferenceID
     * @param string $networkId
     * @return bool or array
     */
    public function muteCall($conferenceId, $networkId) {
        // Check if one of required params is empty
        if (empty($networkId) || empty($conferenceId)) {
            throw new TelesocialApiException(
                        empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'], 
                        102
                      );
        }
        // Build query
        $query  = 'appkey=' . $this->APIKey . '&action=mute';
        // Send query to the server
        $return = $this->sendQuery(
                    'api/rest/conference/' .urlencode($conferenceId) .
                    '/' . urlencode($networkId), 
                    $query
                  );
        // Parse reply
        $return = $this->checkResponse($return, 'ConferenceResponse');
        // Return value based on status code
        switch ($return['status']) {
            case 200: 
                 return $return;
            case 502:
                return false;
            case 401:
                throw new TelesocialApiException(
                           'The specified network ID is not associated with '.
                           'the application identified by the application key.', 
                           200
                          );
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'] , 
                            102
                          );
        }
        return false;
    }

    /**
     * UnMute
     * 
     * UnMutes the specified call leg. 
     * Returns:
     *      - false: Unable to unmute call(s). 
     *      - array: The call legs have been unmuted.
     * 
     * @param string $conferenceID
     * @param string $networkId
     * @return bool or array
     */
    public function unMuteCall($conferenceId, $networkId) {
        if (empty($networkId) || empty($conferenceId)) {
            throw new TelesocialApiException('Missing parameter(s).', 102);
        }
        // Build query
        $query  = 'appkey=' . $this->APIKey . '&action=unmute';
        
        // Send query to the server
        $return = $this->sendQuery(
                    'api/rest/conference/'.urlencode($conferenceId).
                    '/'.urlencode($networkId), 
                    $query
                  );
        // Parse reply
        $return = $this->checkResponse($return, 'ConferenceResponse');
        // Return value based on status code
        switch ($return['status']) {
            case 200: 
                 return $return;
            case 502:
                return false;
            case 401:
                throw new TelesocialApiException(
                           'The specified network ID is not associated with '.
                           'the application identified by the application key.', 
                           200
                          );
            case 400:
                throw new TelesocialApiException(
                            empty($return['message']) 
                                ? 'Missing or invalid parameter(s).'
                                : $return['message'], 
                            102);
        }
        return false;
    }

    /**
     * Undocumented feature.
     * Deletes registration and all associated data with it
     * (Medias, etc).
     * BEAWARE TO USE IT. You will lose data about user on Telesocial server
     * 
     * AGAIN: DO NOT USE THIS FUNCTION IN REAL ENVIORIONMENT. YOU WILL LOOSE ALL
     * DATA FOR SELECTED USER AT TELESOCIAL SERVERS!
     *
     * @return array or false
     */
    public function deleteUser($username) {
        $query  = 'DELETE';
        // Send query to the server
        $return = $this->sendQuery(
                            'api/rest/registrant/'.urlencode($username).
                            '?appkey='.$this->APIKey, 
                            $query
                        );
        // Parse reply
        $return = $this->checkResponse($return, 'RegistrationResponse');
        
        switch ($return['status']) {
            case 200: 
                 return $return;
            default:
                return false;
        }
    }
    
    /**
     * Downloads mp3 file
     *
     * @param string $URI
     * @param string $localPath
     * @return bool
     */
    public function downloadMP3File($URI, $localPath) {
        $file = $this->sendQuery($URI);
        file_put_contents($localPath, $file);
        
        return filesize($localPath);
    }
    
    /**
     * Check response
     *
     * @param mixed $response
     * @param string $checkField
     * @return mixed
     */
    private function checkResponse($response, $checkField) {
        // If we got object -convert it to array, 
        // this will help us to save nerve in operating
        if (is_object($response)) {
            $response = get_object_to_array($response);
        }
        // We expect to get array with needed key
        if (!is_array($response) || 
            !key_exists($checkField, $response) || 
            !key_exists('status', $response[$checkField])) {
                if (is_array($response) && 
                    key_exists('ErrorResponse', $response)) {
                    $checkField = 'ErrorResponse';
                } else {
                    throw new TelesocialApiException(
                                'Unexpected reply from server', 
                                200
                              );
                }
        }
        
        if ($response[$checkField]['status'] == '404') {
            throw new TelesocialApiException('API key is invalid', 100);
        }
        // Save last message
        if (key_exists('message', $response[$checkField])) {
            $this->lastMessage = $response[$checkField]['message'];
        }
        return $response[$checkField];        
    }

    /**
     * Function which perform query to server
     * Can use cURL or sockets to interact with server
     *
     * @param string $url
     * @param string $parameters
     * @param bool $decodeAnswer
     * @return mixed
     */
    private function sendQuery($url, $parameters = '', $decodeAnswer = true) {
        $return = false; 
        $match = array();

        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->serverHost.$url);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Telesocial PHP agent');
            curl_setopt($ch, CURLOPT_HEADER,  true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);

            if ($parameters == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                $parameters = '';
            }
            
            // If we need to not check SSL certs
            if ($this->byPassSSLCertVerification) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
            // If we need to with proxy
            if ($this->proxyHost) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost);
                if ($this->proxyPort) {
                    curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
                }
                curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyType == 'http' 
                                                        ? CURLPROXY_HTTP 
                                                        : CURLPROXY_SOCKS5);

                // Proxy user authentication setting
                if ($this->proxyUser) {
                    curl_setopt($ch, CURLOPT_PROXYAUTH, true);
                    curl_setopt(
                        $ch, 
                        CURLOPT_PROXYUSERPWD, 
                        $this->proxyUser.':'.$this->proxyPassword
                    );
                }
            }
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            
            // curl_setopt($ch, CURLOPT_VERBOSE, 1);

            // If we need to post field
            if (!empty($parameters)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            }
            // Send data to server
            $return = curl_exec($ch);

            if (false != ($errstr = curl_error($ch))) {
                $errno = curl_errno($ch);
            }
            // Close connection
            curl_close($ch);
        } else {
            $protocol = '';
            $regex = '#^((http|https|ssl|sslv2|sslv3)://)\?(.*)$#i';
            if (preg_match($regex, $this->serverHost, $match)) {

                $match[1] = strtolower($match[1]);
                // Determine which protocol and port we should use
                if ($match[1] == '') {
                    $host = $match[2];
                    $port = 80;
                } elseif ($match[1] == 'http') {
                    $host = $match[2];
                    $port = 80;
                } else {
                    // SSL
                    $host  = $match[2];
                    $protocol = ($match[1] == 'https') 
                                                ? 'sslv2://' 
                                                : $match[1].'://';
                    $port = 443;
                }

                $host = str_replace('/', '', $host);
                $fp   = fsockopen($protocol.$host, $port, $errno, $errstr, 10);
                if ($fp != false) {
                    socket_set_timeout($fp, 10);
                    $header = '';
                    if (!empty($parameters)) {
                        $header .= ($parameters != 'DELETE') ? 'POST' : 'DELETE';
                        $parameters = '';
                    } else {
                        $header .= 'GET';
                    }
                    $header .= " /".$url." HTTP/1.1\r\n";
                    $header .= "Host: ".$host."\r\n";
                    $header .= "User-Agent: Telesocial PHP agent\r\n";
                    $header .= "Accept: */*\r\n";
                    // If we need to post data
                    if (!empty($parameters)) {
                        $header .= "Content-Type: application";
                        $header .= "/x-www-form-urlencoded\r\nContent-Length: ";
                        $header .= strlen($parameters)."\r\n";
                        $header .= "\r\n$parameters\r\n";
                    }
                    $header .= "Connection: Close\r\n\r\n";

                    if (!fputs($fp, $header, strlen($header))) {
                        $errstr = "Write error";
                        $errno  = 100;
                    } else {
                        while (!feof($fp)) {
                            $return .= fgets($fp, 1024);
                        }
                        $return = substr(
                                    $return, 
                                    strpos($return, "\r\n\r\n") + 4);
                        fclose($fp);
                    }
                }
            }
        }

        // If there any error - throw exception
        if ($errstr) {
            throw new TelesocialApiException($errstr, $errno);
        }

        if ($decodeAnswer) {
            if (function_exists('json_decode') && !empty($return)) {
                $return = (json_decode($return, true));
            }
        }

        return $return;
    }
}

/**
 * API Exception Class
 *
 */
class TelesocialApiException extends Exception {
    
}

if (!function_exists('json_decode')) {
    /**
     * If we haven't json_decode - create our own dummy function
     *
     * @param string $json
     * @param string $associative
     * @return array
     */
    function json_decode($json, $associative = true) {
        $comment = $out = false;
        if ($associative) {
            $associative = false;
        }

        for ($i=intval($associative), $strlen = strlen($json); $i < $strlen; $i++) {
            if (!$comment) {
                if (($json[$i] == '{') || ($json[$i] == '[')) {
                    $out .= ' array(';
                } elseif (($json[$i] == '}') || ($json[$i] == ']')) {
                    $out .= ')';
                } elseif ($json[$i] == ':') {
                    $out .= '=>';
                } else {
                    $out .= $json[$i];
                }
            } else {
                $out .= $json[$i];
            }
            if (($json[$i] == '"') && ($json[($i-1)] != '\\')) {
                $comment = !$comment;
            }
        }
        eval($out . ';');

        return $out;
    }
}

if (!function_exists('get_object_to_array')) {
    /**
     * Converts object to associative array recursivly
     *
     * @param object $object
     * @return array
     */
    function get_object_to_array($object) {
        if (is_object($object)) {
            $object = get_object_vars($object);
        }
        return is_array($object) ? array_map(__FUNCTION__, $object) : $object;
    }
}