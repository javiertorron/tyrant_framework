<?php

namespace EasyAPI;

use Tyrant\Routers\TyrantRouter;
use Tyrant\Responses\TyrantResponse;
use Tyrant\Exceptions\HttpException;
use Tyrant\Exceptions\TyrantApiException;
use Throwable;

class App {

    public function __construct()
    {
        $this->checkEnv();
    }

    private function checkEnv(): void
    {

        if(empty($_ENV['ROOT_PROJECT'])) {
            throw new TyrantApiException('You must define ROOT_PROJECT environment variable with root path of the project');
        }

        if(!is_dir($_ENV['ROOT_PROJECT'])) {
            throw new TyrantApiException('ROOT_PROJECT environment variable dir not exists');
        }

        $last_char = substr($_ENV['ROOT_PROJECT'], -1);

        if(in_array($last_char, ['/', '\\'])) {
            $_ENV['ROOT_PROJECT'] = substr_replace($_ENV['ROOT_PROJECT'] ,"",-1);
        }

        if(!isset($_ENV['DEBUG_MODE'])) {
            throw new TyrantApiException('You must define DEBUG_MODE environment variable env with value "true" or "false"');
        }

        if(is_bool($_ENV['DEBUG_MODE'])) {
            return;
        }

        if(!is_string($_ENV['DEBUG_MODE'])) {
            throw new TyrantApiException('Invalid format in DEBUG_MODE environment variable, valid values "true" or "false"');
        }

        $debug_mode = strtolower($_ENV['DEBUG_MODE']);
        if($debug_mode === 'true') {
            $_ENV['DEBUG_MODE'] = true;
        }
        elseif($debug_mode === 'false') {
            $_ENV['DEBUG_MODE'] = false;
        }
        else {
            throw new TyrantApiException('Invalid value in DEBUG_MODE environment variable, only valid values "true" or "false"');
        }
    }

    public function send()
    {

        try {
            $route_info = TyrantRouter::getRouteInfo();

            $path_params = $route_info['path_params'];
            $handler = $route_info['handler'];

            if(is_callable($handler) || !preg_match('/@/', $handler)) {
                $response = call_user_func_array($handler, array_values($path_params));
            }
            else {
                list($class, $method) = explode('@', $handler, 2);
                $response = call_user_func_array([new $class, $method], array_values($path_params));
            }

            if(!($response instanceof Response)) {
                throw new TyrantApiException('Invalid response format');
            }

            $response->returnData();
        }
        catch(HttpException $e) {
            $response = new TyrantResponse('json', $e->getMessage(), $e->getCode());
            $response->returnData();
        }
        catch(Throwable $e) {
            if($_ENV['DEBUG_MODE']) {
                throw $e;
            }
            $this->saveLog($e);
            $response = new TyrantResponse('json', 'Internal Server Error', 500);
            $response->returnData();
        }
    }

    private function saveLog($e): void
    {
        $log_dir = $_ENV['ROOT_PROJECT'] . '/logs/';

        if(!is_dir($log_dir)) {
            mkdir($log_dir);
        }

        $data = [
            'DATE' => date('Y-m-d H:i:s'),
            'ENDPOINT' => $_SERVER['REQUEST_URI'] ?? '',
            'METHOD' => $_SERVER['REQUEST_METHOD'] ?? '',
            'MESSAGE_ERROR' => $e->getMessage(),
            'TRACE' => $e->getTrace()[0]
        ];

        $log_file = $log_dir . 'logs-' . date('Y-m-d') . '.log';

        file_put_contents($log_file, json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    }
}