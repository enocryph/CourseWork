<?php

namespace AppBundle\Service;

class TokenGenerator
{
    public function generateToken()
    {
        $length = 16;
        $token = bin2hex(random_bytes($length));
        return $token;
    }
}