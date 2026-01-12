# Ajusta política de execução para permitir scripts no CurrentUser e cria o deploy.ps1 com permissões de escrita
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force

$deployPath = 'C:\xampp\htdocs\www\v2\juridico-php\deploy.ps1'

$deployContent = @'
param(
  [string]$SSHHost = '77.37.126.7',
  [string]$SSHUser = 'srodrigo',
  [int]$SSHPort = 22,
  [string]$LocalPath = 'C:\xampp\htdocs\www\v2\juridico-php',
  [string]$RemotePath = '/var/www/adv.precifex.com',
  [string]$MySQLUser = 'srodrigo',
  [string]$MySQLPass = '@dV#sRnAt98!',
  [string]$SQLFile = 'scripts/novo_banco_adv.sql'
)

if (-not (Get-Command scp -ErrorAction SilentlyContinue)) { Write-Error "scp não encontrado no PATH."; exit 1 }
if (-not (Get-Command ssh -ErrorAction SilentlyContinue)) { Write-Error "ssh não encontrado no PATH."; exit 1 }

Write-Host "Criando diretório remoto (se necessário)..."
ssh -p $SSHPort "$SSHUser@$SSHHost" "mkdir -p '$RemotePath/scripts' || true"

Write-Host "Enviando arquivos para $RemotePath ..."
scp -P $SSHPort -r "$LocalPath/" "$SSHUser@$SSHHost:$RemotePath/" 
if ($LASTEXITCODE -ne 0) { Write-Error "Erro no upload com scp."; exit 2 }

# prepara caminho local do SQL (corrige separadores)
$localSqlFull = Join-Path $LocalPath ($SQLFile -replace '/','\')
if (Test-Path $localSqlFull) {
  Write-Host "Enviando arquivo SQL ($SQLFile) ..."
  scp -P $SSHPort "$localSqlFull" "$SSHUser@$SSHHost:$RemotePath/scripts/"
  if ($LASTEXITCODE -ne 0) { Write-Error "Erro no upload do SQL."; exit 3 }
} else {
  Write-Warning "Arquivo SQL local não encontrado em: $localSqlFull"
}

Write-Host "Executando import do SQL no servidor (usuário MySQL: $MySQLUser) ..."
$remoteSqlPath = "$RemotePath/scripts/$(Split-Path $SQLFile -Leaf)"
$remoteCmd = "mysql -u$MySQLUser -p'$MySQLPass' < '$remoteSqlPath'"
ssh -p $SSHPort "$SSHUser@$SSHHost" "$remoteCmd"
if ($LASTEXITCODE -ne 0) { Write-Error "Erro ao executar import remoto do SQL."; exit 4 }

Write-Host "Deploy concluído."
'@

# Grava o arquivo deploy.ps1
Set-Content -Path $deployPath -Value $deployContent -Encoding UTF8 -Force

# Ajusta ACL para permitir modificação e execução ao usuário atual
$me = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$acl = Get-Acl $deployPath
$rule = New-Object System.Security.AccessControl.FileSystemAccessRule($me,'FullControl','Allow')
$acl.SetAccessRule($rule)
Set-Acl -Path $deployPath -AclObject $acl

Write-Host "Arquivo deploy.ps1 criado em $deployPath"
Write-Host "Para executar: powershell -ExecutionPolicy RemoteSigned -File $deployPath"
```// filepath: c:\xampp\htdocs\www\v2\juridico-php\prepare_deploy.ps1
# Ajusta política de execução para permitir scripts no CurrentUser e cria o deploy.ps1 com permissões de escrita
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force

$deployPath = 'C:\xampp\htdocs\www\v2\juridico-php\deploy.ps1'

$deployContent = @'
param(
  [string]$SSHHost = '77.37.126.7',
  [string]$SSHUser = 'srodrigo',
  [int]$SSHPort = 22,
  [string]$LocalPath = 'C:\xampp\htdocs\www\v2\juridico-php',
  [string]$RemotePath = '/var/www/adv.precifex.com',
  [string]$MySQLUser = 'srodrigo',
  [string]$MySQLPass = '@dV#sRnAt98!',
  [string]$SQLFile = 'scripts/novo_banco_adv.sql'
)

if (-not (Get-Command scp -ErrorAction SilentlyContinue)) { Write-Error "scp não encontrado no PATH."; exit 1 }
if (-not (Get-Command ssh -ErrorAction SilentlyContinue)) { Write-Error "ssh não encontrado no PATH."; exit 1 }

Write-Host "Criando diretório remoto (se necessário)..."
ssh -p $SSHPort "$SSHUser@$SSHHost" "mkdir -p '$RemotePath/scripts' || true"

Write-Host "Enviando arquivos para $RemotePath ..."
scp -P $SSHPort -r "$LocalPath/" "$SSHUser@$SSHHost:$RemotePath/" 
if ($LASTEXITCODE -ne 0) { Write-Error "Erro no upload com scp."; exit 2 }

# prepara caminho local do SQL (corrige separadores)
$localSqlFull = Join-Path $LocalPath ($SQLFile -replace '/','\')
if (Test-Path $localSqlFull) {
  Write-Host "Enviando arquivo SQL ($SQLFile) ..."
  scp -P $SSHPort "$localSqlFull" "$SSHUser@$SSHHost:$RemotePath/scripts/"
  if ($LASTEXITCODE -ne 0) { Write-Error "Erro no upload do SQL."; exit 3 }
} else {
  Write-Warning "Arquivo SQL local não encontrado em: $localSqlFull"
}

Write-Host "Executando import do SQL no servidor (usuário MySQL: $MySQLUser) ..."
$remoteSqlPath = "$RemotePath/scripts/$(Split-Path $SQLFile -Leaf)"
$remoteCmd = "mysql -u$MySQLUser -p'$MySQLPass' < '$remoteSqlPath'"
ssh -p $SSHPort "$SSHUser@$SSHHost" "$remoteCmd"
if ($LASTEXITCODE -ne 0) { Write-Error "Erro ao executar import remoto do SQL."; exit 4 }

Write-Host "Deploy concluído."
'@

# Grava o arquivo deploy.ps1
Set-Content -Path $deployPath -Value $deployContent -Encoding UTF8 -Force

# Ajusta ACL para permitir modificação e execução ao usuário atual
$me = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$acl = Get-Acl $deployPath
$rule = New-Object System.Security.AccessControl.FileSystemAccessRule($me,'FullControl','Allow')
$acl.SetAccessRule($rule)
Set-Acl -Path $deployPath -AclObject $acl

Write-Host "Arquivo deploy.ps1 criado em $deployPath"
Write-Host "Para executar: powershell -ExecutionPolicy RemoteSigned -File $deployPath"