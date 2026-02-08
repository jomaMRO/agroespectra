<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Aquí puedes retornar una vista, por ejemplo:
        return view('index');  // Asegúrate de que la vista 'home.blade.php' exista en resources/views
    }
}