@echo off
cd /d "%~dp0"
start "" http://localhost:8125/index.html
php -S localhost:8125
