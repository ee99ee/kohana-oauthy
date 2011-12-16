<?php
/**
 * Grant type is authorization_code
 *
 * Oauth parameter handler for webserver flow
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauthz
 * @copyright   (c) 2010 OALite
 * @license     ISC License (ISCL)
 * @link        http://oalite.com
 * @see         Oauthz_Extension
 * *
 */
class Oauthz_Extension_Authorization_Code extends Oauthz_Extension {

    /**
     * REQUIRED.  The client identifier as described in Section 2.1.
     *
     * @access	public
     * @var		string	$client_id
     */
    public $client_id;

    /**
     * REQUIRED.  The redirection URI used in the initial request.
     *
     * @access	public
     * @var		string	$redirect_uri
     */
    public $redirect_uri;

    /**
     * Load oauth parameters from GET or POST
     *
     * @access	public
     * @param	string	$flag	default [ FALSE ]
     * @return	void
     */
    public function __construct(array $args)
    {
        // Parse the "state" paramter
        if(isset($_POST['state']) AND $state = Oauthz::urldecode($_POST['state']))
        {
            $this->state = $state;
            unset($_POST['state'], $args['state']);
        }

        // Check all required parameters should NOT be empty
        foreach($args as $key => $val)
        {
            if($val === TRUE)
            {
                if(isset($_POST[$key]) AND $value = Oauthz::urldecode($_POST[$key]))
                {
                    $this->$key = $value;
                }
                else
                {
                    $exception = new Oauthz_Exception_Authorize('invalid_request');

                    if(isset($this->state))
                    {
                        $exception->state = $this->state;
                    }

                    throw $exception;
                }
            }
        }
    }

    /**
     * Populate the access token thu the request info and client info stored in the server
     *
     * @access	public
     * @param	array	$client
     * @return	Oauthz_Token
     * @throw   Oauthz_Exception_Authorize    Error Codes: invalid_request, invalid_scope
     */
    public function execute()
    {
        if($client = Model_Oauthz::factory('Token')->oauth_token($this->client_id, $this->code))
        {
            //$audit = new Model_Oauthz_Audit;
            //$audit->audit_token($response);

            // Verify the oauth token send by client
        }
        else
        {
            $exception = new Oauthz_Exception_Token('invalid_client');

            $exception->state = $this->state;

            throw $exception;
        }

        if($client['redirect_uri'] !== $this->redirect_uri)
        {
            $exception = new Oauthz_Exception_Token('unauthorized_client');

            $exception->state = $this->state;

            throw $exception;
        }

        if($client['client_secret'] !== sha1($this->client_secret))
        {
            $exception = new Oauthz_Exception_Token('unauthorized_client');

            $exception->error_uri = $this->redirect_uri;

            $exception->state = $this->state;

            throw $exception;
        }

        if( ! empty($this->scope) AND ! empty($client['scope']))
        {
            if( ! in_array($this->scope, explode(' ', $client['scope'])))
            {
                $exception = new Oauthz_Exception_Authorize('invalid_scope');

                $exception->error_uri = $this->redirect_uri;

                $exception->state = $this->state;

                throw $exception;
            }
        }

        // Everything is ok, then return the token
        $response = new Oauthz_Token;

        $response->token_type       = $client['token_type'];
        $response->access_token     = $client['access_token'];
        $response->refresh_token    = $client['refresh_token'];
        $response->expires_in       = (int) $client['expires_in'];

        // merge other token properties, e.g. {"mac_key":"adijq39jdlaska9asud","mac_algorithm":"hmac-sha-256"}
        if($client['option'] AND $option = json_decode($client['option'], TRUE))
        {
            foreach($option as $key => $val)
            {
                $response->$key = $val;
            }
        }

        return $response;
    }

} // END Oauthz_Extension_Authorization_Code
