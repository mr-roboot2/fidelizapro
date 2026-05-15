<?php

namespace App\Console\Commands;

use App\Models\Cobranca;
use App\Models\ConfiguracaoSistema;
use App\Models\Empresa;
use App\Services\Pix\AsaasPixDriver;
use Illuminate\Console\Command;

class AsaasTestePix extends Command
{
    protected $signature = 'asaas:teste-pix
                            {--empresa-id= : ID da empresa (default: primeira)}
                            {--valor=1.00 : Valor em reais}
                            {--cnpj= : Sobrescreve CNPJ/CPF da empresa (útil pra usar CPF/CNPJ válidos do sandbox)}';

    protected $description = 'Testa AsaasPixDriver contra a sandbox: cria customer, payment PIX, busca QR Code.';

    public function handle(): int
    {
        $cfg = ConfiguracaoSistema::instancia();
        $this->line("Provider:  <info>{$cfg->pix_provider}</info>");
        $this->line("Ambiente:  <info>{$cfg->pix_ambiente}</info>");
        $this->line("Ativo:     <info>".($cfg->pix_ativo ? 'sim' : 'nao')."</info>");
        $this->line("API key:   <info>".($cfg->pix_api_key ? 'preenchida ('.strlen($cfg->pix_api_key).' chars)' : 'VAZIA')."</info>");
        $this->newLine();

        if ($cfg->pix_provider !== 'asaas') {
            $this->error("pix_provider precisa ser 'asaas'. Atual: {$cfg->pix_provider}");
            return self::FAILURE;
        }

        $empresaId = (int) ($this->option('empresa-id') ?: Empresa::min('id'));
        $empresa = Empresa::find($empresaId);
        if (!$empresa) {
            $this->error("Empresa #{$empresaId} não encontrada.");
            return self::FAILURE;
        }
        if ($cnpjOverride = $this->option('cnpj')) {
            $empresa->cnpj = $cnpjOverride;
        }
        $this->line("Empresa:   #{$empresa->id} <info>{$empresa->nome}</info> ({$empresa->cnpj})");

        $cobranca = new Cobranca();
        $cobranca->forceFill([
            'id'         => 999999,
            'empresa_id' => $empresa->id,
            'valor'      => (float) $this->option('valor'),
            'vencimento' => now()->addDay(),
            'status'     => 'pendente',
        ]);
        $this->line("Valor:     R$ ".number_format((float)$cobranca->valor, 2, ',', '.'));
        $this->line("Vence em:  ".$cobranca->vencimento->format('Y-m-d'));
        $this->newLine();

        try {
            $driver = new AsaasPixDriver();
            $r = $driver->gerarPix($cobranca, $empresa);
        } catch (\Throwable $e) {
            $this->error('FALHOU: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info('PIX gerado com sucesso.');
        $this->newLine();
        $this->line("gateway_customer_id: <info>{$r['gateway_customer_id']}</info>");
        $this->line("gateway_charge_id:   <info>{$r['gateway_charge_id']}</info>");
        $this->line("expira em:           <info>".($r['expira_em'] ?? '—')."</info>");
        $this->line("invoiceUrl:          <info>".($r['link_pagamento'] ?? '—')."</info>");
        $this->newLine();
        $this->line('copia-cola PIX:');
        $this->line('<comment>'.($r['copia_cola'] ?? '(sem payload)').'</comment>');
        $this->newLine();
        $this->line('QR code base64 (primeiros 80 chars):');
        $this->line('<comment>'.substr((string)($r['qr_code_base64'] ?? ''), 0, 80).'...</comment>');

        return self::SUCCESS;
    }
}
