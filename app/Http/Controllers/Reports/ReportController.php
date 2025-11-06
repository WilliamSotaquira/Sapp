<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Lógica para mostrar reportes
        return view('reports.index'); // Ajusta según tu vista
    }

    // Otros métodos si los necesitas...
}
