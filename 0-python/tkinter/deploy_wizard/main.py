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
        self.root.geometry("900x700")
        
        # Configurar para fechar conexões ao sair
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)
        
        self.current_step = 0
        self.config_manager = ConfigManager()
        self.deploy_manager = DeployManager()
        self.validators = Validators()
        
        # Variáveis para armazenar dados
        self.data = {
            'local_path': r'C:\xampp\htdocs\www\v2\juridico-php',
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
            font=("Segoe UI", 24, "bold"),
            bootstyle="inverse-dark"
        )
        self.title_label.pack(pady=20)
        
        self.subtitle_label = ttk.Label(
            self.header,
            text="3 passos simples: Upload → Verificação → Import SQL",
            font=("Segoe UI", 11),
            bootstyle="inverse-dark"
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
        title_frame = ttk.Frame(page)
        title_frame.pack(pady=(0, 20))
        
        ttk.Label(
            title_frame,
            text="📤 Passo 1: Upload de Arquivos",
            font=("Segoe UI", 18, "bold"),
            bootstyle="primary"
        ).pack()
        
        ttk.Label(
            title_frame,
            text="Configure os dados de conexão e path dos arquivos",
            font=("Segoe UI", 10),
            bootstyle="secondary"
        ).pack()
        
        # Form em um frame com scroll se necessário
        form_frame = ttk.Frame(page)
        form_frame.pack(fill=BOTH, expand=True, padx=20)
        
        form = ttk.Frame(form_frame)
        form.pack(fill=BOTH, expand=True)
        
        # Path Local
        ttk.Label(form, text="Path Local:", font=("Segoe UI", 10, "bold")).grid(
            row=0, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        path_frame = ttk.Frame(form)
        path_frame.grid(row=0, column=1, sticky=EW, pady=8)
        
        self.entry_local_path = ttk.Entry(path_frame, width=50)
        self.entry_local_path.pack(side=LEFT, fill=X, expand=True)
        self.entry_local_path.insert(0, self.data['local_path'])
        
        ttk.Button(
            path_frame,
            text="Browse...",
            command=self.browse_folder,
            bootstyle="info-outline",
            width=12
        ).pack(side=LEFT, padx=(5, 0))
        
        # Host
        ttk.Label(form, text="Host:", font=("Segoe UI", 10, "bold")).grid(
            row=1, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_host = ttk.Entry(form, width=50)
        self.entry_host.grid(row=1, column=1, sticky=EW, pady=8)
        self.entry_host.insert(0, self.data['host'])
        
        # Port
        ttk.Label(form, text="Port:", font=("Segoe UI", 10, "bold")).grid(
            row=2, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_port = ttk.Entry(form, width=50)
        self.entry_port.grid(row=2, column=1, sticky=EW, pady=8)
        self.entry_port.insert(0, self.data['port'])
        
        # Username
        ttk.Label(form, text="Username:", font=("Segoe UI", 10, "bold")).grid(
            row=3, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_username = ttk.Entry(form, width=50)
        self.entry_username.grid(row=3, column=1, sticky=EW, pady=8)
        self.entry_username.insert(0, self.data['username'])
        
        # Password
        ttk.Label(form, text="Password:", font=("Segoe UI", 10, "bold")).grid(
            row=4, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_password = ttk.Entry(form, width=50, show="•")
        self.entry_password.grid(row=4, column=1, sticky=EW, pady=8)
        
        # Remote Path
        ttk.Label(form, text="Remote Path:", font=("Segoe UI", 10, "bold")).grid(
            row=5, column=0, sticky=W, pady=8, padx=(0, 10)
        )
        self.entry_remote_path = ttk.Entry(form, width=50)
        self.entry_remote_path.grid(row=5, column=1, sticky=EW, pady=8)
        self.entry_remote_path.insert(0, self.data['remote_path'])
        
        form.columnconfigure(1, weight=1)
        
        # Separador
        ttk.Separator(page, orient=HORIZONTAL).pack(fill=X, pady=20)
        
        # Status
        self.status_upload = ttk.Label(
            page,
            text="⏳ Preencha os campos e clique em 'Próximo' para iniciar o upload",
            font=("Segoe UI", 10),
            bootstyle="info"
        )
        self.status_upload.pack(pady=10)
        
        return page
        
    def create_page_verification(self):
        """Página 2: Verificação de Arquivos"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        title_frame = ttk.Frame(page)
        title_frame.pack(pady=(0, 20))
        
        ttk.Label(
            title_frame,
            text="🔍 Passo 2: Verificar Arquivos no Servidor",
            font=("Segoe UI", 18, "bold"),
            bootstyle="primary"
        ).pack()
        
        ttk.Label(
            title_frame,
            text="Listando arquivos enviados para o servidor remoto",
            font=("Segoe UI", 10),
            bootstyle="secondary"
        ).pack()
        
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
        """Página 3: Import SQL"""
        page = ttk.Frame(self.pages_container)
        
        # Título
        title_frame = ttk.Frame(page)
        title_frame.pack(pady=(0, 20))
        
        ttk.Label(
            title_frame,
            text="🗄️ Passo 3: Importar SQL",
            font=("Segoe UI", 18, "bold"),
            bootstyle="primary"
        ).pack()
        
        ttk.Label(
            title_frame,
            text="Importar arquivo SQL para o banco de dados",
            font=("Segoe UI", 10),
            bootstyle="secondary"
        ).pack()
        
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
            # Validar e executar upload
            if self.validate_upload():
                self.execute_upload_async()
        elif self.current_step == 1:
            # Avançar para página de import
            if self.current_step < len(self.pages) - 1:
                self.show_page(self.current_step + 1)
        elif self.current_step == 2:
            # Executar import
            self.execute_import_async()
            
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
        if not self.validators.validate_path(local_path):
            messagebox.showerror("Erro de Validação", f"Path local não existe:\n{local_path}")
            return False
            
        if not self.validators.validate_ip(host):
            messagebox.showerror("Erro de Validação", "Host/IP inválido!")
            return False
            
        if not self.validators.validate_port(port):
            messagebox.showerror("Erro de Validação", "Port inválido! (1-65535)")
            return False
            
        if not self.validators.validate_username(username):
            messagebox.showerror("Erro de Validação", "Username inválido!")
            return False
            
        if not self.validators.validate_password(password):
            messagebox.showerror("Erro de Validação", "Password não pode estar vazio!")
            return False
            
        if not self.validators.validate_remote_path(remote_path):
            messagebox.showerror("Erro de Validação", "Remote path deve começar com /")
            return False
            
        # Salvar dados
        self.data['local_path'] = local_path
        self.data['host'] = host
        self.data['port'] = port
        self.data['username'] = username
        self.data['password'] = password
        self.data['remote_path'] = remote_path
        
        return True
        
    def execute_upload_async(self):
        """Executar upload em thread separada"""
        self.status_upload.config(text="⏳ Preparando upload...", bootstyle="warning")
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
        if success:
            self.status_upload.config(text="✅ Upload concluído com sucesso!", bootstyle="success")
            messagebox.showinfo("Sucesso", "Arquivos enviados com sucesso!")
            
            # Avançar para próxima página e executar verificação
            self.show_page(self.current_step + 1)
            self.execute_verification_async()
        else:
            self.status_upload.config(text=f"❌ Erro no upload", bootstyle="danger")
            messagebox.showerror("Erro no Upload", message)
            
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
            messagebox.showinfo(
                "Sucesso",
                "Deploy finalizado com sucesso!\n\nTodos os arquivos foram enviados e o SQL foi importado."
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
    # Criar janela com tema darkly
    root = ttk.Window(themename="darkly")
    
    # Criar aplicação
    app = DeployWizard(root)
    
    # Iniciar loop
    root.mainloop()


if __name__ == "__main__":
    main()
