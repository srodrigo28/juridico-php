alert('xampp\htdocs\www\v2\views\processos\index.js')
/**
 * /processos/index.js
 * Script principal do módulo Processos.
 *
 * Dependências:
 * - Bootstrap (Modal / events: show.bs.modal)
 * - window.ProcessosData (injetado no index.php)
 *
 * Observação:
 * - Este arquivo foi organizado por seções para facilitar futuras atualizações.
 */

// =====================================================
// 1) DADOS VINDOS DO PHP
// =====================================================
const TRIBUNAIS = (window.ProcessosData && window.ProcessosData.TRIBUNAIS) ? window.ProcessosData.TRIBUNAIS : [];
const CLIENTES_ATIVOS = (window.ProcessosData && window.ProcessosData.CLIENTES_ATIVOS) ? window.ProcessosData.CLIENTES_ATIVOS : [];

// =====================================================
// 2) HELPERS (MOEDA / MÁSCARA)
// =====================================================

/**
 * Mantém somente dígitos.
 */
function __onlyDigits(v) {
  return (v || '').replace(/\D/g, '');
}

/**
 * Máscara de moeda BR para inputs (digitando).
 * Ex: "123456" -> "1.234,56"
 */
function maskCurrencyBR(v) {
  let d = __onlyDigits(v);
  if (!d) return '';
  if (d.length === 1) d = '0' + d;

  const cents = d.slice(-2);
  let ints = d.slice(0, -2);
  ints = ints.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  return (ints || '0') + ',' + cents;
}

/**
 * Normaliza "1.234,56" para "1234.56" (para enviar pro backend).
 */
function normalizeCurrencyToEN(v) {
  if (v == null) return '';
  let s = String(v).trim();
  if (!s) return '';
  s = s.replace(/[^0-9.,-]/g, '');
  s = s.replace(/\./g, '').replace(/,/g, '.');
  return s;
}

/**
 * Converte "1.234,56" (BR) -> número (1234.56).
 */
function parseCurrencyBR(str) {
  if (str == null) return 0;
  let s = String(str).trim();
  if (!s) return 0;

  s = s.replace(/[^0-9.,-]/g, '');
  s = s.replace(/\./g, '').replace(/,/g, '.');

  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}

/**
 * Formata número -> "1.234,56" (pt-BR).
 */
function formatCurrencyBRNumber(n) {
  const num = Number(n);
  if (!isFinite(num)) return '';
  return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Aplica máscara BR em campos monetários do formulário.
 * Campos:
 * - valor_causa
 * - fin_valor_total
 * - fin_valor_entrada
 */
function attachProcessMasks(formEl) {
  if (!formEl) return;

  const moneyNames = ['valor_causa', 'fin_valor_total', 'fin_valor_entrada'];

  moneyNames.forEach(name => {
    const inp = formEl.querySelector(`input[name="${name}"]`);
    if (!inp) return;

    // Evita duplicar listener
    if (inp.dataset.maskAttached === '1') return;
    inp.dataset.maskAttached = '1';

    inp.addEventListener('input', (e) => {
      e.target.value = maskCurrencyBR(e.target.value);
      e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
    });
  });
}

// =====================================================
// 3) FINANCEIRO (UI + CÁLCULOS)
// =====================================================

/**
 * Inicializa e controla o Financeiro do modal "Novo Processo".
 *
 * Regras:
 * - avista: não mostra entrada e não mostra parcelas
 * - entrada: mostra entrada + parcelas; parcela = (total - entrada) / n
 * - parcelado: mostra parcelas; parcela = total / n
 *
 * Também:
 * - limpa campos escondidos (evita enviar valores ocultos)
 * - mostra alerta no resumo se entrada > total (sem bloquear)
 */
function initFinanceiroNovoProcesso() {
  // --- elementos principais
  const tipo = document.getElementById('finTipoPagamento');
  const blocoEntrada = document.getElementById('finBlocoEntrada');
  const blocoParcelas = document.getElementById('finBlocoParcelas');

  // --- campos de entrada
  const inpTotal = document.getElementById('finValorTotal');       // honorários
  const inpEntrada = document.getElementById('finValorEntrada');   // entrada
  const selParcelas = document.getElementById('finNumParcelas');   // qtd parcelas
  const selDia = document.getElementById('finDiaVencimento');      // dia venc.

  // --- saída/resumo
  const wrapValorParcela = document.getElementById('finResumoParcelaWrap');
  const outValorParcela = document.getElementById('finValorParcelaCalculado');
  const resumoBox = document.getElementById('finResumoBox');

  // Se não existir o básico, sai silenciosamente
  if (!tipo || !blocoEntrada || !blocoParcelas || !resumoBox) return;

  // helpers UI
  function show(el) { if (el) el.style.display = ''; }
  function hide(el) { if (el) el.style.display = 'none'; }
  function setResumo(html) { resumoBox.innerHTML = html; }
  function clearCalculated() { if (outValorParcela) outValorParcela.value = ''; }

  // Limpa campos ao esconder blocos (evita enviar “lixo” no FormData futuramente)
  function clearEntradaFields() {
    if (inpEntrada) inpEntrada.value = '';
  }
  function clearParcelasFields() {
    if (selParcelas) selParcelas.value = '1';
    if (selDia) selDia.value = '';
  }

  /**
   * Mostra/oculta blocos conforme tipo
   */
  function applyVisibility() {
    const v = tipo.value;

    // reset visual
    hide(blocoEntrada);
    hide(blocoParcelas);
    hide(wrapValorParcela);
    clearCalculated();

    // regras por tipo
    if (v === 'avista') {
      clearEntradaFields();
      clearParcelasFields();
      setResumo('<span class="small">Pagamento: <strong>À vista</strong></span>');
      return;
    }

    if (v === 'entrada') {
      show(blocoEntrada);
      show(blocoParcelas);
      show(wrapValorParcela);
      return;
    }

    if (v === 'parcelado') {
      clearEntradaFields();
      show(blocoParcelas);
      show(wrapValorParcela);
      return;
    }
  }

  /**
   * Recalcula parcela e atualiza o resumo
   */
  function recalc() {
    const v = tipo.value;

    const total = inpTotal ? parseCurrencyBR(inpTotal.value) : 0;
    const entrada = inpEntrada ? parseCurrencyBR(inpEntrada.value) : 0;
    const parcelas = selParcelas ? Math.max(1, parseInt(selParcelas.value || '1', 10)) : 1;

    if (v === 'avista') {
      setResumo(`
        <span class="small">
          Pagamento: <strong>À vista</strong> • Total: <strong>R$ ${formatCurrencyBRNumber(total)}</strong>
        </span>
      `);
      clearCalculated();
      return;
    }

    if (v === 'parcelado') {
      const valorParcela = total / parcelas;

      if (outValorParcela) outValorParcela.value = formatCurrencyBRNumber(valorParcela);

      setResumo(`
        <div class="small">
          Pagamento: <strong>Parcelado</strong><br>
          Total: <strong>R$ ${formatCurrencyBRNumber(total)}</strong> • Parcelas: <strong>${parcelas}x</strong><br>
          Parcela: <strong>R$ ${formatCurrencyBRNumber(valorParcela)}</strong>
        </div>
      `);
      return;
    }

    if (v === 'entrada') {
      const entradaMaior = entrada > total;
      const restante = Math.max(0, total - entrada);
      const valorParcela = restante / parcelas;

      if (outValorParcela) outValorParcela.value = formatCurrencyBRNumber(valorParcela);

      setResumo(`
        <div class="small">
          ${entradaMaior ? '<div class="text-danger"><strong>Atenção:</strong> entrada maior que o total.</div>' : ''}
          Pagamento: <strong>Entrada + Parcelas</strong><br>
          Total: <strong>R$ ${formatCurrencyBRNumber(total)}</strong> • Entrada: <strong>R$ ${formatCurrencyBRNumber(entrada)}</strong><br>
          Restante: <strong>R$ ${formatCurrencyBRNumber(restante)}</strong> • Parcelas: <strong>${parcelas}x</strong><br>
          Parcela: <strong>R$ ${formatCurrencyBRNumber(valorParcela)}</strong>
        </div>
      `);
      return;
    }
  }

  /**
   * Bind de eventos (uma vez só)
   */
  if (tipo.dataset.finListener !== '1') {
    tipo.dataset.finListener = '1';

    tipo.addEventListener('change', () => {
      applyVisibility();
      recalc();
    });

    if (inpTotal) inpTotal.addEventListener('input', recalc);
    if (inpEntrada) inpEntrada.addEventListener('input', recalc);
    if (selParcelas) selParcelas.addEventListener('change', recalc);
  }

  // exec inicial
  applyVisibility();
  recalc();
}

// =====================================================
// 4) UPLOADS (add + preview múltiplo + validação opcional)
// =====================================================

/**
 * Adiciona um novo bloco de upload.
 *
 * IMPORTANTE:
 * - Upload é opcional => não usamos "required"
 * - Se quiser permitir vários arquivos num input, adicione "multiple" no HTML OU aqui no template.
 */
window.adicionarUpload = function () {
  const container = document.getElementById('uploadsContainer');
  if (!container) return;

  const div = document.createElement('div');
  div.className = 'input-group mb-2 upload-group';
  div.innerHTML = `
    <input type="text" class="form-control" name="upload_titulo[]" placeholder="Título do arquivo (opcional)">
    <input type="file" class="form-control upload-file" name="uploads[]"
           accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
    <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
  `;
  container.appendChild(div);
};

/**
 * Validação opcional de uploads (DESLIGADA por padrão).
 * Você pode ligar no click de salvar:
 *
 * const vup = validarUploadsOpcional(form, { exigirAoMenosUmArquivo: true, exigirTituloQuandoTemArquivo: true });
 * if (!vup.ok) { alert(vup.msg); return; }
 */
function validarUploadsOpcional(formEl, opts = {}) {
  const {
    exigirAoMenosUmArquivo = false,
    exigirTituloQuandoTemArquivo = true
  } = opts;

  const grupos = Array.from(formEl.querySelectorAll('#uploadsContainer .upload-group'));
  const arquivosSelecionados = [];
  const problemas = [];

  grupos.forEach((g, idx) => {
    const tituloEl = g.querySelector('input[name="upload_titulo[]"]');
    const fileEl = g.querySelector('input[type="file"][name="uploads[]"]');

    const titulo = (tituloEl?.value || '').trim();
    const files = fileEl?.files ? Array.from(fileEl.files) : [];

    if (files.length) {
      arquivosSelecionados.push(...files);

      if (exigirTituloQuandoTemArquivo && !titulo) {
        problemas.push(`Informe o título do arquivo no upload #${idx + 1}.`);
      }
    }
  });

  if (exigirAoMenosUmArquivo && arquivosSelecionados.length === 0) {
    problemas.push('Adicione pelo menos 1 arquivo.');
  }

  if (problemas.length) return { ok: false, msg: problemas[0] };
  return { ok: true, msg: '' };
}

/**
 * Preview múltiplo por delegação:
 * - Se o input tiver multiple e selecionar 5 arquivos => mostra 5 previews.
 * - Imagem => img
 * - PDF => iframe
 * - Outros => mensagem
 */
(function initUploadsMultiPreview() {
  const MAX_PREVIEWS = 10; // limite para evitar travar

  function ensurePreviewBox(group, input) {
    let box = group.querySelector('.upload-preview-box');
    if (!box) {
      box = document.createElement('div');
      box.className = 'upload-preview-box border rounded p-2 bg-light mt-2';
      input.insertAdjacentElement('afterend', box);
    }
    return box;
  }

  function renderFileItem(file) {
    const wrap = document.createElement('div');
    wrap.className = 'mb-2';

    const meta = document.createElement('div');
    meta.className = 'small text-muted mb-1';
    meta.textContent = file.name || 'Arquivo';
    wrap.appendChild(meta);

    const type = (file.type || '').toLowerCase();
    const name = (file.name || '').toLowerCase();

    // imagem
    if (type.startsWith('image/')) {
      const url = URL.createObjectURL(file);
      const img = document.createElement('img');
      img.src = url;
      img.alt = file.name || 'Preview';
      img.style.maxWidth = '100%';
      img.style.height = 'auto';
      img.style.borderRadius = '.25rem';
      img.onload = () => URL.revokeObjectURL(url);
      wrap.appendChild(img);
      return wrap;
    }

    // pdf
    if (type === 'application/pdf' || name.endsWith('.pdf')) {
      const url = URL.createObjectURL(file);
      const iframe = document.createElement('iframe');
      iframe.src = url;
      iframe.title = file.name || 'PDF Preview';
      iframe.style.width = '100%';
      iframe.style.height = '240px';
      iframe.style.border = '0';
      iframe.style.borderRadius = '.25rem';
      iframe.style.background = '#fff';
      iframe.onload = () => setTimeout(() => URL.revokeObjectURL(url), 3000);
      wrap.appendChild(iframe);
      return wrap;
    }

    // outros
    const msg = document.createElement('div');
    msg.className = 'text-muted small';
    msg.textContent = 'Este tipo de arquivo não possui pré-visualização.';
    wrap.appendChild(msg);
    return wrap;
  }

  document.addEventListener('change', function (e) {
    const input = e.target;
    if (!input.matches('input[type="file"][name="uploads[]"]')) return;

    const group = input.closest('.upload-group');
    if (!group) return;

    const box = ensurePreviewBox(group, input);
    box.innerHTML = '';

    const files = input.files ? Array.from(input.files) : [];
    if (!files.length) return;

    const list = files.slice(0, MAX_PREVIEWS);
    list.forEach(f => box.appendChild(renderFileItem(f)));

    if (files.length > MAX_PREVIEWS) {
      const note = document.createElement('div');
      note.className = 'text-muted small';
      note.textContent = `Mostrando ${MAX_PREVIEWS} de ${files.length} arquivos.`;
      box.appendChild(note);
    }
  });
})();

// =====================================================
// 5) FILTRO DA TABELA (CLIENTE)
// =====================================================

function filtrarProcessos() {
  const q = (document.getElementById('buscarProcessoCliente')?.value || '').toLowerCase().trim();
  const linhas = document.querySelectorAll('#tabelaProcessos tbody tr');
  let visiveis = 0;

  linhas.forEach(linha => {
    const clienteCol = linha.querySelector('td:nth-child(2)');
    const nomeCliente = (clienteCol?.textContent || '').toLowerCase();
    const mostrar = q ? nomeCliente.includes(q) : true;

    linha.style.display = mostrar ? '' : 'none';
    if (mostrar) visiveis++;
  });

  const badge = document.getElementById('badgeResultadosProc');
  if (badge) badge.textContent = `Exibindo: ${visiveis}`;

  const titulo = document.getElementById('tituloContagemProc');
  if (titulo) titulo.textContent = `(${visiveis})`;

  const noRes = document.getElementById('noResultsProc');
  if (noRes) noRes.style.display = visiveis === 0 ? '' : 'none';
}

// =====================================================
// 6) INIT GERAL
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
  // --- filtro da lista
  const inputBuscar = document.getElementById('buscarProcessoCliente');
  if (inputBuscar) inputBuscar.addEventListener('input', filtrarProcessos);
  try { filtrarProcessos(); } catch (e) { }

  // --- init modal novo processo
  const novoProcModal = document.getElementById('modalNovoProcesso');
  if (novoProcModal) {
    novoProcModal.addEventListener('show.bs.modal', function () {
      const form = document.getElementById('formNovoProcesso');
      if (!form) return;

      // Reset do form (limpa inputs)
      form.reset();

      // Máscaras BR
      attachProcessMasks(form);

      // Financeiro (UI + cálculos)
      initFinanceiroNovoProcesso();

      // Limpar previews (opcional)
      form.querySelectorAll('.upload-preview-box').forEach(b => b.innerHTML = '');
    });
  }

  // --- botão salvar do novo processo
  const btnSalvarNovo = document.getElementById('btnSalvarNovoProcesso');
  if (btnSalvarNovo) {
    btnSalvarNovo.addEventListener('click', () => {
      // =====================================================
      // VALIDAÇÃO OPCIONAL DE UPLOADS (DESLIGADA POR PADRÃO)
      // =====================================================
      /*
      const form = document.getElementById('formNovoProcesso');
      const vup = validarUploadsOpcional(form, {
        exigirAoMenosUmArquivo: true,
        exigirTituloQuandoTemArquivo: true
      });
      if (!vup.ok) {
        alert(vup.msg);
        return;
      }
      */

      // Aqui você chama sua função existente (cadastrar processo)
      if (typeof window.salvarProcesso === 'function') window.salvarProcesso();
    });
  }
});
