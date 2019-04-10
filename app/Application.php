<?php namespace App;

use Laravel\Lumen\Application as BaseApplication;

class Application extends BaseApplication {

    public function map($methods, $uri, $action)
    {
        foreach ($methods as $method) {
            $this->addRoute($method, $uri, $action);
        }
    }
}