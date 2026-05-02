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

        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm text-left">
            <p class="font-semibold mb-1"><i class="ri-shield-check-line"></i> Próximos passos de segurança</p>
            <ul class="list-disc list-inside space-y-1 text-amber-700">
                <li>Configure SSL Let's Encrypt no painel do CloudPanel (necessário para o PWA instalável)</li>
                <li>Adicione o cron <code>* * * * * php artisan schedule:run</code> em <strong>Sites &rarr; Cron Jobs</strong></li>
                <li>O instalador foi <strong>travado</strong> — para reabrir, delete <code>storage/installed.lock</code> via SSH</li>
            </ul>
        </div>
    </div>
@endsection
