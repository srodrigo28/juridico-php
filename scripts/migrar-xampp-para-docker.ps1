#
# para odar o script
#

# .\migrar-xampp-para-docker.ps1

# migrar-xampp-para-docker.ps1 (versão robusta v2)
# Execute no PowerShell COMO ADMINISTRADOR.

$ErrorActionPreference = "Stop"

# ===== AJUSTE AQUI =====
$XAMPP = "C:\xampp"
$DB_NAME = "adv"
$MYSQL_ROOT_PASSWORD = "123456"
$DOCKER_CONTAINER = "mysql8"
$DOCKER_VOLUME = "mysql_data"
$DOCKER_PORT = 3306
# =======================

function Step($t) { Write-Host "`n=== $t ===" }

function Remove-ContainerIfExists($name) {
  $exists = (docker ps -a --format "{{.Names}}" 2>$null) -contains $name
  if ($exists) {
    docker rm -f $name 2>$null | Out-Null
    Write-Host "Removido container: $name"
  } else {
    Write-Host "Container não existe: $name (ok)"
  }
}

function Remove-VolumeIfExists($name) {
  $vexists = (docker volume ls --format "{{.Name}}" 2>$null) -contains $name
  if ($vexists) {
    docker volume rm $name 2>$null | Out-Null
    Write-Host "Removido volume: $name"
  } else {
    Write-Host "Volume não existe: $name (ok)"
  }
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = Join-Path $PSScriptRoot "backup_$timestamp"
New-Item -ItemType Directory -Path $backupDir | Out-Null

$dataDir = Join-Path $XAMPP "mysql\data"
$dataCopyDir = Join-Path $backupDir "xampp_data_copy"

Step "0) Info"
Write-Host "Backup dir: $backupDir"
Write-Host "XAMPP data: $dataDir"
Write-Host "Docker: $DOCKER_CONTAINER  volume: $DOCKER_VOLUME  port: $DOCKER_PORT"

Step "1) Parar mysqld do XAMPP (se existir)"
Get-Process -Name "mysqld" -ErrorAction SilentlyContinue | Stop-Process -Force
Write-Host "OK."

Step "2) Backup seguro da pasta data (robocopy)"
if (!(Test-Path $dataDir)) { throw "Pasta data não encontrada: $dataDir" }
New-Item -ItemType Directory -Path $dataCopyDir | Out-Null
robocopy $dataDir $dataCopyDir /E /Z /R:1 /W:1 /XF mysql.sock | Out-Null
Write-Host "OK: data copiada para $dataCopyDir"

Step "3) Limpar Docker antigo (se existir)"
Remove-ContainerIfExists $DOCKER_CONTAINER
Remove-ContainerIfExists "pma"
Remove-VolumeIfExists $DOCKER_VOLUME
Write-Host "OK."

Step "4) Subir MySQL Docker"
docker run --name $DOCKER_CONTAINER `
  -p "${DOCKER_PORT}:3306" `
  -v "${DOCKER_VOLUME}:/var/lib/mysql" `
  -e "MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD" `
  -e "MYSQL_DATABASE=$DB_NAME" `
  -d mysql:8.0 | Out-Null


Write-Host "OK: MySQL pronto."