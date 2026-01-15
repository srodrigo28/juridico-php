/**
 * tabela.js
 * Filtro da tabela de processos por cliente.
*/

// alert('xampp\htdocs\www\v2\views\processos\js\tabela.js');
alert('Carregou tabela.js');

window.Processos = window.Processos || {};
Processos.tabela = {};

Processos.tabela.init = function () {
  const input = document.getElementById('buscarProcessoCliente');
  if (!input) return;

  input.addEventListener('input', function () {
    const termo = input.value.toLowerCase();
    document.querySelectorAll('#tabelaProcessos tbody tr').forEach(tr => {
      const cliente = tr.children[1]?.textContent.toLowerCase() || '';
      tr.style.display = cliente.includes(termo) ? '' : 'none';
    });
  });
};
