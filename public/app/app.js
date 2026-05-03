// ============ CONFIG ============
// Detecta se está em white label (URL com /app/{slug}/)
const WHITELABEL_SLUG = (() => {
    const m = location.pathname.match(/\/app\/([^\/]+)\/?/);
    return m ? m[1] : null;
})();

// Calcula a base do projeto (ex: '/fidelizapro/public') a partir da URL atual.
const APP_BASE = location.pathname.replace(/\/app(\/[^\/]+)?\/?.*$/, '');
const API = APP_BASE + '/api/v1';

const STATE = {
    token: localStorage.getItem('fp_token'),
    cliente: JSON.parse(localStorage.getItem('fp_cliente') || 'null'),
    empresa: window.PRELOAD_EMPRESA || JSON.parse(localStorage.getItem('fp_empresa') || 'null'),
};

// White label: persiste a empresa preloaded no localStorage
if (WHITELABEL_SLUG && window.PRELOAD_EMPRESA) {
    localStorage.setItem('fp_empresa', JSON.stringify(window.PRELOAD_EMPRESA));
}

// ============ HELPERS ============
const $ = (sel) => document.querySelector(sel);
const screenContainer = $('#screen-container');

function toast(msg, tipo = 'info') {
    const t = $('#toast');
    t.textContent = msg;
    t.classList.remove('hidden', 'bg-emerald-600', 'bg-rose-600', 'bg-slate-900');
    t.classList.add(tipo === 'success' ? 'bg-emerald-600' : tipo === 'error' ? 'bg-rose-600' : 'bg-slate-900');
    setTimeout(() => t.classList.add('hidden'), 2800);
}

async function api(path, opts = {}) {
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (STATE.token) headers['Authorization'] = 'Bearer ' + STATE.token;
    const res = await fetch(API + path, { ...opts, headers: { ...headers, ...(opts.headers || {}) } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || data.errors?.[Object.keys(data.errors)[0]]?.[0] || 'Erro de comunicação');
    return data;
}

function fmtBRL(v) { return 'R$ ' + Number(v || 0).toFixed(2).replace('.', ','); }
function fmtNum(v) { return Number(v || 0).toLocaleString('pt-BR'); }

function formatarTelefone(v) {
    v = String(v || '').replace(/\D/g, '').slice(0, 11);
    if (!v) return '';
    if (v.length <= 2) return '(' + v;
    if (v.length <= 6) return '(' + v.slice(0, 2) + ') ' + v.slice(2);
    if (v.length <= 10) return '(' + v.slice(0, 2) + ') ' + v.slice(2, 6) + '-' + v.slice(6);
    return '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
}

// Aplica máscara em todos inputs de telefone (login, OTP, registrar, indicação)
document.addEventListener('input', (ev) => {
    const el = ev.target;
    if (!el.matches || !el.matches('input[name="telefone"], input[name="telefone_indicado"], #otp-tel')) return;
    const before = el.value;
    const formatted = formatarTelefone(before);
    if (formatted !== before) {
        el.value = formatted;
        // Joga o cursor pro fim — simples e robusto pra colagem
        el.setSelectionRange(formatted.length, formatted.length);
    }
});

function persistir() {
    if (STATE.token) localStorage.setItem('fp_token', STATE.token); else localStorage.removeItem('fp_token');
    if (STATE.cliente) localStorage.setItem('fp_cliente', JSON.stringify(STATE.cliente)); else localStorage.removeItem('fp_cliente');
    if (STATE.empresa) localStorage.setItem('fp_empresa', JSON.stringify(STATE.empresa)); else localStorage.removeItem('fp_empresa');
}

function aplicarTemaEmpresa() {
    if (STATE.empresa) {
        document.documentElement.style.setProperty('--cor-primaria', STATE.empresa.cor_primaria);
        document.documentElement.style.setProperty('--cor-secundaria', STATE.empresa.cor_secundaria);
        document.querySelector('meta[name="theme-color"]').setAttribute('content', STATE.empresa.cor_primaria);
    }
}

// ============ ROTEAMENTO ============
function setNavActive(nome) {
    document.querySelectorAll('.nav-btn').forEach((b, i) => {
        b.classList.toggle('active', ['home','compras','catalogo','qrcode','perfil'][i] === nome);
    });
}

async function showScreen(nome, params = {}) {
    // White label: a empresa está fixada na URL, então nunca mostra o seletor.
    if (WHITELABEL_SLUG && nome === 'escolherEmpresa') {
        nome = STATE.token ? 'home' : 'login';
    }
    if (!STATE.token && !['login','loginOtp','registrar','escolherEmpresa'].includes(nome)) {
        // White label vai direto pra login (já tem empresa fixada)
        nome = WHITELABEL_SLUG ? 'login' : 'escolherEmpresa';
    }
    setNavActive(nome);
    $('#bottom-nav').classList.toggle('hidden', !STATE.token);

    const fns = {
        escolherEmpresa: telaEscolherEmpresa,
        login: telaLogin,
        loginOtp: telaLoginOtp,
        registrar: telaRegistrar,
        home: telaHome,
        compras: telaCompras,
        catalogo: telaCatalogo,
        qrcode: telaQrCode,
        perfil: telaPerfil,
        editarPerfil: telaEditarPerfil,
        alterarSenha: telaAlterarSenha,
        empresa: telaEmpresa,
        extrato: telaExtrato,
        resgates: telaResgates,
        indicacoes: telaIndicacoes,
        pesquisa: telaPesquisa,
        parceiros: telaParceiros,
        meusCupons: telaMeusCupons,
    };
    screenContainer.innerHTML = '<div class="flex-1 flex items-center justify-center"><i class="ri-loader-4-line animate-spin text-3xl text-slate-400"></i></div>';
    try {
        await fns[nome](params);
    } catch (e) {
        toast(e.message, 'error');
        if (e.message.toLowerCase().includes('unauthenticated') || e.message.includes('401')) {
            STATE.token = null; STATE.cliente = null; persistir();
            showScreen('escolherEmpresa');
        }
    }
}

// ============ TELAS ============

// Tela 0: escolher empresa
async function telaEscolherEmpresa() {
    const data = await api('/empresas');
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col">
        <div class="p-6 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 text-white">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-white/20 flex items-center justify-center text-3xl font-bold mb-3">F</div>
            <h1 class="text-center text-2xl font-bold">FidelizaPro</h1>
            <p class="text-center text-white/80 text-sm mt-1">Escolha onde você compra</p>
        </div>
        <div class="flex-1 p-4 space-y-3">
            ${data.empresas.map(e => `
                <button onclick='selecionarEmpresa(${JSON.stringify(e)})'
                        class="w-full flex items-center gap-3 p-4 bg-white border border-slate-200 rounded-xl hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg" style="background:${e.cor_primaria}">
                        ${e.logo ? `<img src="${e.logo}" class="w-full h-full object-cover rounded-xl">` : e.nome.charAt(0)}
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-semibold">${e.nome}</p>
                        <p class="text-xs text-slate-500">Toque para acessar</p>
                    </div>
                    <i class="ri-arrow-right-s-line text-2xl text-slate-400"></i>
                </button>
            `).join('')}
        </div>
    </div>`;
}

window.selecionarEmpresa = (e) => {
    STATE.empresa = e;
    persistir();
    aplicarTemaEmpresa();
    showScreen('login');
};

// Tela 1: Login
async function telaLogin() {
    const e = STATE.empresa;
    const cor = e?.cor_primaria || '#6366f1';
    const corSec = e?.cor_secundaria || '#8b5cf6';
    const semWhitelabel = !WHITELABEL_SLUG;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col">
        <div class="p-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            ${semWhitelabel ? `<button onclick="showScreen('escolherEmpresa')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition"><i class="ri-arrow-left-line"></i> Trocar empresa</button>` : ''}
            ${e?.logo
                ? `<img src="${e.logo}" alt="${e.nome}" class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur p-2 mb-3 object-contain">`
                : `<div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl font-bold mb-3">${(e?.nome || 'F')[0]}</div>`
            }
            <h1 class="text-2xl font-bold">${e?.nome || 'FidelizaPro'}</h1>
            <p class="text-white/80 text-sm">Acesse seu programa de fidelidade</p>
        </div>
        <form id="form-login" class="p-6 -mt-4 bg-white rounded-t-3xl space-y-4 flex-1">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone</label>
                <div class="relative">
                    <i class="ri-smartphone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="telefone" required placeholder="(11) 99999-9999"
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Senha</label>
                <div class="relative">
                    <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="password" type="password" required placeholder="Sua senha"
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
            </div>

            <button class="w-full py-3.5 text-white rounded-xl font-semibold shadow-md hover:shadow-xl transition flex items-center justify-center gap-2"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                Entrar <i class="ri-arrow-right-line"></i>
            </button>

            <div class="relative my-2">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                <div class="relative flex justify-center text-xs"><span class="bg-white px-3 text-slate-400 uppercase tracking-wide">ou</span></div>
            </div>

            <button type="button" onclick="showScreen('loginOtp')"
                    class="w-full py-3 border-2 border-emerald-500 text-emerald-600 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-emerald-50 transition">
                <i class="ri-whatsapp-line text-xl"></i> Entrar com WhatsApp
            </button>

            <p class="text-center text-sm text-slate-500 pt-3">
                Novo por aqui? <a onclick="showScreen('registrar')" class="font-semibold cursor-pointer hover:underline" style="color:${cor}">Criar conta</a>
            </p>

            <p class="text-center text-[11px] text-slate-400 pt-3 mt-2 border-t border-slate-100">
                <a href="/termos-de-uso" target="_blank" class="hover:underline">Termos de uso</a>
                &middot;
                <a href="/politica-privacidade" target="_blank" class="hover:underline">Política de privacidade</a>
            </p>
        </form>
    </div>`;
    $('#form-login').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        try {
            const res = await api('/auth/login', {
                method: 'POST',
                body: JSON.stringify({ ...fd, empresa_slug: STATE.empresa.slug }),
            });
            STATE.token = res.token;
            STATE.cliente = res.cliente;
            STATE.empresa = res.empresa;
            persistir();
            aplicarTemaEmpresa();
            toast('Bem-vindo, '+ res.cliente.nome.split(' ')[0] + '!', 'success');
            showScreen('home');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// Tela 1.5: Login por OTP via WhatsApp
async function telaLoginOtp() {
    const e = STATE.empresa;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,#10b981,#059669)">
            <button onclick="showScreen('login')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl mb-3">
                <i class="ri-whatsapp-line"></i>
            </div>
            <h1 class="text-2xl font-bold">Entrar com WhatsApp</h1>
            <p class="text-white/80 text-sm mt-1">Receba um código de 6 dígitos no seu WhatsApp</p>
        </div>

        <div id="otp-fase-1" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone com DDD</label>
                    <div class="relative">
                        <i class="ri-smartphone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="otp-tel" type="tel" required placeholder="(11) 99999-9999"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-400 focus:outline-none transition">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 ml-1">O número precisa estar cadastrado nesta empresa</p>
                </div>

                <button onclick="solicitarOtp()" id="otp-btn-solicitar"
                        class="w-full py-3.5 text-white rounded-xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                        style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="ri-send-plane-line"></i> Enviar código
                </button>
            </div>
        </div>

        <div id="otp-fase-2" class="px-4 -mt-6 pb-6 hidden">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <p class="text-sm text-slate-700 text-center">
                    Código enviado para <strong id="otp-tel-show" class="text-emerald-600"></strong>
                </p>

                <input id="otp-codigo" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                       class="w-full px-4 py-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-center text-3xl font-mono tracking-[0.5em] focus:bg-white focus:border-emerald-400 focus:outline-none transition">

                <button onclick="validarOtp()"
                        class="w-full py-3.5 text-white rounded-xl font-semibold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
                        style="background:linear-gradient(135deg,#10b981,#059669)">
                    <i class="ri-check-line"></i> Confirmar código
                </button>

                <div class="flex justify-between text-sm pt-1 border-t border-slate-100 -mx-1">
                    <button onclick="resetOtp()" class="text-slate-500 hover:text-slate-700 px-2 pt-3">
                        <i class="ri-arrow-left-line"></i> Trocar telefone
                    </button>
                    <button onclick="solicitarOtp(true)" id="otp-btn-reenviar" class="text-emerald-600 font-semibold hover:underline px-2 pt-3">
                        Reenviar
                    </button>
                </div>

                <p id="otp-dev" class="text-xs text-amber-600 text-center font-mono"></p>
            </div>
        </div>
    </div>`;
}

window.solicitarOtp = async (reenviar = false) => {
    const tel = $('#otp-tel').value.trim();
    if (!tel) return toast('Informe o telefone', 'error');

    const btnReenviar = $('#otp-btn-reenviar');
    if (reenviar && btnReenviar?.disabled) return; // já em cooldown

    try {
        const res = await api('/auth/otp/solicitar', {
            method: 'POST',
            body: JSON.stringify({ telefone: tel, empresa_slug: STATE.empresa.slug }),
        });
        if (!reenviar) {
            $('#otp-fase-1').classList.add('hidden');
            $('#otp-fase-2').classList.remove('hidden');
            $('#otp-tel-show').textContent = tel;
            setTimeout(() => $('#otp-codigo').focus(), 100);
        } else {
            toast('Código reenviado!', 'success');
        }
        if (res.codigo_dev) {
            $('#otp-dev').textContent = `🧪 Modo dev: código = ${res.codigo_dev}`;
        }
        iniciarCooldownReenvio(30);
    } catch (e) { toast(e.message, 'error'); }
};

function iniciarCooldownReenvio(segundos) {
    const btn = $('#otp-btn-reenviar');
    if (!btn) return;
    let restante = segundos;
    const textoOriginal = 'Reenviar';
    btn.disabled = true;
    btn.classList.add('text-slate-400', 'cursor-not-allowed');
    btn.classList.remove('text-emerald-600', 'hover:underline');

    const tick = () => {
        if (restante <= 0) {
            btn.disabled = false;
            btn.textContent = textoOriginal;
            btn.classList.remove('text-slate-400', 'cursor-not-allowed');
            btn.classList.add('text-emerald-600', 'hover:underline');
            return;
        }
        btn.textContent = `Reenviar em ${restante}s`;
        restante--;
        setTimeout(tick, 1000);
    };
    tick();
}

window.validarOtp = async () => {
    const tel = $('#otp-tel').value.trim();
    const codigo = $('#otp-codigo').value.trim();
    if (codigo.length !== 6) return toast('Código deve ter 6 dígitos', 'error');
    try {
        const res = await api('/auth/otp/validar', {
            method: 'POST',
            body: JSON.stringify({ telefone: tel, codigo, empresa_slug: STATE.empresa.slug }),
        });
        STATE.token = res.token;
        STATE.cliente = res.cliente;
        STATE.empresa = res.empresa;
        persistir();
        aplicarTemaEmpresa();
        toast('Bem-vindo de volta!', 'success');
        showScreen('home');
    } catch (e) { toast(e.message, 'error'); }
};

window.resetOtp = () => {
    $('#otp-fase-1').classList.remove('hidden');
    $('#otp-fase-2').classList.add('hidden');
    $('#otp-codigo').value = '';
    $('#otp-dev').textContent = '';
};

// Tela 2: Registrar
async function telaRegistrar() {
    const e = STATE.empresa;
    const cor = e?.cor_primaria || '#6366f1';
    const corSec = e?.cor_secundaria || '#8b5cf6';
    const params = new URLSearchParams(location.search);
    const ref = params.get('ref') || '';
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col">
        <div class="p-6 pb-8 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('login')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Criar conta</h1>
            <p class="text-white/80 text-sm">${e?.nome || 'FidelizaPro'}</p>
        </div>
        <form id="form-reg" class="p-6 -mt-4 bg-white rounded-t-3xl space-y-5 flex-1 overflow-y-auto">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome completo <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <i class="ri-user-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="nome" required placeholder="Como você quer ser chamado"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <i class="ri-smartphone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="telefone" required placeholder="(11) 99999-9999"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                    <p class="text-xs text-slate-500 mt-1 ml-1">Usado para login e notificações</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Senha <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="password" type="password" required minlength="6" placeholder="Mínimo 6 caracteres"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Informações adicionais</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                        <div class="relative">
                            <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input name="email" type="email" placeholder="seu@email.com"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Data de nascimento</label>
                        <div class="relative">
                            <i class="ri-cake-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                            <input name="data_nascimento" type="date"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition text-slate-700">
                        </div>
                        <p class="text-xs text-slate-500 mt-1 ml-1">Receba um presente no seu aniversário</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Código de indicação</label>
                        <div class="relative">
                            <i class="ri-gift-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input name="codigo_indicacao" value="${ref}" placeholder="Quem te indicou?"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 text-white rounded-xl font-semibold shadow-md hover:shadow-xl transition mt-2 flex items-center justify-center gap-2"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                Criar minha conta <i class="ri-arrow-right-line"></i>
            </button>

            <p class="text-center text-xs text-slate-400 leading-relaxed pb-2">
                Ao criar conta, você concorda em receber<br>comunicações sobre o programa de fidelidade.
            </p>
        </form>
    </div>`;
    $('#form-reg').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        Object.keys(fd).forEach(k => { if (fd[k] === '') delete fd[k]; });
        try {
            const res = await api('/auth/registrar', {
                method: 'POST',
                body: JSON.stringify({ ...fd, empresa_slug: STATE.empresa.slug }),
            });
            STATE.token = res.token; STATE.cliente = res.cliente; STATE.empresa = res.empresa;
            persistir(); aplicarTemaEmpresa();
            toast('Cadastro realizado!', 'success');
            showScreen('home');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// Tela 3: HOME
async function telaHome() {
    const data = await api('/cliente/dashboard');
    Object.assign(STATE.cliente, { pontos_atual: data.pontos, cashback_atual: data.cashback });
    persistir();
    const c = STATE.cliente, e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-6 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-white/70 text-xs uppercase tracking-wider">Olá,</p>
                    <h1 class="text-2xl font-bold mt-0.5">${c.nome.split(' ')[0]} 👋</h1>
                </div>
                <button onclick="showScreen('perfil')" class="w-11 h-11 rounded-full bg-white/20 backdrop-blur flex items-center justify-center font-bold text-lg hover:bg-white/30 transition">
                    ${c.nome.charAt(0).toUpperCase()}
                </button>
            </div>

            <div class="mt-5 bg-white/15 backdrop-blur rounded-2xl border border-white/20 p-4">
                <div class="grid grid-cols-2 gap-3 pb-3 border-b border-white/20">
                    <div>
                        <p class="text-[11px] text-white/70 uppercase tracking-wider flex items-center gap-1">
                            <i class="ri-coin-line"></i> Pontos
                        </p>
                        <p class="text-2xl font-bold mt-1">${fmtNum(data.pontos)}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-white/70 uppercase tracking-wider flex items-center gap-1">
                            <i class="ri-money-dollar-circle-line"></i> Cashback
                        </p>
                        <p class="text-2xl font-bold mt-1">${fmtBRL(data.cashback)}</p>
                        ${data.cashback_pendente > 0 ? `<p class="text-[10px] text-white/70 mt-0.5"><i class="ri-time-line"></i> ${fmtBRL(data.cashback_pendente)} liberando</p>` : ''}
                    </div>
                </div>
                <button onclick="showScreen('qrcode')"
                        class="w-full py-2.5 mt-3 rounded-xl bg-white font-semibold flex items-center justify-center gap-2 hover:bg-white/90 transition"
                        style="color:${cor}">
                    <i class="ri-qr-code-line text-xl"></i> Mostrar meu QR Code
                </button>
            </div>
        </div>

        <div class="p-4 mt-2">
            <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-3 px-1">Acessar</h3>
            <div class="grid grid-cols-3 gap-3">
                <button onclick="showScreen('catalogo')" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:border-slate-300 transition">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:${cor}15">
                        <i class="ri-gift-line text-2xl" style="color:${cor}"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Prêmios</p>
                </button>
                <button onclick="showScreen('compras')" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:border-slate-300 transition">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center">
                        <i class="ri-shopping-bag-line text-2xl text-amber-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Compras</p>
                </button>
                <button onclick="showScreen('parceiros')" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:border-slate-300 transition">
                    <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center">
                        <i class="ri-shake-hands-line text-2xl text-purple-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Parceiros</p>
                </button>
                <button onclick="showScreen('indicacoes')" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:border-slate-300 transition">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <i class="ri-share-line text-2xl text-emerald-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Indicar</p>
                </button>
                <button onclick="showScreen('pesquisa')" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:border-slate-300 transition">
                    <div class="w-11 h-11 rounded-xl bg-pink-50 flex items-center justify-center">
                        <i class="ri-emotion-happy-line text-2xl text-pink-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Avaliar</p>
                </button>
                <button onclick="showScreen('meusCupons')" class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center gap-2 hover:shadow-md hover:border-slate-300 transition">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center">
                        <i class="ri-coupon-3-line text-2xl text-indigo-600"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Cupons</p>
                </button>
            </div>
        </div>

        <div class="px-4 pb-6">
            <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-3 px-1">Resumo</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-2xl p-4 border border-slate-200">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center mb-2">
                        <i class="ri-wallet-3-line text-emerald-600"></i>
                    </div>
                    <p class="text-xs text-slate-500">Total gasto</p>
                    <p class="text-lg font-bold text-slate-800">${fmtBRL(data.total_gasto)}</p>
                </div>
                <div class="bg-white rounded-2xl p-4 border border-slate-200">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center mb-2">
                        <i class="ri-shopping-cart-line text-amber-600"></i>
                    </div>
                    <p class="text-xs text-slate-500">Compras</p>
                    <p class="text-lg font-bold text-slate-800">${data.total_compras || 0}</p>
                </div>
            </div>
        </div>
    </div>`;
}

// Tela 4: Compras
async function telaCompras() {
    const data = await api('/cliente/compras');
    const e = STATE.empresa;
    const totalGasto = data.compras.reduce((s, c) => s + Number(c.valor || 0), 0);
    const totalPontos = data.compras.reduce((s, c) => s + Number(c.pontos_gerados || 0), 0);
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${e.cor_primaria},${e.cor_secundaria})">
            <h1 class="text-2xl font-bold">Minhas compras</h1>
            <p class="text-white/80 text-sm">${data.compras.length} ${data.compras.length === 1 ? 'registro' : 'registros'}</p>
        </div>

        ${data.compras.length > 0 ? `
        <div class="px-4 -mt-6">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Total gasto</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">${fmtBRL(totalGasto)}</p>
                </div>
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Pontos ganhos</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">${fmtNum(totalPontos)}</p>
                </div>
            </div>
        </div>` : ''}

        <div class="p-4 space-y-2 mt-2">
            ${data.compras.length === 0 ? `
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-shopping-bag-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-slate-500 font-medium">Nenhuma compra ainda</p>
                    <p class="text-sm text-slate-400 mt-1">Suas compras aparecerão aqui</p>
                </div>
            ` : ''}
            ${data.compras.map(c => `
                <div class="bg-white border border-slate-200 rounded-2xl p-4 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <i class="ri-shopping-bag-line text-emerald-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-800 truncate">${c.descricao || 'Compra'}</p>
                        <p class="text-xs text-slate-500 mt-0.5">${c.data_formatada}</p>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2">
                            <span class="text-xs text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                                <i class="ri-coin-line"></i> +${fmtNum(c.pontos_gerados)} pts
                            </span>
                            ${c.cashback_gerado > 0 ? `
                                <span class="text-xs text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full">
                                    <i class="ri-money-dollar-circle-line"></i> +${fmtBRL(c.cashback_gerado)}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <p class="font-bold text-slate-800 whitespace-nowrap">${fmtBRL(c.valor)}</p>
                </div>
            `).join('')}
        </div>
    </div>`;
}

// Tela 5: Catálogo recompensas
async function telaCatalogo() {
    const data = await api('/recompensas');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <h1 class="text-2xl font-bold">Prêmios</h1>
            <p class="text-white/80 text-sm">Troque seus pontos por recompensas</p>
        </div>

        <div class="px-4 -mt-6">
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-md flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:${cor}15">
                    <i class="ri-coin-line text-2xl" style="color:${cor}"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Saldo disponível</p>
                    <p class="text-2xl font-bold text-slate-800">${fmtNum(STATE.cliente.pontos_atual)} <span class="text-sm text-slate-500 font-normal">pontos</span></p>
                </div>
            </div>
        </div>

        <div class="p-4 mt-2">
            ${data.recompensas.length === 0 ? `
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-gift-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-slate-500 font-medium">Nenhuma recompensa cadastrada</p>
                </div>
            ` : `
            <div class="grid grid-cols-2 gap-3">
                ${data.recompensas.map(r => `
                    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden ${!r.pode_resgatar ? 'opacity-60' : ''} hover:shadow-md transition">
                        <div class="aspect-square flex items-center justify-center text-white text-5xl relative"
                             style="background:linear-gradient(135deg,${cor},${corSec})">
                            ${r.imagem ? `<img src="${r.imagem}" class="w-full h-full object-cover">` : '<i class="ri-gift-line"></i>'}
                            ${!r.pode_resgatar ? `
                                <div class="absolute top-2 right-2 bg-black/40 backdrop-blur text-white text-[10px] px-2 py-0.5 rounded-full">
                                    Faltam ${fmtNum(r.custo_pontos - STATE.cliente.pontos_atual)} pts
                                </div>
                            ` : ''}
                        </div>
                        <div class="p-3">
                            <p class="font-semibold text-sm text-slate-800 line-clamp-2 min-h-[2.5em]">${r.nome}</p>
                            <p class="font-bold text-sm mt-2 flex items-center gap-1" style="color:${cor}">
                                <i class="ri-coin-line"></i> ${fmtNum(r.custo_pontos)} pts
                            </p>
                            <button onclick="solicitarResgate(${r.id}, '${r.nome.replace(/'/g, "\\'")}', ${r.custo_pontos})"
                                    ${!r.pode_resgatar ? 'disabled' : ''}
                                    class="mt-3 w-full text-xs font-semibold py-2 text-white rounded-lg disabled:bg-slate-200 disabled:text-slate-400 transition"
                                    style="${r.pode_resgatar ? `background:linear-gradient(135deg,${cor},${corSec})` : ''}">
                                ${r.pode_resgatar ? 'Resgatar' : 'Indisponível'}
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>`}
        </div>
    </div>`;
}

window.solicitarResgate = async (id, nome, custo) => {
    if (!confirm(`Resgatar "${nome}" por ${fmtNum(custo)} pontos?`)) return;
    try {
        const res = await api('/resgates', { method: 'POST', body: JSON.stringify({ recompensa_id: id }) });
        STATE.cliente.pontos_atual = res.novo_saldo_pontos;
        persistir();
        toast(`Resgate solicitado! Código: ${res.resgate.codigo}`, 'success');
        setTimeout(() => showScreen('resgates'), 800);
    } catch (e) { toast(e.message, 'error'); }
};

// Tela 6: QR Code
async function telaQrCode() {
    const codigo = encodeURIComponent(STATE.cliente.codigo_qr);
    const e = STATE.empresa;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-12 text-white text-center" style="background:linear-gradient(135deg,${e.cor_primaria},${e.cor_secundaria})">
            <h1 class="text-2xl font-bold">Meu QR Code</h1>
            <p class="text-white/80 text-sm mt-1">Apresente no caixa para acumular pontos</p>
        </div>

        <div class="px-4 -mt-8 pb-6">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-6 text-center">
                <div class="bg-white p-3 rounded-2xl border-2 border-slate-100 inline-block">
                    <img src="${API}/qr/${codigo}" width="240" height="240" alt="QR Code" class="block">
                </div>
                <p class="mt-4 font-mono text-base font-semibold text-slate-700 tracking-wider">${STATE.cliente.codigo_qr}</p>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="font-semibold text-slate-800">${STATE.cliente.nome}</p>
                    <p class="text-sm text-slate-500">${STATE.cliente.telefone}</p>
                </div>
            </div>

            <div class="mt-4 bg-amber-50 border border-amber-100 rounded-2xl p-4 flex gap-3">
                <i class="ri-information-line text-amber-600 text-xl flex-shrink-0"></i>
                <div>
                    <p class="text-sm font-semibold text-amber-900">Como usar</p>
                    <p class="text-xs text-amber-800 mt-1">Mostre este código ao atendente para acumular pontos a cada compra.</p>
                </div>
            </div>
        </div>
    </div>`;
}

// Tela 7: Perfil
async function telaPerfil() {
    const c = STATE.cliente;
    const e = STATE.empresa;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-8 pb-12 text-white text-center" style="background:linear-gradient(135deg,${e.cor_primaria},${e.cor_secundaria})">
            <div class="w-24 h-24 mx-auto rounded-full bg-white/20 backdrop-blur border-4 border-white/30 flex items-center justify-center text-4xl font-bold shadow-lg">
                ${c.nome.charAt(0).toUpperCase()}
            </div>
            <h1 class="text-2xl font-bold mt-4">${c.nome}</h1>
            <p class="text-white/80 text-sm">${c.telefone}</p>
            ${c.email ? `<p class="text-white/70 text-xs mt-1">${c.email}</p>` : ''}
        </div>

        <div class="px-4 -mt-8">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-4 grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-lg font-bold text-slate-800">${fmtNum(c.pontos_atual || 0)}</p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Pontos</p>
                </div>
                <div class="border-x border-slate-100">
                    <p class="text-lg font-bold text-emerald-600">${fmtBRL(c.cashback_atual || 0)}</p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Cashback</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-slate-800">${c.total_compras || 0}</p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Compras</p>
                </div>
            </div>
        </div>

        <div class="px-4 mt-4">
            <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2 px-1">Minha conta</h3>
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
                <button onclick="showScreen('extrato')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="ri-bank-line text-blue-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Extrato</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
                <button onclick="showScreen('empresa')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                        <i class="ri-store-2-line text-purple-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Sobre a empresa</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
                <button onclick="showScreen('resgates')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                        <i class="ri-coupon-line text-amber-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Meus resgates</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
                <button onclick="showScreen('meusCupons')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <i class="ri-coupon-3-line text-indigo-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Meus cupons</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
                <button onclick="showScreen('editarPerfil')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                        <i class="ri-edit-line text-slate-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Editar dados</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
                <button onclick="showScreen('alterarSenha')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-rose-50 flex items-center justify-center">
                        <i class="ri-lock-2-line text-rose-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Alterar senha</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
            </div>
        </div>

        <div class="px-4 mt-4">
            <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2 px-1">Engajamento</h3>
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
                <button onclick="showScreen('indicacoes')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <i class="ri-share-line text-emerald-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Indicar amigos</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
                <button onclick="showScreen('pesquisa')" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-pink-50 flex items-center justify-center">
                        <i class="ri-emotion-happy-line text-pink-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Avaliar atendimento</span>
                    <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                </button>
            </div>
        </div>

        <div class="px-4 mt-4 pb-6">
            <button onclick="logout()" class="w-full py-3.5 bg-white border border-rose-200 text-rose-600 rounded-2xl font-semibold flex items-center justify-center gap-2 hover:bg-rose-50 transition">
                <i class="ri-logout-box-line text-xl"></i> Sair da conta
            </button>
        </div>
    </div>`;
}

// Tela 7.5: Editar perfil
async function telaEditarPerfil() {
    const c = STATE.cliente;
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Editar dados</h1>
            <p class="text-white/80 text-sm mt-1">Atualize suas informações</p>
        </div>

        <form id="form-editar-perfil" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome completo</label>
                    <div class="relative">
                        <i class="ri-user-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="nome" value="${(c.nome || '').replace(/"/g, '&quot;')}" required
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone</label>
                    <div class="relative">
                        <i class="ri-smartphone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input value="${c.telefone || ''}" disabled
                               class="w-full pl-11 pr-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 ml-1">O telefone não pode ser alterado por aqui</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                    <div class="relative">
                        <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="email" type="email" value="${(c.email || '').replace(/"/g, '&quot;')}" placeholder="seu@email.com"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">CPF</label>
                    <div class="relative">
                        <i class="ri-id-card-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="cpf" value="${c.cpf || ''}" placeholder="000.000.000-00" maxlength="14"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Data de nascimento</label>
                    <div class="relative">
                        <i class="ri-cake-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                        <input name="data_nascimento" type="date" value="${c.data_nascimento || ''}"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition text-slate-700">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 ml-1">Receba um presente no seu aniversário</p>
                </div>
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-save-line"></i> Salvar alterações
            </button>

            <button type="button" onclick="showScreen('perfil')" class="w-full mt-2 py-3 text-slate-600 font-medium rounded-2xl hover:bg-slate-100 transition">
                Cancelar
            </button>
        </form>
    </div>`;

    $('#form-editar-perfil').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        Object.keys(fd).forEach(k => { if (fd[k] === '') fd[k] = null; });
        try {
            const res = await api('/cliente/perfil', { method: 'PUT', body: JSON.stringify(fd) });
            STATE.cliente = { ...STATE.cliente, ...res.cliente };
            persistir();
            toast('Dados atualizados!', 'success');
            showScreen('perfil');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// Tela 7.6: Alterar senha
async function telaAlterarSenha() {
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Alterar senha</h1>
            <p class="text-white/80 text-sm mt-1">Defina uma nova senha de acesso</p>
        </div>

        <form id="form-alterar-senha" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Senha atual</label>
                    <div class="relative">
                        <i class="ri-lock-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="senha_atual" type="password" required placeholder="Sua senha atual"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nova senha</label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="senha_nova" type="password" required minlength="6" placeholder="Mínimo 6 caracteres"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirmar nova senha</label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="senha_nova_confirmation" type="password" required minlength="6" placeholder="Repita a nova senha"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 flex gap-2">
                    <i class="ri-shield-check-line text-amber-600 mt-0.5"></i>
                    <p class="text-xs text-amber-800">Use ao menos 6 caracteres. Misture letras, números e símbolos pra ficar mais segura.</p>
                </div>
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-shield-keyhole-line"></i> Atualizar senha
            </button>

            <button type="button" onclick="showScreen('perfil')" class="w-full mt-2 py-3 text-slate-600 font-medium rounded-2xl hover:bg-slate-100 transition">
                Cancelar
            </button>
        </form>
    </div>`;

    $('#form-alterar-senha').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        if (fd.senha_nova !== fd.senha_nova_confirmation) {
            return toast('As senhas novas não conferem', 'error');
        }
        try {
            await api('/cliente/senha', { method: 'PUT', body: JSON.stringify(fd) });
            toast('Senha alterada!', 'success');
            showScreen('perfil');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// Tela 7.8: Sobre a empresa atual + minhas outras empresas
async function telaEmpresa() {
    const data = await api('/cliente/empresas');
    const e = data.empresa_atual;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-12 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-4 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <div class="text-center">
                ${e.logo
                    ? `<img src="${e.logo}" alt="${e.nome}" class="w-20 h-20 mx-auto rounded-2xl bg-white/20 backdrop-blur p-2 mb-3 object-contain">`
                    : `<div class="w-20 h-20 mx-auto rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl font-bold mb-3">${e.nome.charAt(0)}</div>`
                }
                <h1 class="text-2xl font-bold">${e.nome}</h1>
                <p class="text-white/80 text-xs mt-1">Cliente desde ${e.cliente_desde || '—'}</p>
            </div>
        </div>

        <div class="px-4 -mt-8 pb-6 space-y-4">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5">
                <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-3">Programa de fidelidade</h3>
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-xs text-slate-500">Pontos por real</p>
                        <p class="text-xl font-bold mt-1" style="color:${cor}">${e.pontos_por_real} <span class="text-xs text-slate-500 font-normal">/ R$1</span></p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-xs text-slate-500">Cashback</p>
                        <p class="text-xl font-bold text-emerald-600 mt-1">${Number(e.cashback_percentual).toFixed(1).replace('.', ',')}<span class="text-sm font-normal">%</span></p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-center">
                    <div>
                        <p class="text-[11px] text-slate-500 uppercase">Seu saldo</p>
                        <p class="text-sm font-bold text-slate-800">${fmtNum(e.pontos)} pts</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-500 uppercase">Seu cashback</p>
                        <p class="text-sm font-bold text-emerald-600">${fmtBRL(e.cashback)}</p>
                    </div>
                </div>
            </div>

            ${(e.telefone || e.email || e.endereco) ? `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-3">Contato</h3>
                <div class="space-y-3 text-sm">
                    ${e.telefone ? `
                        <a href="tel:${e.telefone.replace(/\\D/g, '')}" class="flex items-center gap-3 hover:bg-slate-50 -mx-2 px-2 py-1 rounded-lg">
                            <i class="ri-phone-line text-slate-400"></i>
                            <span class="text-slate-700">${e.telefone}</span>
                        </a>` : ''}
                    ${e.email ? `
                        <a href="mailto:${e.email}" class="flex items-center gap-3 hover:bg-slate-50 -mx-2 px-2 py-1 rounded-lg">
                            <i class="ri-mail-line text-slate-400"></i>
                            <span class="text-slate-700 truncate">${e.email}</span>
                        </a>` : ''}
                    ${e.endereco ? `
                        <div class="flex items-start gap-3">
                            <i class="ri-map-pin-line text-slate-400 mt-0.5"></i>
                            <span class="text-slate-700">${e.endereco}</span>
                        </div>` : ''}
                </div>
            </div>` : ''}

            ${data.vinculadas.length > 0 ? `
            <div>
                <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2 px-1">Minhas outras empresas (${data.vinculadas.length})</h3>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
                    ${data.vinculadas.map(v => `
                        <a href="${v.url}" class="block p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                            ${v.logo
                                ? `<img src="${v.logo}" alt="${v.nome}" class="w-11 h-11 rounded-xl object-contain bg-slate-50">`
                                : `<div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-semibold" style="background:linear-gradient(135deg,${v.cor_primaria},${v.cor_secundaria})">${v.nome.charAt(0)}</div>`
                            }
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 truncate">${v.nome}</p>
                                <div class="flex gap-3 text-xs text-slate-500 mt-0.5">
                                    <span><i class="ri-coin-line"></i> ${fmtNum(v.pontos)}</span>
                                    <span class="text-emerald-600"><i class="ri-money-dollar-circle-line"></i> ${fmtBRL(v.cashback)}</span>
                                </div>
                            </div>
                            <i class="ri-arrow-right-s-line text-slate-400 text-xl"></i>
                        </a>
                    `).join('')}
                </div>
                <p class="text-[11px] text-slate-400 px-2 mt-2">
                    <i class="ri-information-line"></i> Cada empresa tem login e senha próprios.
                </p>
            </div>` : ''}
        </div>
    </div>`;
}

// Máscara CPF (000.000.000-00)
document.addEventListener('input', (ev) => {
    const el = ev.target;
    if (!el.matches || !el.matches('input[name="cpf"]')) return;
    const v = el.value.replace(/\D/g, '').slice(0, 11);
    let formatted = v;
    if (v.length > 9) formatted = v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9);
    else if (v.length > 6) formatted = v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6);
    else if (v.length > 3) formatted = v.slice(0,3)+'.'+v.slice(3);
    if (formatted !== el.value) {
        el.value = formatted;
        el.setSelectionRange(formatted.length, formatted.length);
    }
});

window.logout = async () => {
    try { await api('/auth/logout', { method: 'POST' }); } catch {}
    STATE.token = null; STATE.cliente = null;
    persistir();
    showScreen('escolherEmpresa');
};

// Tela 7.7: Extrato (movimentações de pontos e cashback)
async function telaExtrato() {
    const data = await api('/cliente/extrato');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    const renderItem = (m, isCashback) => {
        const credito = m.tipo === 'credito';
        const sinal = credito ? '+' : '−';
        const valor = isCashback ? `R$ ${Number(m.valor).toFixed(2).replace('.', ',')}` : fmtNum(m.pontos) + ' pts';
        const saldoFmt = isCashback ? `R$ ${Number(m.saldo_posterior).toFixed(2).replace('.', ',')}` : fmtNum(m.saldo_posterior) + ' pts';
        const pendente = isCashback && m.processado === false;
        return `
        <li class="py-3 flex items-start gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 ${credito ? 'bg-emerald-100' : 'bg-rose-100'}">
                <i class="ri-arrow-${credito ? 'up' : 'down'}-line ${credito ? 'text-emerald-600' : 'text-rose-600'}"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-800 text-sm">${m.descricao || (credito ? 'Crédito' : 'Débito')}</p>
                <div class="flex flex-wrap gap-x-2 text-[11px] text-slate-500 mt-0.5">
                    <span>${m.data}</span>
                    <span class="px-1.5 rounded bg-slate-100 text-slate-600">${m.origem}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-0.5">Saldo após: ${saldoFmt}${pendente ? ` &middot; <span class="text-amber-600">libera em ${m.liberado_em}</span>` : ''}</p>
            </div>
            <p class="font-bold text-sm whitespace-nowrap ${credito ? 'text-emerald-600' : 'text-rose-600'}">${sinal}${valor}</p>
        </li>`;
    };

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Extrato</h1>
            <p class="text-white/80 text-sm mt-1">Veja todas as suas movimentações</p>
        </div>

        <div class="px-4 -mt-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-1.5 flex gap-1">
                <button id="tab-pontos" onclick="extratoTab('pontos')" class="flex-1 py-2.5 rounded-xl font-semibold text-sm flex items-center justify-center gap-1.5 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
                    <i class="ri-coin-line"></i> Pontos
                </button>
                <button id="tab-cashback" onclick="extratoTab('cashback')" class="flex-1 py-2.5 rounded-xl font-semibold text-sm text-slate-600 flex items-center justify-center gap-1.5 hover:bg-slate-50 transition">
                    <i class="ri-money-dollar-circle-line"></i> Cashback
                </button>
            </div>
        </div>

        <div class="px-4 mt-3 pb-6">
            <div id="extrato-conteudo-pontos" class="bg-white border border-slate-200 rounded-2xl px-4">
                ${data.pontos.length === 0
                    ? `<div class="py-10 text-center">
                        <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-2">
                            <i class="ri-coin-line text-2xl text-slate-400"></i>
                        </div>
                        <p class="text-sm text-slate-500 font-medium">Nenhuma movimentação de pontos ainda</p>
                       </div>`
                    : `<ul class="divide-y divide-slate-100">${data.pontos.map(p => renderItem(p, false)).join('')}</ul>`}
            </div>
            <div id="extrato-conteudo-cashback" class="bg-white border border-slate-200 rounded-2xl px-4 hidden">
                ${data.cashback.length === 0
                    ? `<div class="py-10 text-center">
                        <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-2">
                            <i class="ri-money-dollar-circle-line text-2xl text-slate-400"></i>
                        </div>
                        <p class="text-sm text-slate-500 font-medium">Nenhuma movimentação de cashback ainda</p>
                       </div>`
                    : `<ul class="divide-y divide-slate-100">${data.cashback.map(c => renderItem(c, true)).join('')}</ul>`}
            </div>
        </div>
    </div>`;
}

window.extratoTab = (qual) => {
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    const ativa = qual === 'pontos' ? 'tab-pontos' : 'tab-cashback';
    const inativa = qual === 'pontos' ? 'tab-cashback' : 'tab-pontos';
    const ativaEl = document.getElementById(ativa);
    const inativaEl = document.getElementById(inativa);
    ativaEl.style.background = `linear-gradient(135deg, ${cor}, ${corSec})`;
    ativaEl.classList.add('text-white');
    ativaEl.classList.remove('text-slate-600', 'hover:bg-slate-50');
    inativaEl.style.background = '';
    inativaEl.classList.remove('text-white');
    inativaEl.classList.add('text-slate-600', 'hover:bg-slate-50');
    document.getElementById('extrato-conteudo-pontos').classList.toggle('hidden', qual !== 'pontos');
    document.getElementById('extrato-conteudo-cashback').classList.toggle('hidden', qual !== 'cashback');
};

// Tela 8: Resgates
async function telaResgates() {
    const data = await api('/resgates');
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto">
        <div class="p-5 text-white" style="background:linear-gradient(135deg,${STATE.empresa.cor_primaria},${STATE.empresa.cor_secundaria})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-2"><i class="ri-arrow-left-line"></i> Voltar</button>
            <h1 class="text-xl font-bold">Meus resgates</h1>
        </div>
        <div class="p-4 space-y-2">
            ${data.resgates.length === 0 ? `<p class="text-center text-slate-400 py-10">Nenhum resgate ainda.</p>` : ''}
            ${data.resgates.map(r => `
                <div class="bg-white border border-slate-200 rounded-xl p-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold">${r.recompensa}</p>
                            <p class="text-xs font-mono text-slate-500">${r.codigo}</p>
                            <p class="text-xs text-slate-500 mt-1">${r.data}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full ${
                            r.status === 'pendente' ? 'bg-amber-100 text-amber-700' :
                            r.status === 'aprovado' ? 'bg-blue-100 text-blue-700' :
                            r.status === 'entregue' ? 'bg-emerald-100 text-emerald-700' :
                            'bg-slate-200 text-slate-600'
                        }">${r.status}</span>
                    </div>
                    <p class="text-amber-600 text-sm mt-2">−${fmtNum(r.pontos_usados)} pts</p>
                </div>
            `).join('')}
        </div>
    </div>`;
}

// Tela 9: Indicações
async function telaIndicacoes() {
    const data = await api('/indicacoes');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Indique amigos</h1>
            <p class="text-white/80 text-sm mt-1">Ganhe pontos por cada amigo que se cadastrar</p>
        </div>

        <div class="px-4 -mt-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 text-center">
                <p class="text-[11px] text-slate-500 uppercase tracking-wider">Seu código de indicação</p>
                <p class="text-3xl font-bold font-mono my-2 tracking-widest" style="color:${cor}">${data.codigo_indicacao}</p>
                <div class="flex gap-2 mt-3">
                    <button onclick="copiarLink('${data.link}')" class="flex-1 py-2.5 rounded-xl border-2 font-semibold text-sm flex items-center justify-center gap-1.5 hover:bg-slate-50 transition" style="border-color:${cor}; color:${cor}">
                        <i class="ri-link"></i> Copiar link
                    </button>
                    <button onclick="compartilharIndicacao('${data.link}', '${e.nome.replace(/'/g, "\\'")}')" class="flex-1 py-2.5 rounded-xl text-white font-semibold text-sm flex items-center justify-center gap-1.5 hover:shadow-lg transition" style="background:linear-gradient(135deg,${cor},${corSec})">
                        <i class="ri-share-forward-line"></i> Compartilhar
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 mt-3">
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-white border border-slate-200 rounded-2xl p-3 text-center">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Indicados</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${data.total_indicacoes}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-3 text-center">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Convertidas</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">${data.total_convertidas}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-3 text-center">
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Pts ganhos</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">${fmtNum(data.total_pontos_ganhos)}</p>
                </div>
            </div>
        </div>

        <div class="px-4 mt-4">
            <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2 px-1">Indicar manualmente</h3>
            <form id="form-indicar" class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome do amigo</label>
                    <div class="relative">
                        <i class="ri-user-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="nome_indicado" required placeholder="Como ele é chamado"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone</label>
                    <div class="relative">
                        <i class="ri-smartphone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="telefone_indicado" required placeholder="(11) 99999-9999"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>
                <button class="w-full py-3 text-white rounded-xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition" style="background:linear-gradient(135deg,${cor},${corSec})">
                    <i class="ri-user-add-line"></i> Registrar indicação
                </button>
            </form>
        </div>

        <div class="px-4 mt-5 pb-6">
            <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2 px-1">Histórico</h3>
            ${data.indicacoes.length === 0 ? `
                <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-2">
                        <i class="ri-share-line text-2xl text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Nenhuma indicação ainda</p>
                    <p class="text-xs text-slate-400 mt-1">Compartilhe seu código pra começar</p>
                </div>
            ` : `
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100">
                ${data.indicacoes.map(i => `
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-semibold text-slate-600 flex-shrink-0">
                            ${i.nome_indicado.charAt(0).toUpperCase()}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">${i.nome_indicado}</p>
                            <p class="text-xs text-slate-500">${i.telefone} &middot; ${i.data}</p>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-1 rounded-full ${
                            i.status === 'convertido' ? 'bg-emerald-50 text-emerald-700' :
                            i.status === 'cadastrado' ? 'bg-blue-50 text-blue-700' :
                            i.status === 'expirado' ? 'bg-slate-100 text-slate-600' :
                            'bg-amber-50 text-amber-700'
                        }">${i.status}</span>
                    </div>
                `).join('')}
            </div>`}
        </div>
    </div>`;

    $('#form-indicar').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        try {
            await api('/indicacoes', { method: 'POST', body: JSON.stringify(fd) });
            toast('Indicação registrada!', 'success');
            telaIndicacoes();
        } catch (e) { toast(e.message, 'error'); }
    });
}

window.copiarLink = (link) => {
    navigator.clipboard.writeText(link).then(() => toast('Link copiado!', 'success'));
};

window.compartilharIndicacao = async (link, nomeEmpresa) => {
    const texto = `Conheci o programa de fidelidade da ${nomeEmpresa} e queria te indicar! Cadastre-se pelo meu link:`;
    if (navigator.share) {
        try { await navigator.share({ title: nomeEmpresa, text: texto, url: link }); } catch {}
    } else {
        navigator.clipboard.writeText(`${texto} ${link}`).then(() => toast('Mensagem copiada!', 'success'));
    }
};

// Tela 10: Pesquisa de satisfação
async function telaPesquisa() {
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    const data = await api('/pesquisas/minha-geral');
    const existente = data.pesquisa;
    const notaInicial = existente?.nota || 0;
    const comentarioInicial = existente?.comentario || '';

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">${existente ? 'Sua avaliação' : 'Avalie nosso atendimento'}</h1>
            <p class="text-white/80 text-sm mt-1">${existente ? 'Você pode editar ou excluir abaixo' : 'Sua opinião é muito importante'}</p>
        </div>

        <form id="form-pesquisa" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6 space-y-5">
                <div>
                    <p class="text-center text-sm text-slate-700 mb-3">Como você avalia sua experiência?</p>
                    <div class="flex justify-center gap-2" id="estrelas">
                        ${[1,2,3,4,5].map(n => `
                            <button type="button" data-nota="${n}" class="estrela text-4xl ${n <= notaInicial ? 'text-amber-400' : 'text-slate-300'} hover:text-amber-400 transition">
                                <i class="ri-star-fill"></i>
                            </button>
                        `).join('')}
                    </div>
                    <input type="hidden" name="nota" id="nota-input" value="${notaInicial}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Comentário (opcional)</label>
                    <textarea name="comentario" rows="5" maxlength="1000" placeholder="Conte como foi sua experiência..."
                              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">${comentarioInicial.replace(/</g, '&lt;')}</textarea>
                </div>

                ${existente ? `
                <p class="text-xs text-slate-500 text-center">
                    <i class="ri-information-line"></i> Avaliação enviada em ${new Date(existente.created_at).toLocaleDateString('pt-BR')}
                </p>` : ''}
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-${existente ? 'save' : 'send-plane'}-line"></i> ${existente ? 'Salvar alterações' : 'Enviar avaliação'}
            </button>

            ${existente ? `
            <button type="button" onclick="excluirAvaliacao(${existente.id})"
                    class="w-full mt-2 py-3 text-rose-600 font-medium rounded-2xl border-2 border-rose-200 bg-white hover:bg-rose-50 transition flex items-center justify-center gap-2">
                <i class="ri-delete-bin-line"></i> Excluir avaliação
            </button>` : ''}

            <button type="button" onclick="showScreen('perfil')" class="w-full mt-2 py-3 text-slate-600 font-medium rounded-2xl hover:bg-slate-100 transition">
                Cancelar
            </button>
        </form>
    </div>`;

    document.querySelectorAll('.estrela').forEach((el) => {
        el.addEventListener('click', () => {
            const n = +el.dataset.nota;
            $('#nota-input').value = n;
            document.querySelectorAll('.estrela').forEach((s, i) => {
                s.classList.toggle('text-amber-400', i < n);
                s.classList.toggle('text-slate-300', i >= n);
            });
        });
    });

    $('#form-pesquisa').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        if (!fd.nota || fd.nota === '0') return toast('Selecione uma nota', 'error');
        try {
            if (existente) {
                await api('/pesquisas/'+existente.id, { method: 'PUT', body: JSON.stringify(fd) });
                toast('Avaliação atualizada!', 'success');
            } else {
                await api('/pesquisas', { method: 'POST', body: JSON.stringify(fd) });
                toast('Obrigado pela avaliação!', 'success');
            }
            setTimeout(() => showScreen('home'), 800);
        } catch (e) { toast(e.message, 'error'); }
    });
}

window.excluirAvaliacao = async (id) => {
    if (!confirm('Excluir sua avaliação? Você poderá criar outra depois.')) return;
    try {
        await api('/pesquisas/'+id, { method: 'DELETE' });
        toast('Avaliação removida', 'success');
        setTimeout(() => showScreen('perfil'), 600);
    } catch (e) { toast(e.message, 'error'); }
};

// Tela 11: Parceiros e benefícios
async function telaParceiros() {
    const data = await api('/parceiros');
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto">
        <div class="p-5 text-white" style="background:linear-gradient(135deg,${STATE.empresa.cor_primaria},${STATE.empresa.cor_secundaria})">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-bold">Parceiros</h1>
                    <p class="text-white/80 text-sm">Cupons exclusivos de empresas parceiras</p>
                </div>
                <button onclick="showScreen('meusCupons')" class="bg-white/20 px-3 py-1.5 rounded-full text-xs">
                    <i class="ri-coupon-3-line"></i> Meus cupons
                </button>
            </div>
        </div>
        <div class="p-4 space-y-4">
            ${data.parceiros.length === 0 ? `<p class="text-center text-slate-400 py-10">Nenhum parceiro com benefícios ativos.</p>` : ''}
            ${data.parceiros.map(p => `
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <div class="p-4 flex items-start gap-3 border-b border-slate-100">
                        ${p.logo
                            ? `<img src="${p.logo}" class="w-12 h-12 rounded-lg object-cover">`
                            : `<div class="w-12 h-12 rounded-lg bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xl"><i class="ri-building-line"></i></div>`}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold">${p.nome}</p>
                            ${p.categoria ? `<p class="text-xs text-slate-500">${p.categoria}</p>` : ''}
                            ${p.endereco ? `<p class="text-xs text-slate-500"><i class="ri-map-pin-line"></i> ${p.endereco}</p>` : ''}
                        </div>
                    </div>
                    <div class="p-4 space-y-2">
                        ${p.beneficios.map(b => `
                            <div class="border border-slate-200 rounded-lg p-3 ${!b.pode_resgatar ? 'opacity-60' : ''}">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-sm">${b.nome}</p>
                                            ${b.destaque ? '<span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 rounded-full">Destaque</span>' : ''}
                                        </div>
                                        <p class="text-emerald-700 font-bold text-xs mt-0.5">${b.tipo_descricao}</p>
                                        ${b.descricao ? `<p class="text-xs text-slate-600 mt-1">${b.descricao}</p>` : ''}
                                        ${b.condicoes ? `<p class="text-[11px] text-slate-500 mt-1"><i class="ri-information-line"></i> ${b.condicoes}</p>` : ''}
                                        ${b.valido_ate ? `<p class="text-[11px] text-slate-500 mt-1">Válido até ${b.valido_ate}</p>` : ''}
                                    </div>
                                    <button onclick="ativarCupom(${b.id}, '${b.nome.replace(/'/g, "\\'")}')"
                                            ${!b.pode_resgatar ? 'disabled' : ''}
                                            class="text-xs px-3 py-2 rounded-lg text-white font-semibold disabled:bg-slate-300 shrink-0"
                                            style="background:${b.pode_resgatar ? STATE.empresa.cor_primaria : ''}">
                                        ${b.pode_resgatar ? 'Ativar' : 'Indisponível'}
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
    </div>`;
}

window.ativarCupom = async (beneficioId, nome) => {
    if (!confirm(`Ativar cupom "${nome}"?\nVocê receberá um código que precisa apresentar no parceiro.`)) return;
    try {
        const res = await api('/parceiros/cupons', { method: 'POST', body: JSON.stringify({ beneficio_id: beneficioId }) });
        toast(`Cupom ativado: ${res.cupom.codigo}`, 'success');
        setTimeout(() => showScreen('meusCupons'), 800);
    } catch (e) { toast(e.message, 'error'); }
};

// Tela 12: Meus cupons (parceiros)
async function telaMeusCupons() {
    const data = await api('/parceiros/meus-cupons');
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto">
        <div class="p-5 text-white" style="background:linear-gradient(135deg,${STATE.empresa.cor_primaria},${STATE.empresa.cor_secundaria})">
            <button onclick="showScreen('parceiros')" class="text-white/80 mb-2"><i class="ri-arrow-left-line"></i> Voltar</button>
            <h1 class="text-xl font-bold">Meus cupons</h1>
        </div>
        <div class="p-4 space-y-3">
            ${data.cupons.length === 0 ? `<p class="text-center text-slate-400 py-10">Nenhum cupom ativo.</p>` : ''}
            ${data.cupons.map(c => `
                <div class="bg-white border border-slate-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        ${c.parceiro_logo
                            ? `<img src="${c.parceiro_logo}" class="w-10 h-10 rounded-lg object-cover">`
                            : `<div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500"><i class="ri-building-line"></i></div>`}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">${c.beneficio}</p>
                            <p class="text-xs text-slate-500">${c.parceiro}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full ${
                            c.status === 'disponivel' ? 'bg-emerald-100 text-emerald-700' :
                            c.status === 'usado' ? 'bg-slate-200 text-slate-600' :
                            'bg-rose-100 text-rose-700'
                        }">${c.status}</span>
                    </div>
                    ${c.utilizavel ? `
                        <div class="mt-3 p-3 bg-amber-50 border-2 border-dashed border-amber-300 rounded text-center">
                            <p class="text-xs text-amber-700 mb-1">Apresente este código no parceiro</p>
                            <p class="text-2xl font-bold font-mono tracking-wider text-amber-800">${c.codigo}</p>
                            <p class="text-xs text-amber-600 mt-1">Válido até ${c.valido_ate}</p>
                        </div>
                    ` : ''}
                    ${c.usado_em ? `<p class="text-xs text-slate-500 mt-2 text-right">Usado em ${c.usado_em}</p>` : ''}
                </div>
            `).join('')}
        </div>
    </div>`;
}

// ============ PWA INSTALL ============
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    $('#install-btn').classList.remove('hidden');
});

$('#install-btn').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    $('#install-btn').classList.add('hidden');
});

// ============ SERVICE WORKER ============
if ('serviceWorker' in navigator) {
    // Em white label, usa SW dinâmico (gerado pelo Laravel) com escopo da empresa
    if (window.WHITELABEL_SW) {
        navigator.serviceWorker.register(window.WHITELABEL_SW, { scope: './' }).catch(() => {});
    } else {
        navigator.serviceWorker.register('sw.js', { scope: './' }).catch(() => {});
    }
}

// ============ BOOT ============
aplicarTemaEmpresa();
showScreen(STATE.token ? 'home' : 'escolherEmpresa');
