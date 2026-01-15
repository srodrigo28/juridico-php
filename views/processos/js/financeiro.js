/**
 * financeiro.js
 * Toda a lógica de pagamento:
 * - à vista
 * - entrada + parcelas
 * - parcelado
 */

// alert('xampp\htdocs\www\v2\views\processos\js\financeiro.js')
alert('Carregado financeiro.js')

window.Processos = window.Processos || {};
Processos.financeiro = {};

Processos.financeiro.getEls = function () {
  return {
    tipo: document.getElementById('finTipoPagamento'),
    total: document.getElementById('finValorTotal'),
    entrada: document.getElementById('finValorEntrada'),
    parcelas: document.getElementById('finNumParcelas'),
    resumo: document.getElementById('finResumoBox'),
    valorParcela: document.getElementById('finValorParcelaCalculado'),
    blocoEntrada: document.getElementById('finBlocoEntrada'),
    blocoParcelas: document.getElementById('finBlocoParcelas'),
    wrapParcela: document.getElementById('finResumoParcelaWrap')
  };
};

Processos.financeiro.init = function () {
  const els = Processos.financeiro.getEls();
  if (!els.tipo) return;

  const core = Processos.core;

  function atualizarVisibilidade() {
    core.hide(els.blocoEntrada);
    core.hide(els.blocoParcelas);
    core.hide(els.wrapParcela);

    if (els.tipo.value === 'entrada') {
      core.show(els.blocoEntrada);
      core.show(els.blocoParcelas);
      core.show(els.wrapParcela);
    }

    if (els.tipo.value === 'parcelado') {
      core.show(els.blocoParcelas);
      core.show(els.wrapParcela);
    }
  }

  function recalcular() {
    const tipo = els.tipo.value;
    const total = core.parseCurrencyBR(els.total?.value);
    const entrada = core.parseCurrencyBR(els.entrada?.value);
    const parcelas = parseInt(els.parcelas?.value || 1, 10);

    let html = '';

    if (tipo === 'avista') {
      html = `À vista • Total: <strong>R$ ${core.formatCurrencyBR(total)}</strong>`;
      if (els.valorParcela) els.valorParcela.value = '';
    }

    if (tipo === 'parcelado') {
      const valorParcela = total / parcelas;
      if (els.valorParcela) els.valorParcela.value = core.formatCurrencyBR(valorParcela);
      html = `Parcelado • ${parcelas}x de <strong>R$ ${core.formatCurrencyBR(valorParcela)}</strong>`;
    }

    if (tipo === 'entrada') {
      const restante = Math.max(0, total - entrada);
      const valorParcela = restante / parcelas;
      if (els.valorParcela) els.valorParcela.value = core.formatCurrencyBR(valorParcela);

      html = `
        Entrada: <strong>R$ ${core.formatCurrencyBR(entrada)}</strong><br>
        ${parcelas}x de <strong>R$ ${core.formatCurrencyBR(valorParcela)}</strong>
      `;
    }

    if (els.resumo) els.resumo.innerHTML = html || '<span class="text-muted">Informe os valores</span>';
  }

  if (!els.tipo.dataset.binded) {
    els.tipo.dataset.binded = '1';
    els.tipo.addEventListener('change', () => {
      atualizarVisibilidade();
      recalcular();
    });

    els.total?.addEventListener('input', recalcular);
    els.entrada?.addEventListener('input', recalcular);
    els.parcelas?.addEventListener('change', recalcular);
  }

  atualizarVisibilidade();
  recalcular();
};
