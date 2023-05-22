<?php

namespace Core\HttpClient\Exceptions;

use Psr\Http\Client\ClientExceptionInterface;
use \Exception;

class ClientException extends Exception implements ClientExceptionInterface
{

}
