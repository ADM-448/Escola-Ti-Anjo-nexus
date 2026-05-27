<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlatformController extends Controller
{
    /**
     * Dashboard Administrativo Web (Laravel Blade + HTML5 + CSS3)
     */
    public function indexWeb()
    {
        $status = [
            'database' => 'Conectado (MySQL)',
            'spider_engine' => 'Pronto (Roach PHP)',
            'cloud_workers' => 'Ativo (Nuvem)',
            'message' => 'Hello World! Ecossistema Anjo Nexus Operacional no Blade.'
        ];

        return view('admin.dashboard', compact('status'));
    }

    /**
     * Endpoint API para o Frontend Mobile (React Native) + Mock do DeepSeek
     */
    public function generatePitchReport(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'origin' => 'Servidor Nuvem (API Laravel 11)',
            'payload' => [
                'message' => 'Hello World do ecossistema de inteligência preditiva!',
                'match_analysis' => [
                    'fit_score' => '94%',
                    'status_edital' => 'Compatível',
                    'pitch_preview' => 'Pronto para integrar com o prompt do DeepSeek.'
                ]
            ]
        ], 200);
    }
}