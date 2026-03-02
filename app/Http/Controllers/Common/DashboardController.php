<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
}
