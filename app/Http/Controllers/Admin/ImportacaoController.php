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
    /**
     * Processa em chunks de N linhas com uma transaction por chunk.
     * Em CSVs grandes (10k+ linhas) uma transaction única abria lock
     * estendido em `clientes`/`compras` e bloqueava operação normal do
     * caixa. Chunks pequenos liberam lock entre lotes.
     */
    protected const CHUNK_SIZE = 200;

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
        $chunk = [];

        // Lê todas as linhas e processa em lotes de CHUNK_SIZE com uma
        // transaction por lote. Falha em uma linha não derruba o lote inteiro
        // (cada linha é try/catch independente dentro do processar).
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $linha++;
            if (count($row) < count($cabecalho)) continue;
            $chunk[] = ['linha' => $linha, 'dados' => array_combine($cabecalho, array_slice($row, 0, count($cabecalho)))];

            if (count($chunk) >= self::CHUNK_SIZE) {
                $this->processarChunk($chunk, $empresaId, $criarClientes, $compraService, $sucesso, $criados, $erros);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $this->processarChunk($chunk, $empresaId, $criarClientes, $compraService, $sucesso, $criados, $erros);
        }

        fclose($handle);

        $msg = "Importação concluída: {$sucesso} compra(s) lançada(s)";
        if ($criados > 0) $msg .= ", {$criados} cliente(s) criado(s)";
        if (count($erros) > 0) $msg .= ', '.count($erros).' erro(s).';

        return back()->with('success', $msg)->with('importacao_erros', $erros);
    }

    /**
     * Processa um lote de linhas dentro de uma única transaction.
     */
    protected function processarChunk(
        array $chunk,
        int $empresaId,
        bool $criarClientes,
        CompraService $compraService,
        int &$sucesso,
        int &$criados,
        array &$erros
    ): void {
        DB::transaction(function () use ($chunk, $empresaId, $criarClientes, $compraService, &$sucesso, &$criados, &$erros) {
            foreach ($chunk as $item) {
                $linha = $item['linha'];
                $linhaDados = $item['dados'];

                $telefone = trim($linhaDados['telefone'] ?? '');
                $valor = (float) str_replace(',', '.', trim($linhaDados['valor'] ?? '0'));
                $telefoneDigits = preg_replace('/\D/', '', $telefone);

                // Telefone precisa ter dígitos válidos. Antes só checava
                // truthy — "a", "0", emoji passavam e criavam cliente lixo.
                if (!$telefone || strlen($telefoneDigits) < 10 || strlen($telefoneDigits) > 11 || $valor <= 0) {
                    $erros[] = "Linha {$linha}: telefone ou valor inválido.";
                    continue;
                }
                if ($valor > 99999999.99) {
                    $erros[] = "Linha {$linha}: valor acima do cap (R\$ 99.999.999,99).";
                    continue;
                }

                // Lookup via scopeWhereTelefone (telefone_digits indexado).
                // Antes o where literal `->where('telefone', $telefone)`
                // não casava com cliente já cadastrado em formato diferente
                // (DB tinha "(11) 9..."; CSV mandava "11..." → duplicava).
                $cliente = Cliente::where('empresa_id', $empresaId)
                    ->whereTelefone($telefone)
                    ->first();
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
                    // Anti-XSS: mesma regex dos outros endpoints. Sem isso o
                    // CSV injetaria `<img onerror=...>` no nome, que cai cru
                    // em telas do PWA via innerHTML.
                    if (!preg_match("/^[\p{L}\p{N}\s\.\-\']+$/u", $nome)) {
                        $erros[] = "Linha {$linha}: nome contém caracteres inválidos.";
                        continue;
                    }

                    // CPF (opcional): valida formato + dígitos verificadores
                    // como os outros endpoints. Antes aceitava lixo cru (max
                    // não aplicado) e duplicava no DB com formatação variada.
                    $cpfNorm = null;
                    if (!empty($linhaDados['cpf'])) {
                        $cpfNorm = preg_replace('/\D/', '', $linhaDados['cpf']);
                        $regra = new \App\Rules\CpfValido();
                        $valido = true;
                        $regra->validate('cpf', $cpfNorm, function() use (&$valido) { $valido = false; });
                        if (!$valido) {
                            $erros[] = "Linha {$linha}: CPF inválido.";
                            continue;
                        }
                        if (Cliente::where('empresa_id', $empresaId)->where('cpf', $cpfNorm)->exists()) {
                            $erros[] = "Linha {$linha}: já existe cliente com este CPF.";
                            continue;
                        }
                    }

                    // Senha inicial = últimos 6 dígitos do telefone (UX simples
                    // de explicar). senha_temporaria=true exige troca no primeiro acesso.
                    $cliente = Cliente::create([
                        'empresa_id' => $empresaId,
                        'nome' => $nome,
                        'telefone' => $telefone,
                        'cpf' => $cpfNorm,
                        'password' => Hash::make(substr($telefoneDigits, -6)),
                        'senha_temporaria' => true,
                        'aceita_whatsapp' => true,
                    ]);
                    $criados++;
                }

                // Anti formula injection: campos de texto que viram CSV
                // de novo (extração admin → Excel/LibreOffice) executam
                // fórmula se começam com =, +, -, @ ou TAB. Prefixar com
                // apóstrofe quebra a interpretação como fórmula sem
                // alterar visualmente o texto.
                $descricao = trim($linhaDados['descricao'] ?? '') ?: 'Importado de CSV';
                if (preg_match('/^[=+\-@\t\r]/', $descricao)) {
                    $descricao = "'".$descricao;
                }

                try {
                    // origem='import' faz CompraService pular o disparo
                    // de WhatsApp pos_compra (10k linhas não viram 10k
                    // mensagens "obrigado" enfileiradas no provider).
                    $compraService->registrar($cliente, [
                        'valor' => $valor,
                        'codigo' => $linhaDados['codigo'] ?? null,
                        'descricao' => $descricao,
                        'origem' => 'import',
                        'user_id' => Auth::id(),
                    ]);
                    $sucesso++;
                } catch (\Throwable $e) {
                    $erros[] = "Linha {$linha}: ".$e->getMessage();
                }
            }
        });
    }
}
