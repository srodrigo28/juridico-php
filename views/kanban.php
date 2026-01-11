<?php
// Kanban simples baseado em Bootstrap
?>
<div class="py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">Kanban de Tarefas</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" id="addCardBtn"><i class="bi bi-plus-lg"></i> Novo card</button>
            <button class="btn btn-outline-secondary btn-sm" id="resetKanbanBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
        </div>
    </div>

    <div class="row g-3" id="kanbanBoard">
        <!-- Coluna: Tarefas -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light fw-bold tarefas d-flex justify-content-between align-items-center">
                    <span>Tarefas</span>
                    <span class="badge rounded-pill count-badge count-badge-tarefas" id="count-tarefas">0</span>
                </div>
                <div class="card-body kanban-column" data-column="tarefas"></div>
            </div>
        </div>

        <!-- Coluna: Em Progresso -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light fw-bold doing d-flex justify-content-between align-items-center">
                    <span>Em Progresso</span>
                    <span class="badge rounded-pill count-badge count-badge-doing" id="count-doing">0</span>
                </div>
                <div class="card-body kanban-column" data-column="doing"></div>
            </div>
        </div>

        <!-- Coluna: Concluído -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light fw-bold done d-flex justify-content-between align-items-center">
                    <span>Concluído</span>
                    <span class="badge rounded-pill count-badge count-badge-done" id="count-done">0</span>
                </div>
                <div class="card-body kanban-column" data-column="done"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Novo Card -->
<div class="modal fade" id="modalNovoCard" tabindex="-1" aria-labelledby="modalNovoCardLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovoCardLabel">Novo Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formNovoCard" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="novoTitulo" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="novoTitulo" name="titulo" placeholder="Ex.: Título da tarefa" required minlength="3">
                        <div class="invalid-feedback">Informe um nome com pelo menos 3 caracteres.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="novoPrioridade" class="form-label">Prioridade</label>
                            <select class="form-select" id="novoPrioridade" name="prioridade" required>
                                <option value="" selected disabled>Selecione...</option>
                                <option value="1">1 - Prioridade</option>
                                <option value="2">2 - Atenção</option>
                                <option value="3">3 - Aguarde</option>
                            </select>
                            <div class="invalid-feedback">Selecione a prioridade.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="novoPrevista" class="form-label">Data de vencimento / previsão</label>
                            <input type="date" class="form-control" id="novoPrevista" name="data_prevista">
                            <div class="form-text">Opcional: defina uma data prevista de conclusão.</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="novoDescricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="novoDescricao" name="descricao" rows="3" placeholder="Breve descrição da tarefa" required minlength="5"></textarea>
                        <div class="invalid-feedback">Descreva com pelo menos 5 caracteres.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger d-none" id="deleteFromEditBtn"><i class="bi bi-trash"></i> Excluir</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
    
</div>

<style>
/* Estilos mínimos para Kanban */
.kanban-column { height: 90vh; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; }
.kanban-card { background: #fff; border: 1px solid var(--border-color); border-radius: .5rem; padding: .5rem .75rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); cursor: grab; }
.kanban-card:active { cursor: grabbing; }
.kanban-column.drag-over { background: rgba(37,99,235,.06); outline: 2px dashed var(--border-color); }

/* Cores intuitivas por coluna (versão inicial aprovada) */
.card-header.tarefas { background-color: #ecfdf5 !important; color: #065f46; border-bottom: 2px solid #a7f3d0; }
.card-header.doing { background-color: #eff6ff !important; color: #1e40af; border-bottom: 2px solid #bfdbfe; }
.card-header.done { background-color: #e0f2fe !important; color: #0c4a6e; border-bottom: 2px solid #bae6fd; }

/* Destaque de prioridade no card (borda esquerda + badge) */
.kanban-card { position: relative; }
.kanban-card.priority-alta { border-left: 4px solid #ef4444; }
.kanban-card.priority-media { border-left: 4px solid #f59e0b; }
.kanban-card.priority-baixa { border-left: 4px solid #10b981; }

.badge-priority-alta { background: #fee2e2; color: #b91c1c; }
.badge-priority-media { background: #fef3c7; color: #b45309; }
.badge-priority-baixa { background: #dcfce7; color: #065f46; }

/* Descrição resumida no card */
.card-desc{ font-size: .875rem; color: var(--secondary-color); margin-top: .25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* Contadores por coluna */
.count-badge{ font-weight: 600; }
.count-badge-tarefas{ background: #dcfce7; color: #065f46; }
.count-badge-doing{ background: #dbeafe; color: #1e40af; }
.count-badge-done{ background: #e0f2fe; color: #0c4a6e; }

</style>

<script>
(function(){
  const columns = document.querySelectorAll('.kanban-column');
    const addBtn = document.getElementById('addCardBtn');
    const resetBtn = document.getElementById('resetKanbanBtn');
    const modalEl = document.getElementById('modalNovoCard');
    const formEl = document.getElementById('formNovoCard');
    const inputTitulo = document.getElementById('novoTitulo');
    const selectPrioridade = document.getElementById('novoPrioridade');
    const inputPrevista = document.getElementById('novoPrevista');
    const inputDesc = document.getElementById('novoDescricao');
        let editingCard = null;
        const deleteBtnEdit = document.getElementById('deleteFromEditBtn');

        // Mapeamento dos badges de contagem
        const countEls = {
            tarefas: document.getElementById('count-tarefas'),
            doing: document.getElementById('count-doing'),
            done: document.getElementById('count-done')
        };

        // Cliente API
        const API_URL = 'ajax/kanban.php';
        async function api(action, data){
            const opts = { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } };
            const body = new URLSearchParams({ action, ...data }).toString();
            opts.body = body;
            const res = await fetch(API_URL, opts);
            const json = await res.json();
            if(!json.ok) throw new Error(json.error || 'Falha na API');
            return json.data || {};
        }

  // Utilidades
  const priWeight = (p) => ({ alta: 3, media: 2, baixa: 1 }[p] || 0);
  const parseDate = (s) => { const t = Date.parse(s); return isNaN(t) ? Date.now() : t; };
  const todayISO = () => new Date().toISOString().slice(0,10);
  const formatBR = (s) => { try { const d = new Date(s); return d.toLocaleDateString('pt-BR'); } catch { return s; } };

  // Drag & Drop
  let dragSrc = null;
  function handleDragStart(e){ dragSrc = this; e.dataTransfer.effectAllowed = 'move'; }
  function handleDragOver(e){ e.preventDefault(); this.classList.add('drag-over'); e.dataTransfer.dropEffect = 'move'; }
  function handleDragLeave(){ this.classList.remove('drag-over'); }
  function handleDrop(e){
    e.preventDefault();
    this.classList.remove('drag-over');
        if(dragSrc){ 
            this.appendChild(dragSrc); 
            sortColumn(this); 
            updateCounts(); 
            const id = dragSrc.dataset.id ? parseInt(dragSrc.dataset.id,10) : 0;
            const coluna = this.dataset.column;
            if(id > 0){
                api('move', { id, coluna }).catch(()=>{});
            }
            dragSrc = null; 
        }
  }

  function initCards(root){
    root.querySelectorAll('.kanban-card').forEach(c => {
      c.addEventListener('dragstart', handleDragStart);
      const dateSpan = c.querySelector('.card-date');
      if(dateSpan){ dateSpan.textContent = formatBR(dateSpan.dataset.date || dateSpan.textContent); }
            injectActions(c);
    });
  }

  function sortColumn(column){
    const cards = Array.from(column.querySelectorAll('.kanban-card'));
    cards.sort((a,b) => {
      const pa = priWeight((a.dataset.priority||'').toLowerCase());
      const pb = priWeight((b.dataset.priority||'').toLowerCase());
      if(pa !== pb) return pb - pa; // maior prioridade primeiro
      const da = parseDate(a.dataset.date || '');
      const db = parseDate(b.dataset.date || '');
      return da - db; // mais antigo primeiro
    });
    cards.forEach(c => column.appendChild(c));
  }

    function updateCounts(){
        columns.forEach(col => {
            const key = col.dataset.column;
            const count = col.querySelectorAll('.kanban-card').length;
            const el = countEls[key];
            if(el) el.textContent = count;
        });
    }

  columns.forEach(col => {
    col.addEventListener('dragover', handleDragOver);
    col.addEventListener('dragleave', handleDragLeave);
    col.addEventListener('drop', handleDrop);
    sortColumn(col);
  });
    initCards(document);
    updateCounts();

    // Renderizar card a partir de registro
    function renderCard(row){
        const prioridade = (row.prioridade||'media');
        const badgeClass = prioridade==='alta' ? 'badge-priority-alta' : prioridade==='media' ? 'badge-priority-media' : 'badge-priority-baixa';
        const priLabel = prioridade==='alta' ? 'Alta' : prioridade==='media' ? 'Média' : 'Baixa';
        const dataCadastro = row.data_cadastro || todayISO();
        const dataPrevista = row.data_prevista || '';
        const card = document.createElement('div');
        card.className = `kanban-card priority-${prioridade}`;
        card.setAttribute('draggable', 'true');
        card.dataset.priority = prioridade;
        card.dataset.date = dataCadastro;
        card.dataset.prevista = dataPrevista;
        if(row.id) card.dataset.id = row.id;
        card.title = (row.descricao||'');
        card.innerHTML = `
            <div class=\"d-flex justify-content-between align-items-center\">\n        <span class=\"card-title\"></span>\n        <span class=\"badge ${badgeClass}\">${priLabel}\</span>
            </div>
            <div class=\"card-desc\"></div>
            <div class=\"small text-secondary mt-1\">Previsto: <span class=\"card-prevista\"></span></div>
            <div class=\"small text-secondary mt-1 d-flex align-items-center justify-content-between\">\n        <span>Criado: <span class=\"card-date\" data-date=\"${dataCadastro}\"></span></span>\n        <button type=\"button\" class=\"btn btn-sm btn-light view-card-btn\" title=\"Detalhes\"><i class=\"bi bi-eye\"></i></button>\n      </div>`;
        card.querySelector('.card-title').textContent = row.titulo || '';
        card.querySelector('.card-desc').textContent = row.descricao || '';
        const dateSpan = card.querySelector('.card-date');
        dateSpan.textContent = formatBR(dataCadastro);
        const prevSpan = card.querySelector('.card-prevista');
        prevSpan.textContent = dataPrevista ? formatBR(dataPrevista) : '—';
        card.addEventListener('dragstart', handleDragStart);
        injectActions(card);
        return card;
    }

    // Carregar dados do servidor
    (async function loadServer(){
        try{
            const data = await api('list', {});
            const cards = Array.isArray(data.cards) ? data.cards : [];
            // Limpar colunas atuais
            columns.forEach(col => col.innerHTML = '');
            // Distribuir
            cards.forEach(row => {
                const colKey = row.coluna || 'tarefas';
                const target = document.querySelector(`[data-column="${colKey}"]`);
                if(target){
                    const card = renderCard(row);
                    target.appendChild(card);
                }
            });
            // Ordenar e contar
            columns.forEach(sortColumn);
            updateCounts();
        }catch(e){
            // Silencioso no primeiro load
            console.warn('Falha ao carregar Kanban:', e.message);
        }
    })();

    // Abrir modal ao clicar em Novo card
    addBtn?.addEventListener('click', () => {
        const modal = new bootstrap.Modal(modalEl);
        // limpar data prevista
        inputPrevista.value = '';
        formEl.classList.remove('was-validated');
        inputTitulo.value = '';
        selectPrioridade.value = '';
        inputDesc.value = '';
        modal.show();
    });

    // Validar e criar card via modal
    // reset modal texts on hide
    modalEl?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('modalNovoCardLabel').textContent = 'Novo Card';
        const submitBtn = formEl?.querySelector('button[type="submit"]');
        if(submitBtn) submitBtn.textContent = 'Adicionar';
        editingCard = null;
        deleteBtnEdit?.classList.add('d-none');
    });

    formEl?.addEventListener('submit', (e) => {
        e.preventDefault();
        e.stopPropagation();
        formEl.classList.add('was-validated');
        // Validadores básicos
        const title = (inputTitulo.value || '').trim();
        const priSel = selectPrioridade.value;
        const desc = (inputDesc.value || '').trim();
        const dataPrevista = inputPrevista.value || '';

        if(title.length < 3 || !priSel || desc.length < 5){
            return;
        }
        // Mapear prioridade selecionada para classes internas
        const priMap = { '1': 'alta', '2': 'media', '3': 'baixa' };
        const priLabel = { '1': 'Alta', '2': 'Média', '3': 'Baixa' };
        const prioridade = priMap[priSel] || 'media';
        const badgeClass = prioridade==='alta' ? 'badge-priority-alta' : prioridade==='media' ? 'badge-priority-media' : 'badge-priority-baixa';
        if(editingCard){
            // Atualizar card existente
            const titleEl = editingCard.querySelector('.card-title');
            const descEl = editingCard.querySelector('.card-desc');
            const badgeEl = editingCard.querySelector('.badge');
            const prevEl = editingCard.querySelector('.card-prevista');

            // Atualiza dados
            titleEl.textContent = title;
            if(descEl) descEl.textContent = desc; else {
                const newDesc = document.createElement('div');
                newDesc.className = 'card-desc';
                newDesc.textContent = desc;
                editingCard.insertBefore(newDesc, editingCard.querySelector('.small.text-secondary.mt-1'));
            }
            badgeEl.textContent = priLabel[priSel];
            badgeEl.className = `badge ${badgeClass}`;
            editingCard.dataset.priority = prioridade;
            editingCard.dataset.prevista = dataPrevista;
            if(prevEl){ prevEl.textContent = dataPrevista ? formatBR(dataPrevista) : '—'; }

            // Atualiza classes de prioridade
            editingCard.classList.remove('priority-alta','priority-media','priority-baixa');
            editingCard.classList.add(`priority-${prioridade}`);

                        // Persistir
                        const id = editingCard.dataset.id ? parseInt(editingCard.dataset.id,10) : 0;
                        if(id > 0){
                            api('update', { id, titulo: title, descricao: desc, prioridade, data_prevista: dataPrevista }).catch(()=>{});
                        }
                        const parentCol = editingCard.parentElement;
            sortColumn(parentCol);
            editingCard = null;
            bootstrap.Modal.getInstance(modalEl)?.hide();
            return;
        }

        // Criar novo card
        const card = document.createElement('div');
        card.className = `kanban-card priority-${prioridade}`;
        card.setAttribute('draggable', 'true');
        card.dataset.priority = prioridade;
        card.dataset.date = todayISO();
        card.dataset.prevista = dataPrevista;
        card.title = desc; // dica ao passar o mouse
        card.innerHTML = `
            <div class=\"d-flex justify-content-between align-items-center\">\n        <span class=\"card-title\"></span>\n        <span class=\"badge ${badgeClass}\">${priLabel[priSel]}\</span>
            </div>
            <div class=\"card-desc\"></div>
            <div class=\"small text-secondary mt-1\">Previsto: <span class=\"card-prevista\"></span></div>
            <div class=\"small text-secondary mt-1 d-flex align-items-center justify-content-between\">\n              <span>Criado: <span class=\"card-date\" data-date=\"${todayISO()}\"></span></span>\n              <button type=\"button\" class=\"btn btn-sm btn-light view-card-btn\" title=\"Detalhes\"><i class=\"bi bi-eye\"></i></button>\n            </div>`;

        card.querySelector('.card-title').textContent = title;
        card.querySelector('.card-desc').textContent = desc;
        const dateSpan = card.querySelector('.card-date');
        dateSpan.textContent = formatBR(todayISO());
        const prevSpan = card.querySelector('.card-prevista');
        prevSpan.textContent = dataPrevista ? formatBR(dataPrevista) : '—';

        card.addEventListener('dragstart', handleDragStart);
        injectActions(card);
                const tarefas = document.querySelector('[data-column=\"tarefas\"]');
                tarefas.appendChild(card);
                // Persistir criação
                api('create', { titulo: title, descricao: desc, prioridade, coluna: 'tarefas', data_prevista: dataPrevista })
                    .then(d => { if(d.id) { card.dataset.id = d.id; if(d.data_cadastro){ card.dataset.date = d.data_cadastro; dateSpan.textContent = formatBR(d.data_cadastro); } } })
                    .catch(()=>{});
                sortColumn(tarefas);
                updateCounts();

        // Fechar modal após adicionar
        bootstrap.Modal.getInstance(modalEl)?.hide();
    });

  resetBtn?.addEventListener('click', () => { location.reload(); });

    // Injeta ou vincula o botão "olho" ao lado direito da data
    function injectActions(card){
        // Se já existe botão, apenas vincula o evento
        let viewBtn = card.querySelector('.view-card-btn');
        const dateRow = card.querySelector('.small.text-secondary.mt-1');
        if(!viewBtn && dateRow){
            dateRow.classList.add('d-flex','align-items-center','justify-content-between');
            viewBtn = document.createElement('button');
            viewBtn.type = 'button';
            viewBtn.className = 'btn btn-sm btn-light view-card-btn';
            viewBtn.title = 'Detalhes';
            viewBtn.innerHTML = '<i class="bi bi-eye"></i>';
            dateRow.appendChild(viewBtn);
        }
        viewBtn?.addEventListener('click', () => openEdit(card));
    }

    function openEdit(card){
        // Preencher modal de edição diretamente
        inputTitulo.value = card.querySelector('.card-title')?.textContent || '';
        inputDesc.value = card.querySelector('.card-desc')?.textContent || '';
        inputPrevista.value = card.dataset.prevista || '';
        const priKey = (card.dataset.priority||'media');
        const priToSel = { alta: '1', media: '2', baixa: '3' };
        selectPrioridade.value = priToSel[priKey] || '2';
        document.getElementById('modalNovoCardLabel').textContent = 'Editar Card';
        const submitBtn = formEl?.querySelector('button[type="submit"]');
        if(submitBtn) submitBtn.textContent = 'Salvar';
        formEl.classList.remove('was-validated');
        editingCard = card;
        deleteBtnEdit?.classList.remove('d-none');
        new bootstrap.Modal(modalEl).show();
    }

    deleteBtnEdit?.addEventListener('click', () => {
        if(!editingCard) return;
        if(confirm('Deseja excluir este card?')){
            const parent = editingCard.parentElement;
            const id = editingCard.dataset.id ? parseInt(editingCard.dataset.id,10) : 0;
            if(id > 0){ api('delete', { id }).catch(()=>{}); }
            editingCard.remove();
            editingCard = null;
            bootstrap.Modal.getInstance(modalEl)?.hide();
            if(parent) { sortColumn(parent); updateCounts(); }
        }
    });
})();
</script>

