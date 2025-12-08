<?php

namespace WeiJuKeJi\LaravelIam\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use WeiJuKeJi\LaravelIam\Http\Controllers\Concerns\RespondsWithApi;

abstract class Controller extends BaseController
{
    use RespondsWithApi;
}
