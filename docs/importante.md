## Rotas para criar um admin
```
http://localhost/www/juridico-php/scripts/criar_admin.php
```

## Seeds
```
http://localhost/www/juridico-php/scripts/seed-start.php
```

## Copilot
´´´
https://github.com/settings/billing/budgets
´´´

OK: Admin criado/atualizado.
Login: admin@local.test
Senha: Admin123!
Licença ativa para produto 5776734 (Precifex Jurídico).
Acesse: http://localhost/www/juridico-php/login.php

## Passar permissão para AI
```
param(
  [string]$ProjectPath = "C:\xampp\htdocs\www\juridico-php"
)

function Require-Admin {
  $isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()
  ).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
  if (-not $isAdmin) {
    Write-Host "Reabrindo como Administrador..." -ForegroundColor Yellow
    Start-Process -Verb RunAs -FilePath "powershell.exe" -ArgumentList "-ExecutionPolicy Bypass -File `"$PSCommandPath`" -ProjectPath `"$ProjectPath`""
    exit
  }
}

Require-Admin

Write-Host "Aplicando permissões em: $ProjectPath" -ForegroundColor Cyan

# Remover atributo somente leitura
attrib -R "$ProjectPath" /S /D

# Assumir propriedade (recursivo)
takeown /F "$ProjectPath" /R /D Y | Out-Null

# Conceder permissões NTFS
icacls "$ProjectPath" /grant "$env:USERNAME:(OI)(CI)F" /T | Out-Null            # Usuário atual: Full
icacls "$ProjectPath" /grant "Administrators:(OI)(CI)F" /T | Out-Null          # Administrators: Full
icacls "$ProjectPath" /grant "Users:(OI)(CI)M" /T | Out-Null                   # Grupo Users: Modify

# Garantir herança habilitada
icacls "$ProjectPath" /inheritance:e /T | Out-Null

Write-Host "Permissões aplicadas com sucesso." -ForegroundColor Green
```