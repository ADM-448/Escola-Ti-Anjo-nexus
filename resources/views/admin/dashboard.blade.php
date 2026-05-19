<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anjo Nexus - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; color: #2c3e50; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h1 { color: #1b365d; border-bottom: 2px solid #f0f4f8; padding-bottom: 15px; }
        .alert-box { background-color: #e3f2fd; border-left: 5px solid #1b365d; padding: 15px; margin: 20px 0; font-weight: bold; }
        .card { background: #fafbfc; border: 1px solid #e2e8f0; padding: 15px; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Painel de Controle Admin - Margem Exata</h1>
        <div class="alert-box">
            {{ $status['message'] }}
        </div>
        <h2>Status dos Módulos Full-Stack (Nuvem)</h2>
        <div class="card">Banco de Dados: <strong>{{ $status['database'] }}</strong></div>
        <div class="card">Engine Coletora: <strong>{{ $status['spider_engine'] }}</strong></div>
        <div class="card">Infraestrutura Assíncrona: <strong>{{ $status['cloud_workers'] }}</strong></div>
    </div>
</body>
</html>