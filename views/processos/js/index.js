/**
 * =====================================================
 * /views/processos/js/index.js
 * Script principal do módulo Processos
 *
 * DEPENDÊNCIAS:
 * - Bootstrap 5 (Modal)
 * - window.ProcessosData (injetado pelo PHP)
 *
 * OBS:
 * - Este arquivo contém um "Route Tester" interno
 * - O tester pode ser ligado/desligado por flag
 * =====================================================
 */

// alert('xampp\htdocs\www\v2\views\processos\js\index.js')
alert('Carregou index.js')


/* =====================================================
 * 0) FLAG GLOBAL DE DEBUG (LIGAR / DESLIGAR)
 * =====================================================
 *
 * 👉 Para ATIVAR testes de rota:
 *    const PROCESSOS_DEBUG = true;
 *
 * 👉 Para DESATIVAR:
 *    const PROCESSOS_DEBUG = false;
 */
const PROCESSOS_DEBUG = true; // <<< TROQUE PARA false QUANDO QUISER


/* =====================================================
 * 1) DADOS VINDOS DO PHP
 * ===================================================== */
const TRIBUNAIS = window.ProcessosData?.TRIBUNAIS || [];
const CLIENTES_ATIVOS = window.ProcessosData?.CLIENTES_ATIVOS || [];


/* =====================================================
 * 2) ROUTE TESTER (DEBUG DE CARREGAMENTO DE ARQUIVOS)
 * =====================================================
 *
 * O QUE FAZ:
 * - Testa se CSS/JS estão acessíveis (200 / 404)
 * - Mostra no console
 * - Usa HEAD e faz fallback para GET
 *
 * COMO USAR:
 * - Basta deixar PROCESSOS_DEBUG = true
 * - Ajustar a lista de URLs abaixo
 * ===================================================== */
async function testarRotasProcessos() {
  if (!PROCESSOS_DEBUG) return;

  const urls = [
    '/www/v2/views/processos/styles.css',
    '/www/v2/views/processos/js/index.js'
    // futuramente:
    // '/www/v2/views/processos/js/financeiro.js',
    // '/www/v2/views/processos/js/uploads.js'
  ];

  console.groupCollapsed('🧪 [Processos] Teste de rotas');

  async function testar(url) {
    const start = performance.now();
    try {
      let res;
      try {
        res = await fetch(url, { method: 'HEAD', cache: 'no-store' });
      } catch {
        res = await fetch(url, { method: 'GET', cache: 'no-store' });
      }

      const ms = Math.round(performance.now() - start);

      if (res.ok) {
        console.log(`✅ ${url} [${res.status}] (${ms}ms)`);
      } else {
        console.warn(`❌ ${url} [${res.status}] (${ms}ms)`);
      }
    } catch (err) {
      console.error(`💥 ${url} ERRO`, err);
    }
  }

  for (const url of urls) {
    await testar(url);
  }

  console.groupEnd();
}


/* =====================================================
 * 3) HELPERS DE MOEDA (BR)
 * ===================================================== */
function __onlyDigits(v) {
  return (v || '').replace(/\D/g, '');
}

function maskCurrencyBR(v) {
  let d = __onlyDigits(v);
  if (!d) return '';
  if (d.length === 1) d = '0' + d;

  const cents = d.slice(-2);
  let ints = d.slice(0, -2);
  ints = ints.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  return (ints || '0') + ',' + cents;
}

function parseCurrencyBR(str) {
  if (!str) return 0;
  return Number(str.replace(/\./g, '').replace(',', '.')) || 0;
}

function formatCurrencyBRNumber(n) {
  return Number(n || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}


/* =====================================================
 * 4) MÁSCARAS DE INPUT
 * ===================================================== */
function attachProcessMasks(formEl) {
  if (!formEl) return;

  const fields = ['valor_causa', 'fin_valor_total', 'fin_valor_entrada'];

  fields.forEach(name => {
    const inp = formEl.querySelector(`input[name="${name}"]`);
    if (!inp || inp.dataset.maskAttached) return;

    inp.dataset.maskAttached = '1';
    inp.addEventListener('input', e => {
      e.target.value = maskCurrencyBR(e.target.value);
      e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
    });
  });
}


/* =====================================================
 * 5) FINANCEIRO – LÓGICA PRINCIPAL
 * ===================================================== */
function initFinanceiroNovoProcesso() {
  const tipo = document.getElementById('finTipoPagamento');
  const blocoEntrada = document.getElementById('finBlocoEntrada');
  const blocoParcelas = document.getElementById('finBlocoParcelas');
  const resumoBox = document.getElementById('finResumoBox');
  const wrapParcela = document.getElementById('finResumoParcelaWrap');
  const outParcela = document.getElementById('finValorParcelaCalculado');

  const inpTotal = document.getElementById('finValorTotal');
  const inpEntrada = document.getElementById('finValorEntrada');
  const selParcelas = document.getElementById('finNumParcelas');

  if (!tipo) return;

  function esconderTudo() {
    blocoEntrada.style.display = 'none';
    blocoParcelas.style.display = 'none';
    wrapParcela.style.display = 'none';
  }

  function recalcular() {
    const modo = tipo.value;
    const total = parseCurrencyBR(inpTotal?.value);
    const entrada = parseCurrencyBR(inpEntrada?.value);
    const parcelas = Math.max(1, Number(selParcelas?.value || 1));

    esconderTudo();

    if (modo === 'avista') {
      resumoBox.innerHTML = `
        <strong>À vista</strong><br>
        Total: R$ ${formatCurrencyBRNumber(total)}
      `;
      return;
    }

    if (modo === 'parcelado') {
      blocoParcelas.style.display = '';
      wrapParcela.style.display = '';

      const valorParcela = total / parcelas;
      outParcela.value = formatCurrencyBRNumber(valorParcela);

      resumoBox.innerHTML = `
        <strong>Parcelado</strong><br>
        ${parcelas}x de R$ ${formatCurrencyBRNumber(valorParcela)}
      `;
      return;
    }

    if (modo === 'entrada') {
      blocoEntrada.style.display = '';
      blocoParcelas.style.display = '';
      wrapParcela.style.display = '';

      const restante = Math.max(0, total - entrada);
      const valorParcela = restante / parcelas;
      outParcela.value = formatCurrencyBRNumber(valorParcela);

      resumoBox.innerHTML = `
        <strong>Entrada + Parcelas</strong><br>
        Entrada: R$ ${formatCurrencyBRNumber(entrada)}<br>
        ${parcelas}x de R$ ${formatCurrencyBRNumber(valorParcela)}
      `;
    }
  }

  if (!tipo.dataset.listener) {
    tipo.dataset.listener = '1';
    tipo.addEventListener('change', recalcular);
    inpTotal?.addEventListener('input', recalcular);
    inpEntrada?.addEventListener('input', recalcular);
    selParcelas?.addEventListener('change', recalcular);
  }

  recalcular();
}


/* =====================================================
 * 6) UPLOADS – MÚLTIPLOS + PREVIEW
 * ===================================================== */
window.adicionarUpload = function () {
  const container = document.getElementById('uploadsContainer');
  if (!container) return;

  const div = document.createElement('div');
  div.className = 'input-group mb-2 upload-group';
  div.innerHTML = `
    <input type="text" class="form-control" name="upload_titulo[]" placeholder="Título (opcional)">
    <input type="file" class="form-control" name="uploads[]"
           accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" multiple>
    <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
  `;
  container.appendChild(div);
};

document.addEventListener('change', function (e) {
  const input = e.target;
  if (!input.matches('input[type="file"][name="uploads[]"]')) return;

  const group = input.closest('.upload-group');
  if (!group) return;

  let box = group.querySelector('.upload-preview-box');
  if (!box) {
    box = document.createElement('div');
    box.className = 'upload-preview-box border rounded p-2 bg-light mt-2';
    input.after(box);
  }

  box.innerHTML = '';

  [...input.files].slice(0, 10).forEach(file => {
    const p = document.createElement('div');
    p.className = 'small mb-2';
    p.textContent = file.name;
    box.appendChild(p);
  });
});


/* =====================================================
 * 7) FILTRO DA TABELA
 * ===================================================== */
function filtrarProcessos() {
  const q = document.getElementById('buscarProcessoCliente')?.value.toLowerCase() || '';
  const rows = document.querySelectorAll('#tabelaProcessos tbody tr');
  let visiveis = 0;

  rows.forEach(r => {
    const nome = r.children[1]?.textContent.toLowerCase() || '';
    const ok = !q || nome.includes(q);
    r.style.display = ok ? '' : 'none';
    if (ok) visiveis++;
  });

  document.getElementById('badgeResultadosProc')?.textContent = `Exibindo: ${visiveis}`;
  document.getElementById('tituloContagemProc')?.textContent = `(${visiveis})`;
}


/* =====================================================
 * 8) INIT GERAL
 * ===================================================== */
document.addEventListener('DOMContentLoaded', function () {
  console.log('✅ Processos index.js carregado');

  testarRotasProcessos();

  document.getElementById('buscarProcessoCliente')
    ?.addEventListener('input', filtrarProcessos);

  const modal = document.getElementById('modalNovoProcesso');
  modal?.addEventListener('show.bs.modal', function () {
    const form = document.getElementById('formNovoProcesso');
    if (!form) return;

    form.reset();
    attachProcessMasks(form);
    initFinanceiroNovoProcesso();
  });
});
