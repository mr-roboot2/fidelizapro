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
    <div class="fade-in flex-1 flex flex-col">
        <div class="p-6 text-white" style="background:linear-gradient(135deg,${e?.cor_primaria||'#10b981'},${e?.cor_secundaria||'#059669'})">
            <button onclick="showScreen('login')" class="text-white/80 mb-3"><i class="ri-arrow-left-line"></i> Voltar</button>
            <h1 class="text-2xl font-bold flex items-center gap-2"><i class="ri-whatsapp-line"></i> Entrar com WhatsApp</h1>
            <p class="text-white/80 text-sm">Você receberá um código por WhatsApp</p>
        </div>

        <div id="otp-fase-1" class="p-6 space-y-4 flex-1">
            <div>
                <label class="text-sm font-medium">Telefone com DDD</label>
                <input id="otp-tel" type="tel" required placeholder="(11) 99999-9999"
                       class="mt-1 w-full px-4 py-3 border border-slate-300 rounded-xl">
            </div>
            <button onclick="solicitarOtp()" id="otp-btn-solicitar"
                    class="w-full py-3 text-white rounded-xl font-semibold" style="background:#10b981">
                <i class="ri-send-plane-line"></i> Enviar código
            </button>
            <p class="text-xs text-slate-500 text-center">Você receberá um código de 6 dígitos por WhatsApp</p>
        </div>

        <div id="otp-fase-2" class="p-6 space-y-4 flex-1 hidden">
            <p class="text-sm text-center text-slate-700">Digite o código de 6 dígitos enviado por WhatsApp para <strong id="otp-tel-show"></strong></p>
            <input id="otp-codigo" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                   class="w-full px-4 py-4 border border-slate-300 rounded-xl text-center text-3xl font-mono tracking-[0.5em]">
            <button onclick="validarOtp()" class="w-full py-3 text-white rounded-xl font-semibold" style="background:#10b981">
                Confirmar código
            </button>
            <div class="flex justify-between text-sm">
                <button onclick="resetOtp()" class="text-slate-500">← Trocar telefone</button>
                <button onclick="solicitarOtp(true)" id="otp-btn-reenviar" class="text-emerald-600 font-medium">Reenviar</button>
            </div>
            <p id="otp-dev" class="text-xs text-amber-600 text-center"></p>
        </div>
    </div>`;
}

window.solicitarOtp = async (reenviar = false) => {
    const tel = $('#otp-tel').value.trim();
    if (!tel) return toast('Informe o telefone', 'error');
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
    } catch (e) { toast(e.message, 'error'); }
};

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
                        <i class="ri-handshake-line text-2xl text-purple-600"></i>
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
                <button onclick="editarPerfil()" class="w-full p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                        <i class="ri-edit-line text-slate-600"></i>
                    </div>
                    <span class="flex-1 text-left font-medium text-slate-700">Editar dados</span>
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

window.editarPerfil = () => {
    const c = STATE.cliente;
    const novo = prompt('Editar e-mail:', c.email || '');
    if (novo === null) return;
    api('/cliente/perfil', { method: 'PUT', body: JSON.stringify({ email: novo }) })
        .then(() => { STATE.cliente.email = novo; persistir(); toast('Atualizado!', 'success'); })
        .catch(e => toast(e.message, 'error'));
};

window.logout = async () => {
    try { await api('/auth/logout', { method: 'POST' }); } catch {}
    STATE.token = null; STATE.cliente = null;
    persistir();
    showScreen('escolherEmpresa');
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
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto">
        <div class="p-5 text-white" style="background:linear-gradient(135deg,${STATE.empresa.cor_primaria},${STATE.empresa.cor_secundaria})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-2"><i class="ri-arrow-left-line"></i> Voltar</button>
            <h1 class="text-xl font-bold">Indique amigos</h1>
            <p class="text-white/80 text-sm">Ganhe pontos por cada amigo que se cadastrar</p>
        </div>
        <div class="p-4">
            <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-4 text-center">
                <p class="text-xs text-amber-700">Seu código de indicação</p>
                <p class="text-3xl font-bold font-mono text-amber-700 my-2">${data.codigo_indicacao}</p>
                <button onclick="copiarLink('${data.link}')" class="text-sm text-amber-700 underline">
                    <i class="ri-link"></i> Copiar link
                </button>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-4">
                <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
                    <p class="text-xs text-slate-500">Indicações</p>
                    <p class="text-xl font-bold">${data.total_indicacoes}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
                    <p class="text-xs text-slate-500">Convertidas</p>
                    <p class="text-xl font-bold text-emerald-600">${data.total_convertidas}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
                    <p class="text-xs text-slate-500">Pontos ganhos</p>
                    <p class="text-xl font-bold text-amber-600">${fmtNum(data.total_pontos_ganhos)}</p>
                </div>
            </div>

            <form id="form-indicar" class="mt-4 space-y-2">
                <input name="nome_indicado" required placeholder="Nome do amigo" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
                <input name="telefone_indicado" required placeholder="Telefone" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
                <button class="w-full py-3 text-white rounded-xl font-semibold" style="background:${STATE.empresa.cor_primaria}">
                    <i class="ri-add-line"></i> Indicar amigo
                </button>
            </form>

            <div class="mt-4 space-y-2">
                ${data.indicacoes.map(i => `
                    <div class="bg-white border border-slate-200 rounded-xl p-3 flex justify-between">
                        <div>
                            <p class="font-medium text-sm">${i.nome_indicado}</p>
                            <p class="text-xs text-slate-500">${i.telefone}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">${i.data}</p>
                            <p class="text-xs ${i.status==='convertido'?'text-emerald-600':'text-amber-600'}">${i.status}</p>
                        </div>
                    </div>
                `).join('')}
            </div>
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

// Tela 10: Pesquisa de satisfação
async function telaPesquisa() {
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto">
        <div class="p-5 text-white" style="background:linear-gradient(135deg,${STATE.empresa.cor_primaria},${STATE.empresa.cor_secundaria})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-2"><i class="ri-arrow-left-line"></i> Voltar</button>
            <h1 class="text-xl font-bold">Avalie nosso atendimento</h1>
            <p class="text-white/80 text-sm">Sua opinião é muito importante</p>
        </div>
        <form id="form-pesquisa" class="p-6 space-y-4">
            <div>
                <p class="text-center mb-3 text-slate-700">Como você avalia sua experiência?</p>
                <div class="flex justify-center gap-3" id="estrelas">
                    ${[1,2,3,4,5].map(n => `
                        <button type="button" data-nota="${n}" class="estrela text-4xl text-slate-300 hover:text-amber-400 transition">
                            <i class="ri-star-fill"></i>
                        </button>
                    `).join('')}
                </div>
                <input type="hidden" name="nota" id="nota-input" required>
            </div>
            <div>
                <label class="text-sm font-medium">Comentário (opcional)</label>
                <textarea name="comentario" rows="4" placeholder="Conte como foi sua experiência..."
                          class="mt-1 w-full px-4 py-3 border border-slate-300 rounded-xl"></textarea>
            </div>
            <button class="w-full py-3 text-white rounded-xl font-semibold" style="background:${STATE.empresa.cor_primaria}">
                Enviar avaliação
            </button>
        </form>
    </div>`;

    document.querySelectorAll('.estrela').forEach((e) => {
        e.addEventListener('click', () => {
            const n = +e.dataset.nota;
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
        if (!fd.nota) return toast('Selecione uma nota', 'error');
        try {
            await api('/pesquisas', { method: 'POST', body: JSON.stringify(fd) });
            toast('Obrigado pela avaliação!', 'success');
            setTimeout(() => showScreen('home'), 1000);
        } catch (e) { toast(e.message, 'error'); }
    });
}

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
