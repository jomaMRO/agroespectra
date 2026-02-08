<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdentifyController extends Controller
{
    public function index()
    {
        return view('identificarFTIR');
    }

     public function grupos()
    {
        return view('identificarGruposFTIR');
    }
}
