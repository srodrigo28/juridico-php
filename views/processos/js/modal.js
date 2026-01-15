/**
 * modal.js
 * Controle do modal Novo Processo.
*/

// alert('xampp\htdocs\www\v2\views\processos\js\modal.js');
alert('Carregou modal.js');

window.Processos = window.Processos || {};
Processos.modal = {};

Processos.modal.init = function () {
  const modal = document.getElementById('modalNovoProcesso');
  if (!modal) return;

  modal.addEventListener('show.bs.modal', function () {
    const form = document.getElementById('formNovoProcesso');
    if (!form) return;

    form.reset();
    Processos.core.attachMasks(form);
    Processos.financeiro.init();
  });
};
