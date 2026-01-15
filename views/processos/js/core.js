/**
 * core.js
 * Funções utilitárias e helpers reutilizáveis do módulo Processos.
 * NÃO depende de DOM específico.
*/

// alert('xampp\htdocs\www\v2\views\processos\js\core.js')
alert('Carregado Core.js')

window.Processos = window.Processos || {};
Processos.core = {};

/* ================================
 * Helpers gerais
 * ================================ */
Processos.core.onlyDigits = function (v) {
  return (v || '').replace(/\D/g, '');
};

Processos.core.show = function (el) {
  if (el) el.style.display = '';
};

Processos.core.hide = function (el) {
  if (el) el.style.display = 'none';
};

/* ================================
 * Moeda BR
 * ================================ */
Processos.core.maskCurrencyBR = function (v) {
  let d = Processos.core.onlyDigits(v);
  if (!d) return '';
  if (d.length === 1) d = '0' + d;

  const cents = d.slice(-2);
  let ints = d.slice(0, -2);
  ints = ints.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  return (ints || '0') + ',' + cents;
};

Processos.core.parseCurrencyBR = function (str) {
  if (!str) return 0;
  let s = String(str).replace(/[^0-9.,-]/g, '');
  s = s.replace(/\./g, '').replace(/,/g, '.');
  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
};

Processos.core.formatCurrencyBR = function (n) {
  const num = Number(n);
  if (!isFinite(num)) return '';
  return num.toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

/* ================================
 * Máscaras de formulário
 * ================================ */
Processos.core.attachMasks = function (formEl) {
  if (!formEl) return;

  const fields = ['valor_causa', 'fin_valor_total', 'fin_valor_entrada'];

  fields.forEach(name => {
    const input = formEl.querySelector(`input[name="${name}"]`);
    if (!input || input.dataset.masked) return;

    input.dataset.masked = '1';
    input.addEventListener('input', e => {
      e.target.value = Processos.core.maskCurrencyBR(e.target.value);
      e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
    });
  });
};
