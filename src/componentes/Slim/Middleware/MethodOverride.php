<?php

namespace Slim\Middleware;

 /**
  * HTTP Method Override
  *
  * This is middleware for a Slim application that allows traditional
  * desktop browsers to submit pseudo PUT and DELETE requests by relying
  * on a pre-determined request parameter. Without this middleware,
  * desktop browsers are only able to submit GET and POST requests.
  *
  * This middleware is included automatically!
  *
  * @package    Slim
  * @author     Josh Lockhart
  * @since      1.6.0
  */
class MethodOverride extends \Slim\Middleware
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     * @param  array  $settings
     */
    public function __construct($settings = array())
    {
        $this->settings = array_merge(array('key' => '_METHOD'), $settings);
    }

    /**
     * Call
     *
     * Implements Slim middleware interface. This method is invoked and passed
     * an array of environment variables. This middleware inspects the environment
     * variables for the HTTP method override parameter; if found, this middleware
     * modifies the environment settings so downstream middleware and/or the Slim
     * application will treat the request with the desired HTTP method.
     *
     * @return array[status, header, body]
     */
    public function call()
    {
        $env = $this->app->environment();
        if (isset($env['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            // Header commonly used by Backbone.js and others
            $env['slim.method_override.original_method'] = $env['REQUEST_METHOD'];
            $env['REQUEST_METHOD'] = strtoupper($env['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } elseif (isset($env['REQUEST_METHOD']) && $env['REQUEST_METHOD'] === 'POST') {
            // HTML Form Override
            $req = new \Slim\Http\Request($env);
            $method = $req->post($this->settings['key']);
            if ($method) {
                $env['slim.method_override.original_method'] = $env['REQUEST_METHOD'];
                $env['REQUEST_METHOD'] = strtoupper($method);
            }
        }
        $this->next->call();
    }
}
