<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $edital->titulo }} - AnjoNexus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <header class="bg-white border-b shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('editais.index') }}" class="text-gray-500 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-700">Painel da Oportunidade</h1>
            </div>
            <a href="{{ $edital->link }}" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2 text-sm shadow-sm">
                Ir para Fonte Oficial <i class="fas fa-external-link-alt text-xs"></i>
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 space-y-8">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md 
                    @if($edital->fonte === 'FAPESC') bg-sky-50 text-sky-700 
                    @elseif($edital->fonte === 'FAPPR') bg-emerald-50 text-emerald-700 
                    @else bg-blue-50 text-blue-700 @endif">
                    {{ $edital->fonte }}
                </span>
                <span class="text-xs text-gray-400">
                    <i class="far fa-calendar-alt mr-1"></i> Coletado em: {{ $edital->created_at->format('d/m/Y') }}
                </span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 leading-tight">{{ $edital->titulo }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <span class="block text-[11px] font-semibold text-gray-400 uppercase">Recurso Global</span>
                    <span class="text-base font-bold text-gray-800">{{ $edital->valor_total ?? 'Consultar' }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div>
                    <span class="block text-[11px] font-semibold text-gray-400 uppercase">Valor Mínimo</span>
                    <span class="text-base font-bold text-gray-800">{{ $edital->valor_minimo_proposta ?? 'Não estipulado' }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div>
                    <span class="block text-[11px] font-semibold text-gray-400 uppercase">Valor Máximo</span>
                    <span class="text-base font-bold text-gray-800">{{ $edital->valor_maximo_proposta ?? 'Variável' }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg {{ $edital->exige_ict ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-500' }} flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-university"></i>
                </div>
                <div>
                    <span class="block text-[11px] font-semibold text-gray-400 uppercase">Exige ICT?</span>
                    <span class="text-base font-bold {{ $edital->exige_ict ? 'text-red-600' : 'text-gray-700' }}">
                        {{ $edital->exige_ict ? 'SIM, Obrigatório' : 'Não exigido' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="fas fa-bullseye text-blue-600"></i> Objetivo do Programa
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                        {{ $edital->objetivo ?: 'Descrição detalhada disponível no documento oficial.' }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-600"></i> Critérios de Elegibilidade
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                        {{ $edital->elegibilidade_detalhada ?? 'As regras completas de proponentes e contrapartidas financeiras devem ser validadas diretamente no termo de referência oficial.' }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-base font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-orange-500"></i> Documentações Solicitadas
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                        {{ $edital->documentacoes_exigidas }}
                    </p>
                </div>

                @if($edital->fonte === 'FAPPR' && str_contains($edital->link, '.pdf'))
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                            <i class="fas fa-file-pdf text-red-500"></i> Visualização Integral do Documento
                        </h3>
                        <iframe src="{{ $edital->link }}" class="w-full h-[550px] rounded-lg border border-gray-200" frameborder="0"></iframe>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-600"></i> Cronograma do Processo
                    </h3>
                    
                    <div class="relative border-l-2 border-gray-100 pl-4 space-y-6 text-sm">
                        <div class="relative">
                            <div class="absolute -left-[23px] top-0.5 bg-gray-300 w-3 h-3 rounded-full border-2 border-white"></div>
                            <span class="block font-bold text-gray-700">Abertura do Edital</span>
                            <span class="text-xs text-gray-400 block mb-1">Início do prazo de submissão de propostas</span>
                            <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs font-semibold">
                                {{ $edital->data_abertura_inscricoes ? \Carbon\Carbon::parse($edital->data_abertura_inscricoes)->format('d/m/Y') : 'Publicado' }}
                            </span>
                        </div>

                        <div class="relative">
                            <div class="absolute -left-[23px] top-0.5 bg-blue-600 w-3 h-3 rounded-full border-2 border-white"></div>
                            <span class="block font-bold text-gray-800">Prazo Final (Até quando submeter)</span>
                            <span class="text-xs text-gray-400 block mb-1">Encerramento definitivo no sistema do órgão</span>
                            <span class="inline-block bg-blue-50 text-blue-700 font-semibold px-2 py-0.5 rounded text-xs">
                                {{ $edital->prazo_submissao ? \Carbon\Carbon::parse($edital->prazo_submissao)->format('d/m/Y') : 'Consultar cronograma' }}
                            </span>
                        </div>

                        <div class="relative">
                            <div class="absolute -left-[23px] top-0.5 bg-yellow-500 w-3 h-3 rounded-full border-2 border-white"></div>
                            <span class="block font-bold text-gray-800">Resultado Preliminar</span>
                            <span class="text-xs text-gray-400 block mb-1">Avaliação e publicação do mérito</span>
                            <span class="inline-block bg-yellow-50 text-yellow-700 font-semibold px-2 py-0.5 rounded text-xs">
                                {{ $edital->resultado_preliminar ? \Carbon\Carbon::parse($edital->resultado_preliminar)->format('d/m/Y') : 'A definir' }}
                            </span>
                        </div>

                        <div class="relative">
                            <div class="absolute -left-[23px] top-0.5 bg-emerald-600 w-3 h-3 rounded-full border-2 border-white"></div>
                            <span class="block font-bold text-gray-800">Homologação Final</span>
                            <span class="text-xs text-gray-400 block mb-1">Convocação para assinatura do termo</span>
                            <span class="inline-block bg-emerald-50 text-emerald-700 font-semibold px-2 py-0.5 rounded text-xs">
                                {{ $edital->resultado_final ? \Carbon\Carbon::parse($edital->resultado_final)->format('d/m/Y') : 'A definir' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

</body>
</html>