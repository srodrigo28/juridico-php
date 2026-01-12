# 🚀 Roteiro: Deploy Wizard com Tkinter

**Projeto:** Aplicação Desktop para Automatizar Deploy  
**Tecnologia:** Python + Tkinter + ttkbootstrap  
**Data:** Janeiro 2026  
**Autor:** Baseado em treinamento-v3.html

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Requisitos e Dependências](#requisitos-e-dependências)
3. [Estrutura de Arquivos](#estrutura-de-arquivos)
4. [Passo 1: Setup do Projeto](#passo-1-setup-do-projeto)
5. [Passo 2: Interface Principal (main.py)](#passo-2-interface-principal-mainpy)
6. [Passo 3: Validadores (validators.py)](#passo-3-validadores-validatorspy)
7. [Passo 4: Lógica de Deploy (deploy.py)](#passo-4-lógica-de-deploy-deploypy)
8. [Passo 5: Gerenciamento de Configurações (config_manager.py)](#passo-5-gerenciamento-de-configurações-config_managerpy)
9. [Passo 6: Telas do Wizard](#passo-6-telas-do-wizard)
10. [Passo 7: Integração e Testes](#passo-7-integração-e-testes)
11. [Passo 8: Empacotamento (Opcional)](#passo-8-empacotamento-opcional)

---

## 🎯 Visão Geral

### Objetivo
Criar um wizard desktop que automatiza o processo de deploy em 3 passos:
1. **Upload de Arquivos** via PSCP
2. **Verificação SSH** via PuTTY/Paramiko
3. **Import SQL** via MySQL remoto

### Funcionalidades Principais
- ✅ Interface wizard com 3 páginas sequenciais
- ✅ Validação em tempo real de campos
- ✅ Feedback visual (loading, progress bar, status)
- ✅ Salvar/carregar configurações anteriores
- ✅ Log detalhado de operações
- ✅ Tratamento de erros amigável

---

## 📦 Requisitos e Dependências

### Python
- **Versão:** Python 3.8+

### Bibliotecas

```txt
ttkbootstrap==1.10.1
paramiko==3.4.0
Pillow==10.1.0
```

### Ferramentas Externas
- **PSCP** (PuTTY Secure Copy) instalado no PATH do Windows
- **MySQL Client** (opcional, usaremos Paramiko para SSH)

### Instalação

```bash
pip install ttkbootstrap paramiko Pillow
```

---

## 📁 Estrutura de Arquivos

```
deploy_wizard/
│
├── main.py                 # Arquivo principal (GUI)
├── validators.py           # Validação de campos
├── deploy.py              # Lógica de upload/SSH/MySQL
├── config_manager.py      # Salvar/carregar configurações
├── requirements.txt       # Dependências
│
├── config/
│   └── last_config.json   # Última configuração usada
│
├── logs/
│   └── deploy_YYYYMMDD_HHMMSS.log  # Logs de cada deploy
│
└── assets/
    └── icon.png           # Ícone da aplicação (opcional)
```

---

## 🔧 Passo 1: Setup do Projeto

### 1.1 Criar Estrutura de Pastas

```bash
mkdir deploy_wizard
cd deploy_wizard
mkdir config logs assets
```

### 1.2 Criar requirements.txt

```txt
ttkbootstrap==1.10.1
paramiko==3.4.0
Pillow==10.1.0
```

### 1.3 Instalar Dependências

```bash
pip install -r requirements.txt
```

---

## 🖥️ Passo 2: Interface Principal (main.py)

### 2.1 Estrutura Básica

```python
import ttkbootstrap as ttk
from ttkbootstrap.constants import *
from tkinter import filedialog, messagebox
import os
from validators import Validators
from deploy import DeployManager
from config_manager import ConfigManager

class DeployWizard:
    def __init__(self, root):
        self.root = root
        self.root.title("Deploy Wizard - Juridico PHP")
        self.root.geometry("800x600")
        
        self.current_step = 0
        self.config_manager = ConfigManager()
        self.deploy_manager = DeployManager()
        self.validators = Validators()
        
        # Variáveis para armazenar dados
        self.data = {
            'local_path': '',
            'host': '77.37.126.7',
            'port': '22',
            'username': 'srodrigo',
            'password': '',
            'remote_path': '/var/www/adv.precifex.com/',
            'db_name': 'adv',
            'sql_file': 'scripts/criar_new_db.sql'
        }
        
        self.setup_ui()
        self.load_last_config()
        
    def setup_ui(self):
        """Configurar interface principal"""
        # Header
        self.header = ttk.Frame(self.root, bootstyle="dark")
        self.header.pack(fill=X, padx=0, pady=0)
        
        self.title_label = ttk.Label(
            self.header,
            text="🚀 Deploy Wizard",
            font=("Segoe UI", 20, "bold"),
            bootstyle="inverse-dark"
        )
        self.title_label.pack(pady=20)
        
        # Container para as páginas
        self.pages_container = ttk.Frame(self.root)
        self.pages_container.pack(fill=BOTH, expand=True, padx=20, pady=10)
        
        # Footer com botões
        self.footer = ttk.Frame(self.root)
        self.footer.pack(fill=X, padx=20, pady=20)
        
        self.btn_back = ttk.Button(
            self.footer,
            text="← Voltar",
            command=self.previous_step,
            bootstyle="secondary",
            width=15
        )
        self.btn_back.pack(side=LEFT)
        
        self.btn_next = ttk.Button(
            self.footer,
            text="Próximo →",
            command=self.next_step,
            bootstyle="success",
            width=15
        )
        self.btn_next.pack(side=RIGHT)
        
        # Criar páginas
        self.create_pages()
        self.show_page(0)
        
    def create_pages(self):
        """Criar todas as páginas do wizard"""
        self.pages = []
        
        # Página 1: Upload
        self.pages.append(self.create_page_upload())
        
        # Página 2: Verificação
        self.pages.append(self.create_page_verification())
        
        # Página 3: Import SQL
        self.pages.append(self.create_page_import())
        
    def create_page_upload(self):
        """Página 1: Configuração de Upload"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        ttk.Label(
            page,
            text="📤 Passo 1: Upload de Arquivos",
            font=("Segoe UI", 16, "bold"),
            bootstyle="primary"
        ).pack(pady=(0, 20))
        
        # Form
        form = ttk.Frame(page)
        form.pack(fill=BOTH, expand=True)
        
        # Path Local
        ttk.Label(form, text="Path Local:", font=("Segoe UI", 10, "bold")).grid(row=0, column=0, sticky=W, pady=5)
        path_frame = ttk.Frame(form)
        path_frame.grid(row=0, column=1, sticky=EW, pady=5)
        
        self.entry_local_path = ttk.Entry(path_frame, width=50)
        self.entry_local_path.pack(side=LEFT, fill=X, expand=True)
        self.entry_local_path.insert(0, r"C:\xampp\htdocs\www\v2\juridico-php")
        
        ttk.Button(
            path_frame,
            text="Browse...",
            command=self.browse_folder,
            bootstyle="info-outline"
        ).pack(side=LEFT, padx=(5, 0))
        
        # Host
        ttk.Label(form, text="Host:", font=("Segoe UI", 10, "bold")).grid(row=1, column=0, sticky=W, pady=5)
        self.entry_host = ttk.Entry(form, width=50)
        self.entry_host.grid(row=1, column=1, sticky=EW, pady=5)
        self.entry_host.insert(0, "77.37.126.7")
        
        # Port
        ttk.Label(form, text="Port:", font=("Segoe UI", 10, "bold")).grid(row=2, column=0, sticky=W, pady=5)
        self.entry_port = ttk.Entry(form, width=50)
        self.entry_port.grid(row=2, column=1, sticky=EW, pady=5)
        self.entry_port.insert(0, "22")
        
        # Username
        ttk.Label(form, text="Username:", font=("Segoe UI", 10, "bold")).grid(row=3, column=0, sticky=W, pady=5)
        self.entry_username = ttk.Entry(form, width=50)
        self.entry_username.grid(row=3, column=1, sticky=EW, pady=5)
        self.entry_username.insert(0, "srodrigo")
        
        # Password
        ttk.Label(form, text="Password:", font=("Segoe UI", 10, "bold")).grid(row=4, column=0, sticky=W, pady=5)
        self.entry_password = ttk.Entry(form, width=50, show="•")
        self.entry_password.grid(row=4, column=1, sticky=EW, pady=5)
        
        # Remote Path
        ttk.Label(form, text="Remote Path:", font=("Segoe UI", 10, "bold")).grid(row=5, column=0, sticky=W, pady=5)
        self.entry_remote_path = ttk.Entry(form, width=50)
        self.entry_remote_path.grid(row=5, column=1, sticky=EW, pady=5)
        self.entry_remote_path.insert(0, "/var/www/adv.precifex.com/")
        
        form.columnconfigure(1, weight=1)
        
        # Status
        self.status_upload = ttk.Label(
            page,
            text="⏳ Aguardando confirmação...",
            font=("Segoe UI", 10),
            bootstyle="info"
        )
        self.status_upload.pack(pady=20)
        
        return page
        
    def create_page_verification(self):
        """Página 2: Verificação de Arquivos"""
        page = ttk.Frame(self.pages_container)
        
        ttk.Label(
            page,
            text="🔍 Passo 2: Verificar Arquivos no Servidor",
            font=("Segoe UI", 16, "bold"),
            bootstyle="primary"
        ).pack(pady=(0, 20))
        
        # Lista de arquivos
        self.files_text = ttk.Text(page, height=20, width=70)
        self.files_text.pack(fill=BOTH, expand=True)
        
        # Status
        self.status_verification = ttk.Label(
            page,
            text="⏳ Execute upload primeiro...",
            font=("Segoe UI", 10),
            bootstyle="warning"
        )
        self.status_verification.pack(pady=20)
        
        return page
        
    def create_page_import(self):
        """Página 3: Import SQL"""
        page = ttk.Frame(self.pages_container)
        
        ttk.Label(
            page,
            text="🗄️ Passo 3: Importar SQL",
            font=("Segoe UI", 16, "bold"),
            bootstyle="primary"
        ).pack(pady=(0, 20))
        
        # Form
        form = ttk.Frame(page)
        form.pack(fill=BOTH, expand=True)
        
        # Database Name
        ttk.Label(form, text="Database:", font=("Segoe UI", 10, "bold")).grid(row=0, column=0, sticky=W, pady=5)
        self.entry_db_name = ttk.Entry(form, width=50)
        self.entry_db_name.grid(row=0, column=1, sticky=EW, pady=5)
        self.entry_db_name.insert(0, "adv")
        
        # SQL File
        ttk.Label(form, text="SQL File:", font=("Segoe UI", 10, "bold")).grid(row=1, column=0, sticky=W, pady=5)
        self.entry_sql_file = ttk.Entry(form, width=50)
        self.entry_sql_file.grid(row=1, column=1, sticky=EW, pady=5)
        self.entry_sql_file.insert(0, "scripts/criar_new_db.sql")
        
        form.columnconfigure(1, weight=1)
        
        # Progress
        self.progress = ttk.Progressbar(
            page,
            mode='indeterminate',
            bootstyle="success-striped"
        )
        self.progress.pack(fill=X, pady=20)
        
        # Log
        self.log_text = ttk.Text(page, height=15, width=70)
        self.log_text.pack(fill=BOTH, expand=True)
        
        # Status
        self.status_import = ttk.Label(
            page,
            text="⏳ Aguardando execução...",
            font=("Segoe UI", 10),
            bootstyle="info"
        )
        self.status_import.pack(pady=20)
        
        return page
        
    def show_page(self, page_index):
        """Mostrar página específica"""
        for page in self.pages:
            page.pack_forget()
            
        if 0 <= page_index < len(self.pages):
            self.pages[page_index].pack(fill=BOTH, expand=True)
            self.current_step = page_index
            
        # Atualizar botões
        self.update_buttons()
        
    def update_buttons(self):
        """Atualizar estado dos botões"""
        # Botão Voltar
        if self.current_step == 0:
            self.btn_back.config(state="disabled")
        else:
            self.btn_back.config(state="normal")
            
        # Botão Próximo
        if self.current_step == len(self.pages) - 1:
            self.btn_next.config(text="Finalizar 🎉")
        else:
            self.btn_next.config(text="Próximo →")
            
    def next_step(self):
        """Avançar para próxima página"""
        if self.current_step == 0:
            # Validar e executar upload
            if self.validate_upload():
                self.execute_upload()
        elif self.current_step == 1:
            # Verificar arquivos
            self.execute_verification()
        elif self.current_step == 2:
            # Executar import
            self.execute_import()
            return
            
        if self.current_step < len(self.pages) - 1:
            self.show_page(self.current_step + 1)
            
    def previous_step(self):
        """Voltar para página anterior"""
        if self.current_step > 0:
            self.show_page(self.current_step - 1)
            
    def validate_upload(self):
        """Validar dados da página 1"""
        local_path = self.entry_local_path.get()
        host = self.entry_host.get()
        port = self.entry_port.get()
        username = self.entry_username.get()
        password = self.entry_password.get()
        remote_path = self.entry_remote_path.get()
        
        # Validações
        if not os.path.exists(local_path):
            messagebox.showerror("Erro", "Path local não existe!")
            return False
            
        if not self.validators.validate_ip(host):
            messagebox.showerror("Erro", "Host inválido!")
            return False
            
        if not port.isdigit() or not (1 <= int(port) <= 65535):
            messagebox.showerror("Erro", "Port inválido!")
            return False
            
        if not username or not password:
            messagebox.showerror("Erro", "Username e Password são obrigatórios!")
            return False
            
        # Salvar dados
        self.data['local_path'] = local_path
        self.data['host'] = host
        self.data['port'] = port
        self.data['username'] = username
        self.data['password'] = password
        self.data['remote_path'] = remote_path
        
        return True
        
    def execute_upload(self):
        """Executar upload via PSCP"""
        self.status_upload.config(text="⏳ Fazendo upload...", bootstyle="warning")
        self.root.update()
        
        success, message = self.deploy_manager.upload_files(self.data)
        
        if success:
            self.status_upload.config(text="✅ Upload concluído!", bootstyle="success")
        else:
            self.status_upload.config(text=f"❌ Erro: {message}", bootstyle="danger")
            messagebox.showerror("Erro no Upload", message)
            
    def execute_verification(self):
        """Verificar arquivos no servidor"""
        self.status_verification.config(text="⏳ Verificando...", bootstyle="warning")
        self.root.update()
        
        success, files = self.deploy_manager.list_remote_files(self.data)
        
        self.files_text.delete(1.0, END)
        if success:
            self.files_text.insert(END, files)
            self.status_verification.config(text="✅ Verificação completa!", bootstyle="success")
        else:
            self.status_verification.config(text="❌ Erro na verificação", bootstyle="danger")
            
    def execute_import(self):
        """Executar import SQL"""
        self.status_import.config(text="⏳ Importando SQL...", bootstyle="warning")
        self.progress.start()
        self.root.update()
        
        self.data['db_name'] = self.entry_db_name.get()
        self.data['sql_file'] = self.entry_sql_file.get()
        
        success, log = self.deploy_manager.import_sql(self.data)
        
        self.progress.stop()
        self.log_text.delete(1.0, END)
        self.log_text.insert(END, log)
        
        if success:
            self.status_import.config(text="✅ Deploy concluído com sucesso! 🎉", bootstyle="success")
            messagebox.showinfo("Sucesso", "Deploy finalizado com sucesso!")
        else:
            self.status_import.config(text="❌ Erro no import", bootstyle="danger")
            messagebox.showerror("Erro", "Erro ao importar SQL. Veja o log.")
            
    def browse_folder(self):
        """Selecionar pasta local"""
        folder = filedialog.askdirectory()
        if folder:
            self.entry_local_path.delete(0, END)
            self.entry_local_path.insert(0, folder)
            
    def load_last_config(self):
        """Carregar última configuração usada"""
        config = self.config_manager.load_config()
        if config:
            self.entry_local_path.delete(0, END)
            self.entry_local_path.insert(0, config.get('local_path', ''))
            # ... carregar outros campos
            
    def save_config(self):
        """Salvar configuração atual"""
        self.config_manager.save_config(self.data)

if __name__ == "__main__":
    root = ttk.Window(themename="darkly")
    app = DeployWizard(root)
    root.mainloop()
```

---

## ✅ Passo 3: Validadores (validators.py)

```python
import re
import os
import socket

class Validators:
    """Classe para validação de campos"""
    
    @staticmethod
    def validate_ip(ip):
        """Validar endereço IP ou hostname"""
        # Tentar validar como IP
        pattern = r'^(\d{1,3}\.){3}\d{1,3}$'
        if re.match(pattern, ip):
            parts = ip.split('.')
            return all(0 <= int(part) <= 255 for part in parts)
        
        # Tentar validar como hostname
        try:
            socket.gethostbyname(ip)
            return True
        except:
            return False
    
    @staticmethod
    def validate_port(port):
        """Validar porta"""
        try:
            port_num = int(port)
            return 1 <= port_num <= 65535
        except:
            return False
    
    @staticmethod
    def validate_path(path):
        """Validar path local"""
        return os.path.exists(path)
    
    @staticmethod
    def validate_remote_path(path):
        """Validar formato de path remoto"""
        # Path Unix deve começar com /
        return path.startswith('/')
    
    @staticmethod
    def validate_not_empty(text):
        """Validar se texto não está vazio"""
        return text and text.strip() != ""
```

---

## 🚀 Passo 4: Lógica de Deploy (deploy.py)

```python
import subprocess
import paramiko
import os
from datetime import datetime

class DeployManager:
    """Classe para gerenciar operações de deploy"""
    
    def __init__(self):
        self.ssh_client = None
        
    def upload_files(self, data):
        """Upload de arquivos via PSCP"""
        try:
            local_path = data['local_path']
            host = data['host']
            port = data['port']
            username = data['username']
            password = data['password']
            remote_path = data['remote_path']
            
            # Construir comando PSCP
            cmd = [
                'pscp',
                '-r',
                '-P', port,
                '-pw', password,
                f"{local_path}\\*",
                f"{username}@{host}:{remote_path}"
            ]
            
            # Executar comando
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=300  # 5 minutos timeout
            )
            
            if result.returncode == 0:
                return True, "Upload concluído com sucesso"
            else:
                return False, result.stderr or "Erro desconhecido no upload"
                
        except subprocess.TimeoutExpired:
            return False, "Timeout: Upload demorou muito tempo"
        except FileNotFoundError:
            return False, "PSCP não encontrado. Instale o PuTTY e adicione ao PATH"
        except Exception as e:
            return False, f"Erro: {str(e)}"
    
    def connect_ssh(self, data):
        """Conectar via SSH usando Paramiko"""
        try:
            if self.ssh_client:
                self.ssh_client.close()
                
            self.ssh_client = paramiko.SSHClient()
            self.ssh_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
            self.ssh_client.connect(
                hostname=data['host'],
                port=int(data['port']),
                username=data['username'],
                password=data['password'],
                timeout=10
            )
            
            return True, "Conectado com sucesso"
            
        except paramiko.AuthenticationException:
            return False, "Erro de autenticação: usuário ou senha incorretos"
        except paramiko.SSHException as e:
            return False, f"Erro SSH: {str(e)}"
        except Exception as e:
            return False, f"Erro de conexão: {str(e)}"
    
    def list_remote_files(self, data):
        """Listar arquivos no servidor remoto"""
        try:
            # Conectar se ainda não conectado
            if not self.ssh_client:
                success, msg = self.connect_ssh(data)
                if not success:
                    return False, msg
            
            remote_path = data['remote_path']
            
            # Executar comando ls
            stdin, stdout, stderr = self.ssh_client.exec_command(f"ls -lah {remote_path}")
            
            output = stdout.read().decode()
            error = stderr.read().decode()
            
            if error:
                return False, error
            
            return True, output
            
        except Exception as e:
            return False, f"Erro ao listar arquivos: {str(e)}"
    
    def import_sql(self, data):
        """Importar arquivo SQL no banco de dados"""
        try:
            # Conectar se ainda não conectado
            if not self.ssh_client:
                success, msg = self.connect_ssh(data)
                if not success:
                    return False, msg
            
            username = data['username']
            password = data['password']
            db_name = data['db_name']
            sql_file = data['sql_file']
            remote_path = data['remote_path']
            
            # Construir caminho completo do SQL
            full_sql_path = f"{remote_path}{sql_file}"
            
            # Verificar se arquivo existe
            check_cmd = f"test -f {full_sql_path} && echo 'EXISTS' || echo 'NOT_FOUND'"
            stdin, stdout, stderr = self.ssh_client.exec_command(check_cmd)
            check_result = stdout.read().decode().strip()
            
            if check_result == 'NOT_FOUND':
                return False, f"Arquivo SQL não encontrado: {full_sql_path}"
            
            # Executar import MySQL
            mysql_cmd = f"mysql -u {username} -p'{password}' {db_name} < {full_sql_path}"
            
            stdin, stdout, stderr = self.ssh_client.exec_command(mysql_cmd)
            
            output = stdout.read().decode()
            error = stderr.read().decode()
            
            # MySQL pode retornar warnings no stderr que não são erros fatais
            log = f"=== STDOUT ===\n{output}\n\n=== STDERR ===\n{error}"
            
            # Verificar se deu erro fatal (contém "ERROR")
            if "ERROR" in error.upper():
                return False, log
            
            return True, log + "\n\n✅ Import concluído com sucesso!"
            
        except Exception as e:
            return False, f"Erro ao importar SQL: {str(e)}"
    
    def close(self):
        """Fechar conexão SSH"""
        if self.ssh_client:
            self.ssh_client.close()
            self.ssh_client = None
```

---

## 💾 Passo 5: Gerenciamento de Configurações (config_manager.py)

```python
import json
import os
from datetime import datetime

class ConfigManager:
    """Gerenciar salvamento e carregamento de configurações"""
    
    def __init__(self, config_file='config/last_config.json'):
        self.config_file = config_file
        self.ensure_config_dir()
    
    def ensure_config_dir(self):
        """Garantir que diretório config existe"""
        config_dir = os.path.dirname(self.config_file)
        if config_dir and not os.path.exists(config_dir):
            os.makedirs(config_dir)
    
    def save_config(self, data):
        """Salvar configuração em arquivo JSON"""
        try:
            # Remover senha por segurança (opcional)
            safe_data = data.copy()
            safe_data['password'] = ''  # Não salvar senha
            safe_data['last_saved'] = datetime.now().isoformat()
            
            with open(self.config_file, 'w', encoding='utf-8') as f:
                json.dump(safe_data, f, indent=4, ensure_ascii=False)
            
            return True, "Configuração salva"
        except Exception as e:
            return False, f"Erro ao salvar: {str(e)}"
    
    def load_config(self):
        """Carregar última configuração"""
        try:
            if not os.path.exists(self.config_file):
                return None
            
            with open(self.config_file, 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            return data
        except Exception as e:
            print(f"Erro ao carregar config: {e}")
            return None
```

---

## 🎨 Passo 6: Telas do Wizard

### 6.1 Melhorias Visuais

Adicionar ao `main.py`:

```python
# Adicionar validação visual em tempo real
def setup_validation_bindings(self):
    """Configurar validação em tempo real"""
    self.entry_local_path.bind('<FocusOut>', lambda e: self.validate_field(
        self.entry_local_path,
        self.validators.validate_path
    ))
    
    self.entry_host.bind('<FocusOut>', lambda e: self.validate_field(
        self.entry_host,
        self.validators.validate_ip
    ))
    
    self.entry_port.bind('<FocusOut>', lambda e: self.validate_field(
        self.entry_port,
        self.validators.validate_port
    ))

def validate_field(self, entry, validator_func):
    """Validar campo e mostrar feedback visual"""
    value = entry.get()
    
    if validator_func(value):
        entry.config(bootstyle="success")
    else:
        entry.config(bootstyle="danger")
```

### 6.2 Progress Bar Realista

Para upload de arquivos grandes, usar callback:

```python
# Em deploy.py, modificar upload_files para mostrar progresso
def upload_files_with_progress(self, data, progress_callback=None):
    """Upload com callback de progresso"""
    # Usar paramiko SFTP ao invés de PSCP para ter controle de progresso
    try:
        sftp = self.ssh_client.open_sftp()
        
        local_path = data['local_path']
        remote_path = data['remote_path']
        
        # Listar todos os arquivos
        files_to_upload = []
        for root, dirs, files in os.walk(local_path):
            for file in files:
                local_file = os.path.join(root, file)
                relative_path = os.path.relpath(local_file, local_path)
                remote_file = os.path.join(remote_path, relative_path).replace('\\', '/')
                files_to_upload.append((local_file, remote_file))
        
        total_files = len(files_to_upload)
        
        for idx, (local_file, remote_file) in enumerate(files_to_upload):
            # Criar diretórios remotos se necessário
            remote_dir = os.path.dirname(remote_file)
            try:
                sftp.stat(remote_dir)
            except IOError:
                self._create_remote_dir(sftp, remote_dir)
            
            # Upload do arquivo
            sftp.put(local_file, remote_file)
            
            # Callback de progresso
            if progress_callback:
                progress = (idx + 1) / total_files * 100
                progress_callback(progress, f"Enviando: {os.path.basename(local_file)}")
        
        sftp.close()
        return True, "Upload concluído"
        
    except Exception as e:
        return False, f"Erro: {str(e)}"

def _create_remote_dir(self, sftp, remote_dir):
    """Criar diretório remoto recursivamente"""
    dirs = remote_dir.split('/')
    current_dir = ''
    
    for dir_name in dirs:
        if not dir_name:
            continue
        current_dir += '/' + dir_name
        try:
            sftp.stat(current_dir)
        except IOError:
            sftp.mkdir(current_dir)
```

---

## 🧪 Passo 7: Integração e Testes

### 7.1 Testar Cada Componente

```python
# test_validators.py
from validators import Validators

v = Validators()

# Testes
assert v.validate_ip("77.37.126.7") == True
assert v.validate_ip("999.999.999.999") == False
assert v.validate_port("22") == True
assert v.validate_port("99999") == False

print("✅ Todos os testes passaram!")
```

### 7.2 Testar Deploy Manager

```python
# test_deploy.py
from deploy import DeployManager

dm = DeployManager()

data = {
    'host': '77.37.126.7',
    'port': '22',
    'username': 'srodrigo',
    'password': 'sua_senha',
    'local_path': r'C:\test',
    'remote_path': '/tmp/test/'
}

# Testar conexão
success, msg = dm.connect_ssh(data)
print(f"Conexão: {success} - {msg}")

# Testar listagem
success, files = dm.list_remote_files(data)
print(f"Listagem: {success}\n{files}")

dm.close()
```

---

## 📦 Passo 8: Empacotamento (Opcional)

### 8.1 Gerar Executável com PyInstaller

```bash
pip install pyinstaller
```

### 8.2 Criar spec file

```python
# deploy_wizard.spec
# -*- mode: python ; coding: utf-8 -*-

block_cipher = None

a = Analysis(
    ['main.py'],
    pathex=[],
    binaries=[],
    datas=[],
    hiddenimports=['paramiko', 'ttkbootstrap'],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyt = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyt,
    a.scripts,
    a.binaries,
    a.zipfiles,
    a.datas,
    [],
    name='DeployWizard',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=False,
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
    icon='assets/icon.ico'  # Opcional
)
```

### 8.3 Gerar Executável

```bash
pyinstaller deploy_wizard.spec
```

O executável estará em: `dist/DeployWizard.exe`

---

## 📝 Checklist de Desenvolvimento

### Fase 1: Setup ✅
- [ ] Criar estrutura de pastas
- [ ] Instalar dependências
- [ ] Criar requirements.txt

### Fase 2: Validadores ✅
- [ ] Implementar validators.py
- [ ] Testar validações

### Fase 3: Deploy Manager ✅
- [ ] Implementar upload PSCP
- [ ] Implementar conexão SSH
- [ ] Implementar listagem de arquivos
- [ ] Implementar import SQL
- [ ] Testar cada função

### Fase 4: Config Manager ✅
- [ ] Implementar save_config
- [ ] Implementar load_config
- [ ] Testar salvamento/carregamento

### Fase 5: Interface ✅
- [ ] Criar janela principal
- [ ] Criar Página 1 (Upload)
- [ ] Criar Página 2 (Verificação)
- [ ] Criar Página 3 (Import)
- [ ] Adicionar navegação entre páginas
- [ ] Adicionar validação visual

### Fase 6: Integração ✅
- [ ] Integrar validadores com UI
- [ ] Integrar deploy manager com botões
- [ ] Testar fluxo completo
- [ ] Adicionar tratamento de erros
- [ ] Adicionar logs

### Fase 7: Polimento ✅
- [ ] Melhorar mensagens de erro
- [ ] Adicionar tooltips
- [ ] Adicionar ícones
- [ ] Testar em diferentes resoluções
- [ ] Criar documentação de uso

### Fase 8: Distribuição (Opcional) ✅
- [ ] Gerar executável
- [ ] Testar executável em máquina limpa
- [ ] Criar instalador (opcional)

---

## 🚀 Como Executar

### Desenvolvimento

```bash
cd deploy_wizard
python main.py
```

### Produção (após compilar)

```bash
dist/DeployWizard.exe
```

---

## 📚 Recursos Adicionais

### Documentação
- [ttkbootstrap Docs](https://ttkbootstrap.readthedocs.io/)
- [Paramiko Docs](http://docs.paramiko.org/)
- [Tkinter Tutorial](https://docs.python.org/3/library/tkinter.html)

### Melhorias Futuras
- [ ] Adicionar suporte a múltiplos perfis de deploy
- [ ] Adicionar scheduler para deploys automáticos
- [ ] Adicionar rollback automático em caso de erro
- [ ] Adicionar notificações desktop
- [ ] Adicionar integração com Git
- [ ] Adicionar backup automático antes do deploy
- [ ] Adicionar suporte a Docker

---

## 🐛 Troubleshooting

### PSCP não encontrado
```bash
# Adicionar PuTTY ao PATH do Windows ou usar caminho completo
"C:\Program Files\PuTTY\pscp.exe"
```

### Erro de permissão SSH
```bash
# Verificar permissões no servidor
chmod 755 /var/www/adv.precifex.com/
```

### Timeout na conexão
```python
# Aumentar timeout no código
self.ssh_client.connect(..., timeout=30)
```

---

## ✅ Conclusão

Este roteiro fornece um guia completo para desenvolver o Deploy Wizard com Tkinter.

**Próximos passos:**
1. Seguir a ordem do roteiro
2. Testar cada componente individualmente
3. Integrar tudo no final
4. Distribuir como executável

**Tempo estimado:** 6-8 horas de desenvolvimento

Bom desenvolvimento! 🚀
