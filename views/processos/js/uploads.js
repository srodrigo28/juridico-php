/**
 * uploads.js
 * Upload opcional com preview múltiplo.
 */

alert('uploads carregado')

window.Processos = window.Processos || {};
Processos.uploads = {};

Processos.uploads.add = function () {
  const container = document.getElementById('uploadsContainer');
  if (!container) return;

  const div = document.createElement('div');
  div.className = 'input-group mb-2 upload-group';
  div.innerHTML = `
    <input type="text" class="form-control" name="upload_titulo[]" placeholder="Título (opcional)">
    <input type="file" class="form-control" name="uploads[]" multiple>
    <button type="button" class="btn btn-outline-danger" onclick="this.parentNode.remove()">Remover</button>
  `;
  container.appendChild(div);
};

Processos.uploads.initPreview = function () {
  document.addEventListener('change', function (e) {
    if (!e.target.matches('input[type="file"][name="uploads[]"]')) return;

    const group = e.target.closest('.upload-group');
    if (!group) return;

    let box = group.querySelector('.upload-preview-box');
    if (!box) {
      box = document.createElement('div');
      box.className = 'upload-preview-box mt-2 p-2 border rounded bg-light';
      e.target.after(box);
    }

    box.innerHTML = '';
    Array.from(e.target.files).forEach(file => {
      const div = document.createElement('div');
      div.className = 'small text-muted';
      div.textContent = file.name;
      box.appendChild(div);
    });
  });
};
