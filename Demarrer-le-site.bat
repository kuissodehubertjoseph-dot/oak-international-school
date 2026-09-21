@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

set "PORT=8125"
set "URL=http://127.0.0.1:%PORT%/index.html"

set "PHP_EXE=php"
where php >nul 2>nul
if errorlevel 1 (
    for /d %%D in ("C:\wamp64\bin\php\php*") do set "PHP_EXE=%%D\php.exe"
)

rem Le serveur tourne-t-il deja sur ce port ?
netstat -ano | findstr ":%PORT% " | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo Le site est deja demarre. Ouverture du navigateur...
    start "" "%URL%"
    goto :end
)

echo Demarrage du serveur du site OAK International School...
start "Serveur OAK International School - NE PAS FERMER CETTE FENETRE" "%PHP_EXE%" -S 127.0.0.1:%PORT%

echo Attente du demarrage du serveur...
:waitloop
timeout /t 1 /nobreak >nul
netstat -ano | findstr ":%PORT% " | findstr "LISTENING" >nul
if errorlevel 1 goto waitloop

echo Serveur pret. Ouverture du site...
start "" "%URL%"

:end
