<?php

namespace App\Modules\Exemplo\Http\Controllers;

use App\Http\Controllers\Controller;

class ExemploController extends Controller
{
    public function index()
    {
        return view('exemplo::index');
    }
}
