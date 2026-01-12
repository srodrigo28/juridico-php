"""
Deploy Wizard - Interface Gráfica Principal
Wizard de 3 passos para deploy automatizado
"""
import ttkbootstrap as ttk
from ttkbootstrap.constants import *
from tkinter import filedialog, messagebox
import os
import threading
from validators import Validators
from deploy import DeployManager
from config_manager import ConfigManager


class DeployWizard:
    def __init__(self, root):
        self.root = root
        self.root.title("Deploy Wizard - Juridico PHP")
        
        # Obter dimensões da tela
        screen_width = self.root.winfo_screenwidth()
        screen_height = self.root.winfo_screenheight()
        
        # Configurar tamanho: 700px largura, 800px altura
        self.root.geometry("700x1000")
        
        # Desabilitar redimensionamento
        self.root.resizable(False, False)
        
        # Configurar para fechar conexões ao sair
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)
        
        self.current_step = 0
        self.config_manager = ConfigManager()
        self.deploy_manager = DeployManager()
        self.validators = Validators()
        
        # Flags de controle
        self.backup_files_done = False
        self.backup_db_done = False
        
        # Variáveis para armazenar dados
        self.data = {
            'local_path': r'C:\xampp\htdocs\www\v2\juridico-php',
            'backup_local_path': r'C:\backups\deploy_wizard',
            'host': '77.37.126.7',
            'port': '22',
            'username': 'srodrigo',
            'password': '@dV#sRnAt98!',  # Senha padrão
            'remote_path': '/var/www/adv.precifex.com/',
            'db_name': 'adv',
            'db_user': 'srodrigo',
            'db_pass': '@dV#sRnAt98!',
            'sql_file': 'scripts/criar_new_db.sql'
        }
        
        self.setup_ui()
        self.load_last_config()
        
    def setup_ui(self):
        """Configurar interface principal"""
        # Header
        self.header = ttk.Frame(self.root)
        self.header.pack(fill=X, padx=0, pady=0)
        
        # Frame para título com foguete violet
        title_frame = ttk.Frame(self.header)
        title_frame.pack(pady=20)
        
        # Foguete em violet
        rocket_label = ttk.Label(
            title_frame,
            text="🚀",
            font=("Segoe UI", 24, "bold"),
            foreground="#8B5CF6"  # Violet
        )
        rocket_label.pack(side=LEFT, padx=(0, 10))
        
        # Nome Deploy em violet
        self.title_label = ttk.Label(
            title_frame,
            text="Deploy",
            font=("Segoe UI", 24, "bold"),
            foreground="#8B5CF6"  # Violet
        )
        self.title_label.pack(side=LEFT)
        
        self.subtitle_label = ttk.Label(
            self.header,
            text="3 passos simples: Upload → Verificação → Import SQL",
            font=("Segoe UI", 11)
        )
        self.subtitle_label.pack(pady=(0, 20))
        
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
        
        # Botão para salvar configuração
        self.btn_save_config = ttk.Button(
            self.footer,
            text="💾 Salvar Config",
            command=self.save_current_config,
            bootstyle="info-outline",
            width=15
        )
        self.btn_save_config.pack(side=LEFT, padx=(10, 0))
        
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
        
        # Página 1: Configuração
        self.pages.append(self.create_page_config())
        
        # Página 2: Backup
        self.pages.append(self.create_page_backup())
        
        # Página 3: Upload
        self.pages.append(self.create_page_upload())
        
        # Página 4: Verificação
        self.pages.append(self.create_page_verification())
        
        # Página 5: Import SQL
        self.pages.append(self.create_page_import())
    
    def create_page_config(self):
        """Página 1: Configuração"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        ttk.Label(
            page,
            text="⚙️ Configuração do Deploy",
            font=("Segoe UI", 16, "bold"),
            foreground="#8B5CF6"
        ).pack(pady=(0, 30))
        
        # Container com padding para centralizar
        container = ttk.Frame(page)
        container.pack(fill=BOTH, expand=True, padx=40, pady=10)
        
        # Frame do formulário
        form = ttk.Frame(container)
        form.pack(fill=X)
        
        # Seção 1: Diretórios
        ttk.Label(
            form, 
            text="📂 Diretórios", 
            font=("Segoe UI", 11, "bold"),
            foreground="#8B5CF6"
        ).grid(row=0, column=0, columnspan=2, sticky=W, pady=(0, 10))
        
        # Local Path
        ttk.Label(form, text="Local Path:", font=("Segoe UI", 10)).grid(
            row=1, column=0, sticky=W, pady=10, padx=(10, 15)
        )
        path_frame = ttk.Frame(form)
        path_frame.grid(row=1, column=1, sticky=EW, pady=10)
        
        self.entry_local_path = ttk.Entry(path_frame, font=("Segoe UI", 9))
        self.entry_local_path.pack(side=LEFT, fill=X, expand=True)
        self.entry_local_path.insert(0, self.data['local_path'])
        
        ttk.Button(
            path_frame,
            text="📁",
            command=self.browse_folder,
            bootstyle="info-outline",
            width=4
        ).pack(side=LEFT, padx=(5, 0))
        
        # Backup Path
        ttk.Label(form, text="Backup Path:", font=("Segoe UI", 10)).grid(
            row=2, column=0, sticky=W, pady=10, padx=(10, 15)
        )
        backup_frame = ttk.Frame(form)
        backup_frame.grid(row=2, column=1, sticky=EW, pady=10)
        
        self.entry_backup_path = ttk.Entry(backup_frame, font=("Segoe UI", 9))
        self.entry_backup_path.pack(side=LEFT, fill=X, expand=True)
        self.entry_backup_path.insert(0, self.data['backup_local_path'])
        
        ttk.Button(
            backup_frame,
            text="💾",
            command=self.browse_backup_folder,
            bootstyle="info-outline",
            width=4
        ).pack(side=LEFT, padx=(5, 0))
        
        # Separador
        ttk.Separator(form, orient=HORIZONTAL).grid(
            row=3, column=0, columnspan=2, sticky=EW, pady=20
        )
        
        # Seção 2: Conexão
        ttk.Label(
            form, 
            text="🌐 Servidor", 
            font=("Segoe UI", 11, "bold"),
            foreground="#8B5CF6"
        ).grid(row=4, column=0, columnspan=2, sticky=W, pady=(0, 10))
        
        # Host e Port
        ttk.Label(form, text="Conexão:", font=("Segoe UI", 10)).grid(
            row=5, column=0, sticky=W, pady=10, padx=(10, 15)
        )
        host_port_frame = ttk.Frame(form)
        host_port_frame.grid(row=5, column=1, sticky=EW, pady=10)
        
        self.entry_host = ttk.Entry(host_port_frame, font=("Segoe UI", 9))
        self.entry_host.pack(side=LEFT, fill=X, expand=True)
        self.entry_host.insert(0, self.data['host'])
        
        ttk.Label(host_port_frame, text=":", font=("Segoe UI", 10, "bold")).pack(
            side=LEFT, padx=5
        )
        self.entry_port = ttk.Entry(host_port_frame, width=8, font=("Segoe UI", 9))
        self.entry_port.pack(side=LEFT)
        self.entry_port.insert(0, self.data['port'])
        
        # Username e Password
        ttk.Label(form, text="Credenciais:", font=("Segoe UI", 10)).grid(
            row=6, column=0, sticky=W, pady=10, padx=(10, 15)
        )
        user_pass_frame = ttk.Frame(form)
        user_pass_frame.grid(row=6, column=1, sticky=EW, pady=10)
        
        self.entry_username = ttk.Entry(user_pass_frame, font=("Segoe UI", 9))
        self.entry_username.pack(side=LEFT, fill=X, expand=True)
        self.entry_username.insert(0, self.data['username'])
        
        ttk.Label(user_pass_frame, text="@", font=("Segoe UI", 10, "bold")).pack(
            side=LEFT, padx=5
        )
        
        password_inner_frame = ttk.Frame(user_pass_frame)
        password_inner_frame.pack(side=LEFT, fill=X, expand=True)
        
        self.entry_password = ttk.Entry(password_inner_frame, show="•", font=("Segoe UI", 9))
        self.entry_password.pack(side=LEFT, fill=X, expand=True)
        self.entry_password.insert(0, self.data['password'])
        
        ttk.Button(
            password_inner_frame,
            text="👁",
            command=self.toggle_password_visibility,
            bootstyle="info-outline",
            width=4
        ).pack(side=LEFT, padx=(5, 0))
        
        # Separador
        ttk.Separator(form, orient=HORIZONTAL).grid(
            row=7, column=0, columnspan=2, sticky=EW, pady=20
        )
        
        # Seção 3: Deploy
        ttk.Label(
            form, 
            text="🚀 Deploy", 
            font=("Segoe UI", 11, "bold"),
            foreground="#8B5CF6"
        ).grid(row=8, column=0, columnspan=2, sticky=W, pady=(0, 10))
        
        # Remote Path e Database
        ttk.Label(form, text="Destino:", font=("Segoe UI", 10)).grid(
            row=9, column=0, sticky=W, pady=10, padx=(10, 15)
        )
        remote_db_frame = ttk.Frame(form)
        remote_db_frame.grid(row=9, column=1, sticky=EW, pady=10)
        
        self.entry_remote_path = ttk.Entry(remote_db_frame, font=("Segoe UI", 9))
        self.entry_remote_path.pack(side=LEFT, fill=X, expand=True)
        self.entry_remote_path.insert(0, self.data['remote_path'])
        
        ttk.Label(remote_db_frame, text="DB:", font=("Segoe UI", 10, "bold")).pack(
            side=LEFT, padx=8
        )
        self.entry_db_name = ttk.Entry(remote_db_frame, width=15, font=("Segoe UI", 9))
        self.entry_db_name.pack(side=LEFT)
        self.entry_db_name.insert(0, self.data['db_name'])
        
        # SQL File
        ttk.Label(form, text="SQL File:", font=("Segoe UI", 10)).grid(
            row=10, column=0, sticky=W, pady=10, padx=(10, 15)
        )
        sql_frame = ttk.Frame(form)
        sql_frame.grid(row=10, column=1, sticky=EW, pady=10)
        
        self.entry_sql_file = ttk.Entry(sql_frame, font=("Segoe UI", 9))
        self.entry_sql_file.pack(side=LEFT, fill=X, expand=True)
        self.entry_sql_file.insert(0, self.data['sql_file'])
        
        ttk.Button(
            sql_frame,
            text="📄",
            command=self.browse_sql_file,
            bootstyle="info-outline",
            width=4
        ).pack(side=LEFT, padx=(5, 0))
        
        form.columnconfigure(1, weight=1)
        
        # Status
        self.status_config = ttk.Label(
            page,
            text="✅ Configure os parâmetros e avance para o backup",
            font=("Segoe UI", 10),
            bootstyle="success"
        )
        self.status_config.pack(pady=25)
        
        return page
    
    def create_page_backup(self):
        """Página 2: Backup"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        ttk.Label(
            page,
            text="💾 Backup Automático",
            font=("Segoe UI", 16, "bold"),
            foreground="#FFA500"
        ).pack(pady=(0, 20))
        
        # Descrição
        desc = ttk.Label(
            page,
            text="Antes de fazer o deploy, vamos criar backup de segurança:\n"
                 "• Arquivos PHP atuais do servidor\n"
                 "• Banco de dados atual",
            font=("Segoe UI", 10),
            justify="center"
        )
        desc.pack(pady=10)
        
        # Frame para botões de backup
        buttons_frame = ttk.Frame(page)
        buttons_frame.pack(pady=30)
        
        # Botão: Backup Arquivos
        self.btn_backup_files = ttk.Button(
            buttons_frame,
            text="📦 Backup Arquivos PHP",
            command=self.execute_backup_files,
            bootstyle="warning",
            width=25
        )
        self.btn_backup_files.pack(pady=10)
        
        # Status backup arquivos
        self.status_backup_files = ttk.Label(
            buttons_frame,
            text="⏳ Aguardando...",
            font=("Segoe UI", 10)
        )
        self.status_backup_files.pack(pady=5)
        
        # Botão: Backup Database
        self.btn_backup_db = ttk.Button(
            buttons_frame,
            text="🗄️ Backup Banco de Dados",
            command=self.execute_backup_database,
            bootstyle="warning",
            width=25
        )
        self.btn_backup_db.pack(pady=10)
        
        # Status backup database
        self.status_backup_db = ttk.Label(
            buttons_frame,
            text="⏳ Aguardando...",
            font=("Segoe UI", 10)
        )
        self.status_backup_db.pack(pady=5)
        
        # Separador
        ttk.Separator(page, orient=HORIZONTAL).pack(fill=X, pady=20)
        
        # Log de backup
        ttk.Label(
            page,
            text="📋 Log de Backup:",
            font=("Segoe UI", 11, "bold")
        ).pack(anchor=W, pady=(10, 5))
        
        log_frame = ttk.Frame(page)
        log_frame.pack(fill=BOTH, expand=True, pady=10)
        
        scrollbar = ttk.Scrollbar(log_frame)
        scrollbar.pack(side=RIGHT, fill=Y)
        
        import tkinter as tk
        self.backup_log_text = tk.Text(
            log_frame,
            height=10,
            wrap=WORD,
            yscrollcommand=scrollbar.set,
            font=("Consolas", 9),
            bg="#1a1a1a",
            fg="#00ff00"
        )
        self.backup_log_text.pack(side=LEFT, fill=BOTH, expand=True)
        scrollbar.config(command=self.backup_log_text.yview)
        
        # Progress bar
        self.backup_progress = ttk.Progressbar(
            page,
            mode='indeterminate',
            bootstyle="warning-striped"
        )
        self.backup_progress.pack(fill=X, pady=10)
        
        return page
    
    def create_page_upload(self):
        """Página 3: Upload de Arquivos"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        ttk.Label(
            page,
            text="📤 Upload de Arquivos",
            font=("Segoe UI", 16, "bold"),
            foreground="#8B5CF6"
        ).pack(pady=(0, 20))
        
        # Descrição
        desc = ttk.Label(
            page,
            text="Os arquivos serão enviados para o servidor via PSCP",
            font=("Segoe UI", 10),
            bootstyle="inverse-secondary"
        ).pack(pady=10)
        
        # Informações de configuração
        info_frame = ttk.LabelFrame(page, text="📋 Configuração Atual")
        info_frame.pack(fill=X, padx=20, pady=20)
        
        ttk.Label(
            info_frame,
            text=f"• Local: {self.data['local_path']}\n"
                 f"• Servidor: {self.data['host']}:{self.data['port']}\n"
                 f"• Destino: {self.data['remote_path']}",
            font=("Segoe UI", 9),
            bootstyle="secondary"
        ).pack(anchor=W, padx=15, pady=15)
        
        # Status
        self.status_upload = ttk.Label(
            page,
            text="⏳ Clique em 'Próximo' para iniciar o upload",
            font=("Segoe UI", 11, "bold"),
            bootstyle="info"
        )
        self.status_upload.pack(pady=20)
        
        # Log de upload
        ttk.Label(
            page,
            text="📋 Log de Upload:",
            font=("Segoe UI", 11, "bold")
        ).pack(anchor=W, padx=20, pady=(10, 5))
        
        log_frame = ttk.Frame(page)
        log_frame.pack(fill=BOTH, expand=True, padx=20, pady=10)
        
        scrollbar = ttk.Scrollbar(log_frame)
        scrollbar.pack(side=RIGHT, fill=Y)
        
        import tkinter as tk
        self.upload_log_text = tk.Text(
            log_frame,
            height=15,
            wrap=WORD,
            yscrollcommand=scrollbar.set,
            font=("Consolas", 9),
            bg="#1a1a1a",
            fg="#00ff00"
        )
        self.upload_log_text.pack(side=LEFT, fill=BOTH, expand=True)
        scrollbar.config(command=self.upload_log_text.yview)
        
        # Progress bar
        self.upload_progress = ttk.Progressbar(
            page,
            mode='indeterminate',
            bootstyle="success-striped"
        )
        self.upload_progress.pack(fill=X, padx=20, pady=10)
        
        return page
        
    def create_page_verification(self):
        """Página 4: Verificação de Arquivos"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        ttk.Label(
            page,
            text="🔍 Verificar Arquivos no Servidor",
            font=("Segoe UI", 16, "bold"),
            foreground="#8B5CF6"
        ).pack(pady=(0, 20))
        
        # Lista de arquivos com scrollbar
        text_frame = ttk.Frame(page)
        text_frame.pack(fill=BOTH, expand=True, padx=20)
        
        scrollbar = ttk.Scrollbar(text_frame, bootstyle="secondary-round")
        scrollbar.pack(side=RIGHT, fill=Y)
        
        self.files_text = ttk.Text(
            text_frame,
            height=20,
            width=70,
            font=("Consolas", 9),
            yscrollcommand=scrollbar.set
        )
        self.files_text.pack(fill=BOTH, expand=True)
        scrollbar.config(command=self.files_text.yview)
        
        # Separador
        ttk.Separator(page, orient=HORIZONTAL).pack(fill=X, pady=20)
        
        # Status
        self.status_verification = ttk.Label(
            page,
            text="⏳ Execute o upload primeiro...",
            font=("Segoe UI", 10),
            bootstyle="warning"
        )
        self.status_verification.pack(pady=10)
        
        return page
        
    def create_page_import(self):
        """Página 5: Import SQL"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        ttk.Label(
            page,
            text="🗄️ Importar Banco de Dados",
            font=("Segoe UI", 16, "bold"),
            foreground="#8B5CF6"
        ).pack(pady=(0, 20))
        
        # Descrição
        ttk.Label(
            page,
            text="Importar arquivo SQL para o banco de dados",
            font=("Segoe UI", 10)
        ).pack(pady=10)
        
        # Form
        form_frame = ttk.Frame(page)
        form_frame.pack(fill=X, padx=20, pady=(0, 20))
        
        form = ttk.Frame(form_frame)
        form.pack(fill=X)
        
        # Database Name
        ttk.Label(form, text="Database:", font=("Segoe UI", 10, "bold")).grid(
            row=0, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_db_name = ttk.Entry(form, width=50)
        self.entry_db_name.grid(row=0, column=1, sticky=EW, pady=8)
        self.entry_db_name.insert(0, self.data['db_name'])
        
        # SQL File
        ttk.Label(form, text="SQL File:", font=("Segoe UI", 10, "bold")).grid(
            row=1, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_sql_file = ttk.Entry(form, width=50)
        self.entry_sql_file.grid(row=1, column=1, sticky=EW, pady=8)
        self.entry_sql_file.insert(0, self.data['sql_file'])
        
        form.columnconfigure(1, weight=1)
        
        # Progress
        self.progress = ttk.Progressbar(
            page,
            mode='indeterminate',
            bootstyle="success-striped"
        )
        self.progress.pack(fill=X, padx=20, pady=(0, 20))
        
        # Log com scrollbar
        log_frame = ttk.Frame(page)
        log_frame.pack(fill=BOTH, expand=True, padx=20)
        
        scrollbar = ttk.Scrollbar(log_frame, bootstyle="secondary-round")
        scrollbar.pack(side=RIGHT, fill=Y)
        
        self.log_text = ttk.Text(
            log_frame,
            height=15,
            width=70,
            font=("Consolas", 9),
            yscrollcommand=scrollbar.set
        )
        self.log_text.pack(fill=BOTH, expand=True)
        scrollbar.config(command=self.log_text.yview)
        
        # Separador
        ttk.Separator(page, orient=HORIZONTAL).pack(fill=X, pady=20)
        
        # Status
        self.status_import = ttk.Label(
            page,
            text="⏳ Clique em 'Finalizar' para importar o SQL",
            font=("Segoe UI", 10),
            bootstyle="info"
        )
        self.status_import.pack(pady=10)
        
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
            # Página 1: Configuração - validar e avançar
            if self.validate_config():
                self.show_page(self.current_step + 1)
        elif self.current_step == 1:
            # Página 2: Backup - verificar se backups foram feitos
            if not self.backup_files_done and not self.backup_db_done:
                response = messagebox.askyesno(
                    "Aviso - Backup não realizado",
                    "⚠️ Você não fez nenhum backup!\n\n"
                    "É altamente recomendado fazer backup dos arquivos PHP "
                    "e do banco de dados antes de continuar.\n\n"
                    "Deseja continuar sem backup?",
                    icon='warning'
                )
                if not response:
                    return
            elif not self.backup_files_done:
                response = messagebox.askyesno(
                    "Aviso - Backup Incompleto",
                    "⚠️ Você não fez backup dos arquivos PHP!\n\n"
                    "Deseja continuar sem esse backup?",
                    icon='warning'
                )
                if not response:
                    return
            elif not self.backup_db_done:
                response = messagebox.askyesno(
                    "Aviso - Backup Incompleto",
                    "⚠️ Você não fez backup do banco de dados!\n\n"
                    "Deseja continuar sem esse backup?",
                    icon='warning'
                )
                if not response:
                    return
            
            self.show_page(self.current_step + 1)
        elif self.current_step == 2:
            # Página 3: Upload - validar e executar
            if self.validate_upload():
                self.execute_upload_async()
        elif self.current_step == 3:
            # Página 4: Verificação - avançar para import
            self.show_page(self.current_step + 1)
        elif self.current_step == 4:
            # Página 5: Import - executar import
            self.execute_import_async()
            
    def previous_step(self):
        """Voltar para página anterior"""
        if self.current_step > 0:
            self.show_page(self.current_step - 1)
    
    def validate_config(self):
        """Validar configurações da página 1"""
        local_path = self.entry_local_path.get()
        backup_path = self.entry_backup_path.get()
        host = self.entry_host.get()
        port = self.entry_port.get()
        username = self.entry_username.get()
        password = self.entry_password.get()
        remote_path = self.entry_remote_path.get()
        db_name = self.entry_db_name.get()
        
        if not local_path or not os.path.exists(local_path):
            messagebox.showerror("Erro", "Pasta local não existe!")
            return False
        
        if not backup_path:
            messagebox.showerror("Erro", "Pasta de backup não definida!")
            return False
        
        if not host or not port or not username or not password:
            messagebox.showerror("Erro", "Preencha todos os campos de conexão!")
            return False
        
        if not remote_path or not db_name:
            messagebox.showerror("Erro", "Preencha Remote Path e Database!")
            return False
        
        # Salvar dados
        self.data['local_path'] = local_path
        self.data['backup_local_path'] = backup_path
        self.data['host'] = host
        self.data['port'] = port
        self.data['username'] = username
        self.data['password'] = password
        self.data['remote_path'] = remote_path
        self.data['db_name'] = db_name
        self.data['sql_file'] = self.entry_sql_file.get()
        
        return True
            
    def validate_upload(self):
        """Validar dados da página de upload (já foram validados na config)"""
        # Os dados já foram validados na página de configuração
        # Apenas garantir que estão atualizados
        self.data['local_path'] = self.entry_local_path.get()
        self.data['host'] = self.entry_host.get()
        self.data['port'] = self.entry_port.get()
        self.data['username'] = self.entry_username.get()
        self.data['password'] = self.entry_password.get()
        self.data['remote_path'] = self.entry_remote_path.get()
        
        return True
        
    def execute_upload_async(self):
        """Executar upload em thread separada"""
        self.status_upload.config(text="⏳ Preparando upload...", bootstyle="warning")
        self.upload_progress.start()
        self.btn_next.config(state="disabled")
        self.btn_back.config(state="disabled")
        self.root.update()
        
        def upload_thread():
            success, message = self.deploy_manager.upload_files(self.data)
            
            # Atualizar UI na thread principal
            self.root.after(0, lambda: self.on_upload_complete(success, message))
        
        thread = threading.Thread(target=upload_thread, daemon=True)
        thread.start()
        
    def on_upload_complete(self, success, message):
        """Callback quando upload termina"""
        self.upload_progress.stop()
        self.upload_log_text.delete(1.0, END)
        self.upload_log_text.insert(END, message)
        
        if success:
            self.status_upload.config(
                text="✅ Upload concluído! Arquivos enviados com sucesso",
                bootstyle="success"
            )
            # Avançar automaticamente para verificação
            self.show_page(self.current_step + 1)
            # Executar verificação automaticamente
            self.execute_verification_async()
        else:
            self.status_upload.config(
                text="❌ Erro no upload",
                bootstyle="danger"
            )
            
        self.btn_next.config(state="normal")
        self.btn_back.config(state="normal")
            
    def execute_verification_async(self):
        """Verificar arquivos em thread separada"""
        self.status_verification.config(text="⏳ Conectando e verificando arquivos...", bootstyle="warning")
        self.root.update()
        
        def verify_thread():
            success, files = self.deploy_manager.list_remote_files(self.data)
            
            # Atualizar UI na thread principal
            self.root.after(0, lambda: self.on_verification_complete(success, files))
        
        thread = threading.Thread(target=verify_thread, daemon=True)
        thread.start()
        
    def on_verification_complete(self, success, files):
        """Callback quando verificação termina"""
        self.files_text.delete(1.0, END)
        
        if success:
            self.files_text.insert(END, files)
            self.status_verification.config(
                text="✅ Verificação completa! Arquivos listados acima.",
                bootstyle="success"
            )
        else:
            self.files_text.insert(END, f"❌ Erro na verificação:\n\n{files}")
            self.status_verification.config(text="❌ Erro na verificação", bootstyle="danger")
            
    def execute_import_async(self):
        """Executar import SQL em thread separada"""
        self.status_import.config(text="⏳ Importando SQL...", bootstyle="warning")
        self.progress.start()
        self.btn_next.config(state="disabled")
        self.btn_back.config(state="disabled")
        self.root.update()
        
        # Atualizar dados
        self.data['db_name'] = self.entry_db_name.get()
        self.data['sql_file'] = self.entry_sql_file.get()
        
        def import_thread():
            success, log = self.deploy_manager.import_sql(self.data)
            
            # Salvar log em arquivo
            self.config_manager.save_log(log)
            
            # Atualizar UI na thread principal
            self.root.after(0, lambda: self.on_import_complete(success, log))
        
        thread = threading.Thread(target=import_thread, daemon=True)
        thread.start()
        
    def on_import_complete(self, success, log):
        """Callback quando import termina"""
        self.progress.stop()
        self.log_text.delete(1.0, END)
        self.log_text.insert(END, log)
        
        if success:
            self.status_import.config(
                text="✅ Deploy concluído com sucesso! 🎉",
                bootstyle="success"
            )
            
            # Mensagem de sucesso com credenciais
            success_message = (
                "🎉 Deploy Concluído com Sucesso!\n\n"
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                "📍 Acesse o Sistema:\n"
                "   https://adv.precifex.com/\n\n"
                "📧 Email de Acesso:\n"
                "   rodrigoexer2@gmail.com\n\n"
                "🔑 Senha Padrão:\n"
                "   123123\n\n"
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                "✨ O sistema está pronto para uso!"
            )
            
            messagebox.showinfo(
                "Deploy Finalizado",
                success_message
            )
        else:
            self.status_import.config(text="❌ Erro no import SQL", bootstyle="danger")
            messagebox.showerror("Erro", "Erro ao importar SQL. Verifique o log acima.")
            
        self.btn_next.config(state="normal")
        self.btn_back.config(state="normal")
            
    def browse_folder(self):
        """Selecionar pasta local"""
        folder = filedialog.askdirectory(
            title="Selecione a pasta do projeto",
            initialdir=self.entry_local_path.get()
        )
        if folder:
            self.entry_local_path.delete(0, END)
            self.entry_local_path.insert(0, folder)
    
    def browse_backup_folder(self):
        """Selecionar pasta para backups"""
        folder = filedialog.askdirectory(
            title="Selecione a pasta para backups",
            initialdir=self.entry_backup_path.get()
        )
        if folder:
            self.entry_backup_path.delete(0, END)
            self.entry_backup_path.insert(0, folder)
    
    def browse_sql_file(self):
        """Selecionar arquivo SQL"""
        file = filedialog.askopenfilename(
            title="Selecione o arquivo SQL",
            initialdir=self.entry_local_path.get(),
            filetypes=[("SQL Files", "*.sql"), ("All Files", "*.*")]
        )
        if file:
            # Tornar relativo ao local_path se possível
            local_path = self.entry_local_path.get()
            if file.startswith(local_path):
                file = os.path.relpath(file, local_path)
            self.entry_sql_file.delete(0, END)
            self.entry_sql_file.insert(0, file)
    
    def execute_backup_files(self):
        """Executar backup de arquivos"""
        self.backup_progress.start()
        self.btn_backup_files.config(state="disabled")
        self.status_backup_files.config(text="🔄 Fazendo backup...", bootstyle="warning")
        
        # Criar pasta de backup se não existir
        backup_path = self.entry_backup_path.get()
        os.makedirs(backup_path, exist_ok=True)
        
        def backup_thread():
            self.data['backup_local_path'] = backup_path
            self.data['remote_path'] = self.entry_remote_path.get()
            self.data['host'] = self.entry_host.get()
            self.data['port'] = self.entry_port.get()
            self.data['username'] = self.entry_username.get()
            self.data['password'] = self.entry_password.get()
            success, log = self.deploy_manager.backup_files(self.data)
            self.root.after(0, lambda: self.on_backup_files_complete(success, log))
        
        thread = threading.Thread(target=backup_thread, daemon=True)
        thread.start()

    def on_backup_files_complete(self, success, log):
        """Callback quando backup de arquivos termina"""
        self.backup_progress.stop()
        self.backup_log_text.insert(END, log + "\n\n")
        self.backup_log_text.see(END)
        
        if success:
            self.backup_files_done = True
            self.status_backup_files.config(
                text="✅ Backup concluído!",
                bootstyle="success"
            )
            messagebox.showinfo(
                "Backup Concluído",
                "🎉 Backup dos arquivos PHP foi gerado com segurança!\n\n"
                f"📁 Localização: {self.data['backup_local_path']}\n\n"
                "Agora você pode continuar com o deploy."
            )
        else:
            self.status_backup_files.config(text="❌ Erro no backup", bootstyle="danger")
            messagebox.showerror("Erro", "Falha ao fazer backup dos arquivos.")
        
        self.btn_backup_files.config(state="normal")

    def execute_backup_database(self):
        """Executar backup do banco"""
        self.backup_progress.start()
        self.btn_backup_db.config(state="disabled")
        self.status_backup_db.config(text="🔄 Fazendo backup...", bootstyle="warning")
        
        # Criar pasta de backup se não existir
        backup_path = self.entry_backup_path.get()
        os.makedirs(backup_path, exist_ok=True)
        
        def backup_thread():
            self.data['backup_local_path'] = backup_path
            self.data['db_name'] = self.entry_db_name.get()
            self.data['host'] = self.entry_host.get()
            self.data['port'] = self.entry_port.get()
            self.data['username'] = self.entry_username.get()
            self.data['password'] = self.entry_password.get()
            success, log = self.deploy_manager.backup_database(self.data)
            self.root.after(0, lambda: self.on_backup_database_complete(success, log))
        
        thread = threading.Thread(target=backup_thread, daemon=True)
        thread.start()

    def on_backup_database_complete(self, success, log):
        """Callback quando backup do banco termina"""
        self.backup_progress.stop()
        self.backup_log_text.insert(END, log + "\n\n")
        self.backup_log_text.see(END)
        
        if success:
            self.backup_db_done = True
            self.status_backup_db.config(
                text="✅ Backup concluído!",
                bootstyle="success"
            )
            messagebox.showinfo(
                "Backup Concluído",
                "🎉 Backup do banco de dados foi gerado com segurança!\n\n"
                f"📁 Localização: {self.data['backup_local_path']}\n\n"
                "✨ Agora vamos subir as atualizações!"
            )
        else:
            self.status_backup_db.config(text="❌ Erro no backup", bootstyle="danger")
            messagebox.showerror("Erro", "Falha ao fazer backup do banco.")
        
        self.btn_backup_db.config(state="normal")
            
    def load_last_config(self):
        """Carregar última configuração usada"""
        config = self.config_manager.load_config()
        if config:
            # Atualizar campos
            if 'local_path' in config and config['local_path']:
                self.entry_local_path.delete(0, END)
                self.entry_local_path.insert(0, config['local_path'])
                self.data['local_path'] = config['local_path']
                
            if 'host' in config and config['host']:
                self.entry_host.delete(0, END)
                self.entry_host.insert(0, config['host'])
                self.data['host'] = config['host']
                
            if 'port' in config and config['port']:
                self.entry_port.delete(0, END)
                self.entry_port.insert(0, config['port'])
                self.data['port'] = config['port']
                
            if 'username' in config and config['username']:
                self.entry_username.delete(0, END)
                self.entry_username.insert(0, config['username'])
                self.data['username'] = config['username']
                
            if 'remote_path' in config and config['remote_path']:
                self.entry_remote_path.delete(0, END)
                self.entry_remote_path.insert(0, config['remote_path'])
                self.data['remote_path'] = config['remote_path']
                
            if 'db_name' in config and config['db_name']:
                self.data['db_name'] = config['db_name']
                
            if 'sql_file' in config and config['sql_file']:
                self.data['sql_file'] = config['sql_file']
            
            self.status_upload.config(
                text="✅ Configuração anterior carregada",
                bootstyle="success"
            )
            
    def toggle_password_visibility(self):
        """Alternar visibilidade da senha"""
        if self.entry_password.cget('show') == '•':
            self.entry_password.config(show='')
        else:
            self.entry_password.config(show='•')
    
    def save_current_config(self):
        """Salvar configuração atual"""
        # Coletar dados atuais dos campos
        self.data['local_path'] = self.entry_local_path.get()
        self.data['host'] = self.entry_host.get()
        self.data['port'] = self.entry_port.get()
        self.data['username'] = self.entry_username.get()
        self.data['remote_path'] = self.entry_remote_path.get()
        self.data['db_name'] = self.entry_db_name.get()
        self.data['sql_file'] = self.entry_sql_file.get()
        # Senha não é salva por segurança
        
        success, msg = self.config_manager.save_config(self.data)
        
        if success:
            messagebox.showinfo("Configuração Salva", "Configuração salva com sucesso!")
        else:
            messagebox.showerror("Erro", msg)
    
    def on_closing(self):
        """Executar quando janela for fechada"""
        # Fechar conexões SSH
        self.deploy_manager.close()
        
        # Destruir janela
        self.root.destroy()


def main():
    """Função principal"""
    # Criar janela com tema cyborg (preto com violet)
    root = ttk.Window(themename="cyborg")
    
    # Criar aplicação
    app = DeployWizard(root)
    
    # Iniciar loop
    root.mainloop()


if __name__ == "__main__":
    main()
