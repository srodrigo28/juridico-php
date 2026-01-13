// Funções globais do sistema

// Formatar moeda brasileira
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// Formatar data para padrão brasileiro
function formatarData(data) {
    if (!data) return '';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

// Validar CPF
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return false;
    }
    
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let resto = 11 - (soma % 11);
    let digitoVerificador1 = resto === 10 || resto === 11 ? 0 : resto;
    
    if (digitoVerificador1 !== parseInt(cpf.charAt(9))) {
        return false;
    }
    
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i)) * (11 - i);
    }
    resto = 11 - (soma % 11);
    let digitoVerificador2 = resto === 10 || resto === 11 ? 0 : resto;
    
    return digitoVerificador2 === parseInt(cpf.charAt(10));
}

// Validar CNPJ
function validarCNPJ(cnpj) {
    cnpj = cnpj.replace(/[^\d]/g, '');
    
    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
        return false;
    }
    
    return true; // Validação simplificada
}

// Validar data no formato dd/mm/aaaa
function validarData(data) {
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(data)) {
        return false;
    }
    
    const [dia, mes, ano] = data.split('/').map(Number);
    const dataObj = new Date(ano, mes - 1, dia);
    
    return dataObj.getDate() === dia &&
           dataObj.getMonth() === mes - 1 &&
           dataObj.getFullYear() === ano;
}

// Aplicar máscara de telefone
function mascaraTelefone(valor) {
    valor = valor.replace(/\D/g, '');
    
    if (valor.length <= 10) {
        valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    }
    
    return valor;
}

// Aplicar máscara de CEP
function mascaraCEP(valor) {
    valor = valor.replace(/\D/g, '');
    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    return valor;
}

// Buscar CEP via API ViaCEP
async function buscarCEP(cep) {
    cep = cep.replace(/\D/g, '');
    
    if (cep.length !== 8) {
        return null;
    }
    
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (data.erro) {
            return null;
        }
        
        return data;
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
        return null;
    }
}

// Mostrar mensagem de sucesso
function mostrarSucesso(mensagem) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Mostrar mensagem de erro
function mostrarErro(mensagem) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Confirmar ação
function confirmar(mensagem) {
    return confirm(mensagem);
}

// Debounce para evitar múltiplas requisições
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Inicialização quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar máscaras automaticamente
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = mascaraTelefone(e.target.value);
        });
    });
    
    // Tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Drawer Mobile
    const drawer = document.getElementById('mobileDrawer');
    const backdrop = document.getElementById('drawerBackdrop');
    const toggleBtn = document.querySelector('.drawer-toggle');
    const closeBtn = document.querySelector('.drawer-close');

    function lockScroll(lock) {
        if (lock) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    function openDrawer() {
        if (!drawer || !backdrop) return;
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        backdrop.hidden = false;
        // force reflow to apply transition
        void backdrop.offsetWidth;
        backdrop.classList.add('show');
        toggleBtn?.setAttribute('aria-expanded', 'true');
        lockScroll(true);
        // Focus management
        closeBtn?.focus();
    }

    function closeDrawer() {
        if (!drawer || !backdrop) return;
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        backdrop.classList.remove('show');
        toggleBtn?.setAttribute('aria-expanded', 'false');
        setTimeout(() => { backdrop.hidden = true; lockScroll(false); }, 350);
        toggleBtn?.focus();
    }

    toggleBtn?.addEventListener('click', openDrawer);
    closeBtn?.addEventListener('click', closeDrawer);
    backdrop?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer?.classList.contains('open')) {
            closeDrawer();
        }
    });
});

// Exportar funções para uso global
window.formatarMoeda = formatarMoeda;
window.formatarData = formatarData;
window.validarCPF = validarCPF;
window.validarCNPJ = validarCNPJ;
window.validarData = validarData;
window.mascaraTelefone = mascaraTelefone;
window.mascaraCEP = mascaraCEP;
window.buscarCEP = buscarCEP;
window.mostrarSucesso = mostrarSucesso;
window.mostrarErro = mostrarErro;
window.confirmar = confirmar;
window.debounce = debounce;
