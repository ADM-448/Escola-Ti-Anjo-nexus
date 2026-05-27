<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anjo Nexus - Oportunidades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <header class="bg-white border-b shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-blue-700">Anjo<span class="text-gray-700">Nexus</span></h1>
            <div class="text-sm font-medium text-gray-500">
                <i class="fas fa-database mr-1"></i> {{ $editais->total() }} Oportunidades disponíveis
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
        
     <aside class="w-full md:w-64 flex-shrink-0">
            <div class="sticky top-24">
                <h2 class="font-bold text-lg mb-4 flex items-center">
                    <i class="fas fa-filter mr-2 text-blue-600"></i> Filtros
                </h2>
                
                <form action="{{ route('editais.index') }}" method="GET" class="space-y-6">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif

                    <div>
                        <h3 class="font-semibold text-sm uppercase text-gray-500 mb-3 border-b pb-1">Público Alvo</h3>
                        <div class="space-y-2">
                            @foreach(['Empresa', 'Startup', 'Cooperativa', 'ICT'] as $publico)
                                <label class="flex items-center text-sm cursor-pointer hover:text-blue-600">
                                    <input type="checkbox" 
                                           name="publico[]" 
                                           value="{{ $publico }}" 
                                           class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ is_array(request('publico')) && in_array($publico, request('publico')) ? 'checked' : '' }}>
                                    {{ $publico }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold text-sm uppercase text-gray-500 mb-3 border-b pb-1">Órgão / Fonte</h3>
                        <div class="space-y-2">
                            @foreach(['FINEP', 'FAPESC', 'FAPPR'] as $orgao)
                                <label class="flex items-center text-sm cursor-pointer hover:text-blue-600">
                                    <input type="checkbox" 
                                           name="fonte[]" 
                                           value="{{ $orgao }}" 
                                           class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           {{ is_array(request('fonte')) && in_array($orgao, request('fonte')) ? 'checked' : '' }}>
                                    {{ $orgao }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition shadow-sm">
                        Aplicar Filtros
                    </button>
                </form>
            </div>
        </aside>

        <section class="flex-1">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                @foreach ($editais as $edital)
                    <div class="bg-white rounded-xl shadow-sm border-t-4 
                        @if($edital->fonte === 'FAPESC') border-sky-500 
                        @elseif($edital->fonte === 'FAPPR') border-emerald-600 
                        @else border-blue-600 @endif p-6 flex flex-col justify-between hover:shadow-md transition">                        
                        <div>
                            <div class="flex justify-between items-center mb-3">
                              <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md 
                                @if($edital->fonte === 'FAPESC') bg-sky-50 text-sky-700 
                                @elseif($edital->fonte === 'FAPPR') bg-emerald-50 text-emerald-700 
                                @else bg-blue-50 text-blue-700 @endif">
                                {{ $edital->fonte }}
                              </span>
                                <span class="text-xs text-gray-400">
                                    <i class="far fa-calendar-alt mr-1"></i> 
                                    {{ $edital->data_publicacao ? \Carbon\Carbon::parse($edital->data_publicacao)->format('d/m/Y') : 'Disponível' }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-gray-800 mb-3 line-clamp-2 leading-tight min-h-[3rem]">
                                {{ $edital->titulo }}
                            </h3>

                            <div class="space-y-3 flex-1">
                                <p class="text-sm text-gray-600">
                                    <span class="font-bold text-gray-800">Objetivo:</span> 
                                    {{ $edital->objetivo ? Str::limit($edital->objetivo, 150) : 'Objetivo completo disponível no link oficial.' }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-bold text-blue-600">Público:</span> 
                                    {{ $edital->publico ?: 'Verificar elegibilidade no edital' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center gap-2">
                            <div class="flex items-center gap-4">
                                <a href="{{ route('editais.show', $edital->id) }}" class="text-blue-600 text-sm font-bold hover:text-blue-800 transition flex items-center gap-1">
                                    <i class="fas fa-info-circle text-xs"></i> Ver Detalhes
                                </a>

                                <a href="{{ $edital->link }}" target="_blank" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition flex items-center gap-1 border-l pl-4 border-gray-200">
                                    Link Oficial <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </div>

                            <button class="text-gray-300 hover:text-yellow-500 transition flex-shrink-0">
                                <i class="fas fa-star"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
            </div>

            <div class="mt-12">
                {{ $editais->links() }}
            </div>
        </section>
    </main>

</body>
</html>