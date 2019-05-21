<?php
namespace App\Exceptions;
use Exception;

class FacebookException extends Exception
{
    protected $message = 'Facebook refused to authenticate your account';
}