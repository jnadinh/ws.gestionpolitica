<?php

namespace Slim\Exception;

/**
 * Stop Exception
 *
 * This Exception is thrown when the Slim application needs to abort
 * processing and return control flow to the outer PHP script.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class Stop extends \Exception
{
}
