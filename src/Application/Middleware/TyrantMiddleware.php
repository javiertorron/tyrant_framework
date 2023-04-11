<?php

namespace Tyrant\Middlewares;
use Tyrant\Request;

abstract class TyrantMiddleware
{
    abstract public function handle(Request $request): Request;
}