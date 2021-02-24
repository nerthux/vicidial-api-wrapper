<?php

namespace Vicidial\Api\Wrapper\Agent;

use Vicidial\Api\Wrapper\Exceptions\InvalidIpException;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Connection
 * @package Api\Wrapper
 */
class Client {
    /**
     * @var string
     */
    private $server_ip;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $api_user;

    /**
     * @var string
     */
    private $api_password;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * Client constructor.
     * @param $server_ip
     * @param $api_user
     * @param $api_password
     * @param $source
     * @param bool $hasSSl
     * @throws InvalidIpException
     */
    public function __construct(
        string $server_ip,
        string $api_user,
        string $api_password,
        string $source,
        bool $hasSSl = true
    ) {
        // Validates if valid IP or resolv hostname WARNING: Not fully tested !!
        if (( filter_var($server_ip, FILTER_VALIDATE_IP ) === false) && ( filter_var(gethostbyname($server_ip), FILTER_VALIDATE_IP) === false ))
        {
            throw new InvalidIpException;
        }

        $this->server_ip = urlencode($server_ip);
        $this->source = urlencode($source ?? 'test');
        $this->api_user = urlencode($api_user);
        $this->api_password = urlencode($api_password);

        $this->base_url = $hasSSl ? 'https://' : 'http://';
        $this->base_url .= $this->server_ip . '/agc/api.php';
        $this->client = new GuzzleClient(['handler' => GuzzleFactory::handler()]);
    }

    /**
     * @param $url
     * @param $options
     * @return string
     * @throws Exception
     */
    public function call_api_url(string $url, array $options)
    {
        if ( filter_var(urldecode($url), FILTER_VALIDATE_URL, FILTER_FLAG_QUERY_REQUIRED) === false )
            throw new Exception("URL may contain malicious code: $url");

        $options += [
            'api_user' => $this->api_user,
            'api_password' => $this->api_password,
            'source' => $this->source
        ];

        try {
            $response = $this->client->get($url, $options);
        } catch (GuzzleException $exception) {
            throw new Exception($exception->getMessage());
        }

        return $response->getBody();
    }

    /**
     * Creates the URL for  the external_hangup method and calls 'call_api_url' to execute it
     *
     * @param $agent_user
     * @return string
     * @throws Exception
     */
    public function hangup(string $agent_user)
    {
        return $this->call_api_url($this->base_url,[
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'external_hangup',
            'value' => '1'
        ]);
    }

    /**
     * Creates the URL for  the external_status method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @param $status
     * @return string
     * @throws Exception
     */
    public function dispo(string $agent_user, string $status)
    {
        return $this->call_api_url($this->base_url,[
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'external_status',
            'value' => urlencode(trim($status))
        ]);
    }

    /**
     * Creates the URL for  the external_pause method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @param $status
     * @return string
     * @throws Exception
     */
    public function pause($agent_user, $status)
    {
        return $this->call_api_url($this->base_url,[
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'external_pause',
            'value' => urlencode(trim($status))
        ]);
    }


    /**
     * Creates the URL for  the pause_code method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @param $code
     * @return string
     * @throws Exception
     */
    public function pause_code($agent_user, $code)
    {
        return $this->call_api_url($this->base_url,[
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'pause_code',
            'value' => urlencode(trim($code))
        ]);
    }

    /**
     * Creates the URL for the webserver method and calls 'call_api_url' to execute it
     * @return string
     * @throws Exception
     */

    public function webserver()
    {
        return $this->call_api_url($this->base_url,[
            'function' => 'webserver'
        ]);
    }

    /**
     * Creates the URL for the version method and calls 'call_api_url' to execute it
     * @return string
     * @throws Exception
     */

    public function version()
    {
        return $this->call_api_url($this->base_url,[
            'function' => 'version'
        ]);
    }

    /**
     * Creates the URL for the logout method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @return string
     * @throws Exception
     */

    public function logout($agent_user)
    {
        return $this->call_api_url($this->base_url,[
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'logout',
            'value' => 'LOGOUT'
        ]);
    }

    /**
     * Creates the URL for  the external_dial method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @param $options
     * @return string
     * @throws Exception
     */

    public function dial($agent_user, $options)
    {
        if (!isset($options['phone_number']) ) {
            throw new Exception("Please provide a valid phone number");
        }

        $options += [
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'external_dial',
            'value' => urlencode(trim($options['phone_numer'])),
            'phone_code' => urlencode(trim($options['phone_code'] ?? '')),
            'search' => 'YES',
            'preview' => 'NO',
            'focues' => 'YES'
        ];

        return $this->call_api_url($this->base_url, $options);
    }

    /**
     * Creates the URL for  the external_dial method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @param $fields_to_update
     * @return string
     * @throws Exception
     */
    public function update_fields($agent_user, $fields_to_update)
    {
        if ( !is_array($fields_to_update)) {
            throw new Exception('Fields must be an array');
        }

        // According to the API documentation only these fields are allowed to update using this method
        $permited_fields = array("address1","address2","address3","rank","owner","vendor_lead_code",
            "alt_phone","city","comments","country_code","date_of_birth","email","first_name",
            "gender","gmt_offset_now","last_name","middle_initial","phone_number","phone_code",
            "postal_code","province","security_phrase","source_id","state","title"
        );

        // Validate that every single field to update us valid
        foreach( $fields_to_update as $key => $value ){
            if ( !in_array($key, $permited_fields))
                throw new Exception("$key is not a valid field");
        }

        $options = $fields_to_update + [
                'agent_user' => urlencode(trim($agent_user)),
                'function' => 'update_fields'
            ];
        return $this->call_api_url($this->base_url,$options);
    }

    /**
     * Creates the URL for  the pause_code method and calls 'call_api_url' to execute it
     * @param $agent_user
     * @param $lead_id
     * @return string
     * @throws Exception
     */
    public function switch_lead($agent_user, $lead_id)
    {
        return $this->call_api_url($this->base_url,[
            'agent_user' => urlencode(trim($agent_user)),
            'function' => 'switch_lead',
            'value' => urlencode(trim($lead_id))
        ]);
    }
}
