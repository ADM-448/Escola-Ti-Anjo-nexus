<?php

namespace App\Http\Controllers;

use App\Models\Edital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditalController extends Controller
{
    /**
     * Lista editais para a WEB (Blade) com Filtros Combinados de Público e Órgão
     * GET /editais
     */
    public function index(Request $request): View
    {
        $query = Edital::query();

        // 1. Filtro por busca de texto (Título ou Objetivo)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                    ->orWhere('objetivo', 'like', '%' . $request->search . '%');
            });
        }

        // 2. Filtro por Público Alvo (Busca Semântica por aproximação)
        if ($request->filled('publico') && is_array($request->publico)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->publico as $index => $publicoSelecionado) {
                    if ($index === 0) {
                        $q->where('publico', 'like', '%' . $publicoSelecionado . '%');
                    } else {
                        $q->orWhere('publico', 'like', '%' . $publicoSelecionado . '%');
                    }
                }
                $q->orWhere('publico', 'like', '%Verificar elegibilidade%');
            });
        }

        // 3. NOVO: Filtro por Órgão / Fonte (Busca exata por conjunto com whereIn)
        if ($request->filled('fonte') && is_array($request->fonte)) {
            $query->whereIn('fonte', $request->fonte);
        }

        // 4. Paginação de 12 em 12 ordenada pelos mais recentes
        $editais = $query->latest('data_publicacao')->paginate(12)->withQueryString();

        return view('editais.index', compact('editais'));
    }
    /**
     * MANTIDO: Lista editais para a API (React Native)
     * GET /api/editais
     */
    public function apiIndex(): JsonResponse
    {
        // Aqui também é bom usar paginação no futuro pro app não travar
        $editais = Edital::latest()->get();

        return response()->json([
            'success' => true,
            'total' => $editais->count(),
            'data' => $editais,
        ]);
    }

    /**
     * Exibe os detalhes de um edital específico
     * GET /editais/{id}
     */
    public function show($id): View
    {
        // Busca o edital ou estoura um erro 404 caso o ID não exista
        $edital = Edital::findOrFail($id);

        return view('editais.show', compact('edital'));
    }

}