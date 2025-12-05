<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DescargasController extends Controller
{
    public function download(Request $request)
    {
        $request->validate([
            'file' => 'required'
        ]);

        if (! Storage::disk('public')->exists($request->file)) {
            abort(404, 'No se encontró el archivo');
        }

        return response()->download(Storage::disk('public')->path($request->file));
    }
}
