(function(){
  // Cliente Autocomplete - suporta múltiplos campos com atributo data-cliente-search
  function initInput(input){
    if (!input) return;
    const hidden = document.querySelector(input.getAttribute('data-target-hidden'));
    const suggestEl = document.querySelector(input.getAttribute('data-suggestions'));
    const statusEl = document.querySelector('[data-cliente-search-status]');
    let debounceTimer = null;
    let currentList = [];
    let activeIndex = -1;

    function clearSuggestions(){ suggestEl.innerHTML = ''; currentList = []; activeIndex = -1; }
    function escapeHtml(s){ return String(s).replace(/[&<>\"'`]/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;' }[c])); }

    function renderSuggestions(list, query){
      clearSuggestions();
      currentList = list || [];
      if (!list || list.length === 0){
        const div = document.createElement('div');
        div.className = 'p-2';
        div.innerHTML = `<div class="text-muted">Nenhum cliente encontrado</div><div class="mt-2"><button class="btn btn-sm btn-outline-primary" id="criarClienteInline">Criar cliente "${escapeHtml(query)}"</button></div>`;
        suggestEl.appendChild(div);
        const btn = document.getElementById('criarClienteInline');
        if (btn) btn.addEventListener('click', ()=> criarClienteInline(query));
        return;
      }
      const container = document.createElement('div');
      container.className = 'list-group';
      list.forEach((c, idx) => {
        const a = document.createElement('button');
        a.type = 'button';
        a.className = 'list-group-item list-group-item-action';
        a.textContent = c.nome;
        a.dataset.id = c.id;
        a.dataset.index = idx;
        a.setAttribute('role','option');
        a.addEventListener('click', ()=> selectCliente(c));
        container.appendChild(a);
      });
      suggestEl.appendChild(container);
    }

    function selectCliente(c){
      input.value = c.nome;
      if (hidden) hidden.value = c.id;
      clearSuggestions();
      if (statusEl) statusEl.textContent = 'Cliente selecionado';
    }

    async function criarClienteInline(nome){
      if (!nome || nome.trim().length < 3) return alert('Informe um nome válido');
      const fd = new FormData();
      fd.append('action','cadastrar_cliente');
      fd.append('nome', nome);
      fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
      try{
        const r = await fetch('', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.success){
          const cliente = j.cliente || { id: j.id, nome: nome };
          selectCliente(cliente);
          if (typeof mostrarSucesso === 'function') mostrarSucesso('Cliente criado e selecionado');
        } else {
          if (typeof mostrarErro === 'function') mostrarErro(j.message || 'Erro ao criar cliente');
        }
      }catch(e){ if (typeof mostrarErro === 'function') mostrarErro('Erro ao criar cliente'); }
    }

    async function buscarServidor(q){
      const fd = new FormData();
      fd.append('action','buscar_clientes');
      fd.append('q', q);
      fd.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
      const res = await fetch('', { method: 'POST', body: fd });
      const j = await res.json();
      if (j.success) return j.clientes || [];
      return [];
    }

    function focusSuggestion(index){
      const items = suggestEl.querySelectorAll('.list-group-item');
      if (!items || items.length === 0) return;
      items.forEach(it => it.classList.remove('active'));
      const target = items[index];
      if (target){ target.classList.add('active'); target.focus(); }
      activeIndex = index;
    }

    function debounceFetch(q){
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(async ()=>{
        if (!q || q.trim().length < 1){
          try{ const initial = window.CLIENTES_ATIVOS || []; renderSuggestions(initial.slice(0,10), q); if (statusEl) statusEl.textContent = 'Selecione um cliente'; }catch(e){ clearSuggestions(); }
          return;
        }
        if (statusEl) statusEl.textContent = 'Buscando...';
        const list = await buscarServidor(q);
        renderSuggestions(list, q);
        if (statusEl) statusEl.textContent = list.length ? 'Selecione um cliente' : 'Nenhum resultado';
      }, 250);
    }

    input.addEventListener('input', (e)=>{ if (hidden) hidden.value = ''; debounceFetch(e.target.value); });

    input.addEventListener('keydown', (e)=>{
      const items = suggestEl.querySelectorAll('.list-group-item');
      if (e.key === 'ArrowDown'){
        e.preventDefault();
        if (items.length === 0) return;
        const next = Math.min(activeIndex + 1, items.length - 1);
        focusSuggestion(next);
      } else if (e.key === 'ArrowUp'){
        e.preventDefault();
        if (items.length === 0) return;
        const prev = Math.max(activeIndex - 1, 0);
        focusSuggestion(prev);
      } else if (e.key === 'Enter'){
        if (activeIndex >= 0){
          const itemsArr = Array.from(items);
          const sel = itemsArr[activeIndex];
          if (sel) sel.click();
          e.preventDefault();
        }
      } else if (e.key === 'Escape'){
        clearSuggestions();
      }
    });

    document.addEventListener('click', (e)=>{ if (!suggestEl.contains(e.target) && e.target !== input) clearSuggestions(); });

    // Inicializar com lista local
    try{ const initial = window.CLIENTES_ATIVOS || []; renderSuggestions(initial.slice(0,10), ''); }catch(e){}
  }

  function initAll(){
    const inputs = document.querySelectorAll('[data-cliente-search]');
    inputs.forEach(initInput);
  }

  // Auto init
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll); else initAll();
})();
