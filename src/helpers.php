<?php
use Tyrant\Responses\TyrantResponse;

if(!function_exists('view')) {
    function view(
        string $type,
        $data = null,
        int $status_code = 200,
        array $headers = []
    ): TyrantResponse
    {
        return new Response($type, $data, $status_code, $headers);
    }
}