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
                <div class="card-header bg-light fw-bold tarefas">Tarefas</div>
                <div class="card-body kanban-column" data-column="tarefas">
                    <div class="kanban-card priority-alta" draggable="true" data-priority="alta" data-date="2026-01-11">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="card-title">Definir escopo inicial</span>
                            <span class="badge badge-priority-alta">Alta</span>
                        </div>
                        <div class="small text-secondary mt-1">Criado: <span class="card-date" data-date="2026-01-11">11/01/2026</span></div>
                    </div>
                    <div class="kanban-card priority-media" draggable="true" data-priority="media" data-date="2026-01-10">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="card-title">Criar layout base</span>
                            <span class="badge badge-priority-media">Média</span>
                        </div>
                        <div class="small text-secondary mt-1">Criado: <span class="card-date" data-date="2026-01-10">10/01/2026</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna: Em Progresso -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light fw-bold doing">Em Progresso</div>
                <div class="card-body kanban-column" data-column="doing">
                    <div class="kanban-card priority-media" draggable="true" data-priority="media" data-date="2026-01-11">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="card-title">Implementar arrastar e soltar</span>
                            <span class="badge badge-priority-media">Média</span>
                        </div>
                        <div class="small text-secondary mt-1">Criado: <span class="card-date" data-date="2026-01-11">11/01/2026</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna: Concluído -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light fw-bold done">Concluído</div>
                <div class="card-body kanban-column" data-column="done">
                    <div class="kanban-card priority-baixa" draggable="true" data-priority="baixa" data-date="2026-01-09">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="card-title">Configurar páginas e menu</span>
                            <span class="badge badge-priority-baixa">Baixa</span>
                        </div>
                        <div class="small text-secondary mt-1">Criado: <span class="card-date" data-date="2026-01-09">09/01/2026</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos mínimos para Kanban */
.kanban-column { min-height: 240px; display: flex; flex-direction: column; gap: .5rem; }
.kanban-card { background: #fff; border: 1px solid var(--border-color); border-radius: .5rem; padding: .5rem .75rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); cursor: grab; }
.kanban-card:active { cursor: grabbing; }
.kanban-column.drag-over { background: rgba(37,99,235,.06); outline: 2px dashed var(--border-color); }

/* Cores intuitivas por coluna (versão inicial aprovada) */
.card-header.tarefas { background-color: #ecfdf5 !important; color: #065f46; border-bottom: 2px solid #a7f3d0; }
.card-header.doing { background-color: #eff6ff !important; color: #1e40af; border-bottom: 2px solid #bfdbfe; }
.card-header.done { background-color: #f9fafb !important; color: #334155; border-bottom: 2px solid #e5e7eb; }

/* Destaque de prioridade no card (borda esquerda + badge) */
.kanban-card { position: relative; }
.kanban-card.priority-alta { border-left: 4px solid #ef4444; }
.kanban-card.priority-media { border-left: 4px solid #f59e0b; }
.kanban-card.priority-baixa { border-left: 4px solid #10b981; }

.badge-priority-alta { background: #fee2e2; color: #b91c1c; }
.badge-priority-media { background: #fef3c7; color: #b45309; }
.badge-priority-baixa { background: #dcfce7; color: #065f46; }
</style>

<script>
(function(){
  const columns = document.querySelectorAll('.kanban-column');
  const addBtn = document.getElementById('addCardBtn');
  const resetBtn = document.getElementById('resetKanbanBtn');

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
    if(dragSrc){ this.appendChild(dragSrc); sortColumn(this); dragSrc = null; }
  }

  function initCards(root){
    root.querySelectorAll('.kanban-card').forEach(c => {
      c.addEventListener('dragstart', handleDragStart);
      const dateSpan = c.querySelector('.card-date');
      if(dateSpan){ dateSpan.textContent = formatBR(dateSpan.dataset.date || dateSpan.textContent); }
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

  columns.forEach(col => {
    col.addEventListener('dragover', handleDragOver);
    col.addEventListener('dragleave', handleDragLeave);
    col.addEventListener('drop', handleDrop);
    sortColumn(col);
  });
  initCards(document);

  addBtn?.addEventListener('click', () => {
    const title = prompt('Título do card:');
    if(!title) return;
    let prioridade = prompt('Prioridade (Alta/Média/Baixa):', 'Média') || 'Média';
    prioridade = prioridade.trim().toLowerCase().replace('é','e');
    if(!['alta','media','baixa'].includes(prioridade)) prioridade = 'media';
    const data = prompt('Data de cadastro (AAAA-MM-DD):', todayISO()) || todayISO();

    const card = document.createElement('div');
    card.className = `kanban-card priority-${prioridade}`;
    card.setAttribute('draggable', 'true');
    card.dataset.priority = prioridade;
    card.dataset.date = data;
    card.innerHTML = `
      <div class=\"d-flex justify-content-between align-items-center\">\n        <span class=\"card-title\"></span>\n        <span class=\"badge ${
          prioridade==='alta'?'badge-priority-alta':
          prioridade==='media'?'badge-priority-media':'badge-priority-baixa'
        }\">${prioridade==='alta'?'Alta':prioridade==='media'?'Média':'Baixa'}\</span>
      </div>
      <div class=\"small text-secondary mt-1\">Criado: <span class=\"card-date\" data-date=\"${data}\"></span></div>`;

    card.querySelector('.card-title').textContent = title;
    const dateSpan = card.querySelector('.card-date');
    dateSpan.textContent = formatBR(data);

    card.addEventListener('dragstart', handleDragStart);
    const tarefas = document.querySelector('[data-column=\"tarefas\"]');
    tarefas.appendChild(card);
    sortColumn(tarefas);
  });

  resetBtn?.addEventListener('click', () => { location.reload(); });
})();
</script>

