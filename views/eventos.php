<!-- Modal Movimentações do Processo -->
<div class="modal fade" id="modalNovaMovimentacao" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Eventos do Processo <span id="movProcNumero"></span></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<form id="formNovaMovimentacao">
					<input type="hidden" name="action" value="cadastrar_evento">
					<input type="hidden" name="processo_id" id="movProcessoId">
					<input type="hidden" name="tribunal" id="movTribunal" value="NACIONAL">
					<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
						<h6 class="mb-0">Eventos do Processo</h6>
						<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNovoEvento"><i class="bi bi-plus"></i> Novo Evento</button>
					</div>
					<div class="table-responsive mb-3">
						<table class="table table-sm align-middle">
							<thead>
								<tr>
									<th>Descrição</th>
									<th>Data Inicial</th>
									<th>Prazo</th>
									<th>Contagem</th>
									<th>Metodologia</th>
									<th>Data Final</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody id="movEventosContainer">
								<tr><td colspan="7" class="text-muted">Carregando eventos...</td></tr>
							</tbody>
						</table>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                
			</div>
		</div>
	</div>
	</div>

<!-- Modal Novo Evento -->
<div class="modal fade" id="modalNovoEvento" tabindex="-1">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Evento</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<form id="formNovoEvento">
					<input type="hidden" name="action" value="cadastrar_evento">
					<input type="hidden" name="processo_id" id="novoEvtProcessoId">
					<input type="hidden" name="tribunal" id="novoEvtTribunal" value="NACIONAL">
					<div class="row mb-3">
						<div class="col-12">
							<label class="form-label">Descrição *</label>
							<input type="text" class="form-control" name="descricao" required>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label">Data Inicial *</label>
							<input type="date" class="form-control" name="data_inicial" value="<?= date('Y-m-d') ?>" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Prazo (dias) *</label>
							<input type="number" class="form-control" name="prazo_dias" min="1" required>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label">Contagem *</label>
							<select class="form-select" name="tipo_contagem" required>
								<option value="uteis">Dias úteis</option>
								<option value="corridos">Dias corridos</option>
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Metodologia *</label>
							<select class="form-select" name="metodologia" required>
								<option value="exclui_inicio">Exclui início</option>
								<option value="inclui_inicio">Inclui início</option>
							</select>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-12">
							<label class="form-label">Data Final</label>
							<input type="date" class="form-control" name="data_final" id="novoEvtDataFinal">
							<div class="form-text">Se não informado, será calculada automaticamente</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" onclick="salvarNovoEvento()"><i class="bi bi-check2"></i> Salvar</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal Editar Evento -->
<div class="modal fade" id="modalEditarEvento" tabindex="-1">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Editar Evento</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<form id="formEditarEvento">
					<input type="hidden" name="action" value="atualizar_evento">
					<input type="hidden" name="evento_id" id="editarEvtId">
					<div class="row mb-3">
						<div class="col-12">
							<label class="form-label">Descrição *</label>
							<input type="text" class="form-control" name="descricao" required>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label">Data Inicial *</label>
							<input type="text" class="form-control" name="data_inicial" placeholder="dd/mm/aaaa" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Prazo (dias) *</label>
							<input type="number" class="form-control" name="prazo_dias" min="1" required>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label">Contagem *</label>
							<select class="form-select" name="tipo_contagem" required>
								<option value="uteis">Dias úteis</option>
								<option value="corridos">Dias corridos</option>
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Metodologia *</label>
							<select class="form-select" name="metodologia" required>
								<option value="exclui_inicio">Exclui início</option>
								<option value="inclui_inicio">Inclui início</option>
							</select>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-12">
							<label class="form-label">Data Final</label>
							<div class="input-group">
								<input type="text" class="form-control" name="data_final" id="editarEvtDataFinal" placeholder="dd/mm/aaaa">
								<button class="btn btn-outline-secondary" type="button" onclick="calcularDataFinalEditarEvento()">
									<i class="bi bi-calculator"></i> Calcular
								</button>
							</div>
							<div class="form-text">Se não informado, será calculada automaticamente</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" onclick="salvarEdicaoEvento()"><i class="bi bi-check2"></i> Salvar</button>
			</div>
		</div>
	</div>
</div>
