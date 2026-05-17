@extends('install.layout', ['step' => 'complete'])

@section('title', 'Instalação concluída')

@section('content')
    <div class="text-center py-4">
        <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-4">
            <i class="ri-check-line text-5xl text-emerald-600"></i>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Instalação concluída!</h2>
        <p class="text-slate-500 mb-6">O FidelizaPro está pronto para uso.</p>

        @if($admin_email)
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6 inline-block text-left">
                <p class="text-xs text-slate-500 mb-1">Login do super admin</p>
                <p class="text-slate-800 font-mono">{{ $admin_email }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            <a href="{{ url('/admin/login') }}" class="block p-4 rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 transition">
                <i class="ri-shield-user-line text-2xl text-indigo-600"></i>
                <div class="text-sm font-semibold text-slate-800 mt-1">Painel Admin</div>
                <div class="text-xs text-slate-500">/admin/login</div>
            </a>
            <a href="{{ url('/super') }}" class="block p-4 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 transition">
                <i class="ri-vip-crown-line text-2xl text-purple-600"></i>
                <div class="text-sm font-semibold text-slate-800 mt-1">Super Admin</div>
                <div class="text-xs text-slate-500">/super</div>
            </a>
            <a href="{{ url('/app/') }}" class="block p-4 rounded-lg border border-pink-200 bg-pink-50 hover:bg-pink-100 transition">
                <i class="ri-smartphone-line text-2xl text-pink-600"></i>
                <div class="text-sm font-semibold text-slate-800 mt-1">PWA Cliente</div>
                <div class="text-xs text-slate-500">/app/</div>
            </a>
        </div>

        @php $cronLinha = '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1'; @endphp

        <div class="bg-rose-50 border-2 border-rose-300 text-rose-900 px-4 py-3 rounded-lg text-sm text-left mb-3"
             x-data="{ copiado: false }">
            <p class="font-semibold mb-2"><i class="ri-alarm-warning-line"></i> Importante: configure o cron do scheduler</p>
            <p class="text-rose-800 text-xs mb-2">
                Sem essa linha de cron, <strong>nenhuma tarefa agendada roda</strong>:
                geração de cobranças, notificações WhatsApp, liberação de cashback, etc.
                Se você rodou o <code>install.sh</code> pelo SSH, provavelmente já foi adicionado.
                Caso contrário, cole a linha abaixo via <strong>Sites → Cron Jobs</strong> no painel:
            </p>
            <div class="flex items-stretch gap-2">
                <code class="flex-1 bg-white border border-rose-200 rounded px-2 py-1.5 text-xs font-mono break-all"
                      x-ref="cron">{{ $cronLinha }}</code>
                <button type="button"
                        @click="navigator.clipboard.writeText($refs.cron.innerText); copiado=true; setTimeout(()=>copiado=false,2000)"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-semibold whitespace-nowrap">
                    <i class="ri-file-copy-line"></i>
                    <span x-text="copiado ? 'Copiado!' : 'Copiar'"></span>
                </button>
            </div>
            <p class="text-rose-700 text-xs mt-2">
                Pra validar depois: <code>crontab -l | grep schedule:run</code>
            </p>
        </div>

        @if (!empty($workerCommands))
            @php $blocoWorker = implode("\n", $workerCommands); @endphp
            <div class="bg-amber-50 border-2 border-amber-300 text-amber-900 px-4 py-3 rounded-lg text-sm text-left mb-3"
                 x-data="{ copiado: false }">
                <p class="font-semibold mb-2"><i class="ri-cpu-line"></i> Importante: ative o worker da fila WhatsApp</p>
                <p class="text-amber-800 text-xs mb-2">
                    O arquivo systemd foi gerado em <code class="bg-white px-1 rounded">{{ $workerServicePath }}</code>.
                    Sem o worker rodando, <strong>campanhas WhatsApp ficam empilhadas e não enviam</strong>.
                    Logue como root via SSH e cole os comandos abaixo:
                </p>
                <div class="flex items-stretch gap-2">
                    <pre class="flex-1 bg-white border border-amber-200 rounded px-2 py-1.5 text-xs font-mono whitespace-pre overflow-x-auto"
                         x-ref="worker">{{ $blocoWorker }}</pre>
                    <button type="button"
                            @click="navigator.clipboard.writeText($refs.worker.innerText); copiado=true; setTimeout(()=>copiado=false,2000)"
                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded text-xs font-semibold whitespace-nowrap">
                        <i class="ri-file-copy-line"></i>
                        <span x-text="copiado ? 'Copiado!' : 'Copiar'"></span>
                    </button>
                </div>
                <p class="text-amber-700 text-xs mt-2">
                    Após deploys futuros: <code>sudo systemctl restart fidelizapro-queue</code> pra worker pegar o código novo.
                </p>
            </div>
        @endif

        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm text-left">
            <p class="font-semibold mb-1"><i class="ri-shield-check-line"></i> Outros próximos passos</p>
            <ul class="list-disc list-inside space-y-1 text-amber-700">
                <li>Configure SSL Let's Encrypt no painel do CloudPanel (necessário para o PWA instalável)</li>
                <li>O instalador foi <strong>travado</strong> — para reabrir, delete <code>storage/installed.lock</code> via SSH</li>
            </ul>
        </div>
    </div>
@endsection
