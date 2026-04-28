<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportacaoController extends Controller
{
    public function index()
    {
        return view('admin.importacao.index');
    }

    public function processar(Request $request, CompraService $compraService)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,txt|max:5120',
            'criar_clientes' => 'nullable|boolean',
        ]);

        $empresaId = Auth::user()->empresa_id;
        $criarClientes = $request->boolean('criar_clientes');

        $arquivo = $request->file('arquivo');
        $handle = fopen($arquivo->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Não foi possível abrir o arquivo.');
        }

        $cabecalho = fgetcsv($handle, 0, ',');
        if (!$cabecalho) {
            fclose($handle);
            return back()->with('error', 'Arquivo CSV vazio ou inválido.');
        }

        // Normaliza cabeçalho (lowercase, sem espaços)
        $cabecalho = array_map(fn($c) => strtolower(trim($c)), $cabecalho);

        $obrigatorios = ['telefone', 'valor'];
        foreach ($obrigatorios as $col) {
            if (!in_array($col, $cabecalho)) {
                fclose($handle);
                return back()->with('error', "Coluna obrigatória ausente: '{$col}'. Cabeçalho deve conter: telefone, valor (e opcionais: nome, cpf, descricao, codigo).");
            }
        }

        $sucesso = 0;
        $criados = 0;
        $erros = [];
        $linha = 1;

        DB::transaction(function () use ($handle, $cabecalho, $empresaId, $criarClientes, $compraService, &$sucesso, &$criados, &$erros, &$linha) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $linha++;
                if (count($row) < count($cabecalho)) continue;
                $linhaDados = array_combine($cabecalho, array_slice($row, 0, count($cabecalho)));

                $telefone = trim($linhaDados['telefone'] ?? '');
                $valor = (float) str_replace(',', '.', trim($linhaDados['valor'] ?? '0'));

                if (!$telefone || $valor <= 0) {
                    $erros[] = "Linha {$linha}: telefone ou valor inválido.";
                    continue;
                }

                $cliente = Cliente::where('empresa_id', $empresaId)->where('telefone', $telefone)->first();
                if (!$cliente) {
                    if (!$criarClientes) {
                        $erros[] = "Linha {$linha}: cliente {$telefone} não encontrado (marque 'criar clientes novos').";
                        continue;
                    }
                    $nome = trim($linhaDados['nome'] ?? '');
                    if (!$nome) {
                        $erros[] = "Linha {$linha}: cliente novo precisa de nome.";
                        continue;
                    }
                    $cliente = Cliente::create([
                        'empresa_id' => $empresaId,
                        'nome' => $nome,
                        'telefone' => $telefone,
                        'cpf' => $linhaDados['cpf'] ?? null,
                        'password' => Hash::make(substr(preg_replace('/\D/', '', $telefone), -6)),
                        'aceita_whatsapp' => true,
                    ]);
                    $criados++;
                }

                try {
                    $compraService->registrar($cliente, [
                        'valor' => $valor,
                        'codigo' => $linhaDados['codigo'] ?? null,
                        'descricao' => $linhaDados['descricao'] ?? 'Importado de CSV',
                        'origem' => 'manual',
                        'user_id' => Auth::id(),
                    ]);
                    $sucesso++;
                } catch (\Throwable $e) {
                    $erros[] = "Linha {$linha}: ".$e->getMessage();
                }
            }
        });

        fclose($handle);

        $msg = "Importação concluída: {$sucesso} compra(s) lançada(s)";
        if ($criados > 0) $msg .= ", {$criados} cliente(s) criado(s)";
        if (count($erros) > 0) $msg .= ', '.count($erros).' erro(s).';

        return back()->with('success', $msg)->with('importacao_erros', $erros);
    }
}
