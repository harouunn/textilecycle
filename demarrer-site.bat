@echo off
rem =============================================================================
rem  TexTileCycle : démarrage rapide du site en local (double-clic)
rem  1. MySQL de XAMPP (s'il ne tourne pas déjà)
rem  2. Laravel (php artisan serve) sur les ports 8000 a 8003 : pages PHP, apres mise en cache
rem     + la file d attente (analyse de risque des dons, envoyee en arriere-plan)
rem  3. Apache de XAMPP sur le port 8090 : fichiers statiques en parallèle, compressés,
rem     mis en cache, et relais vers Laravel (configuration : serveur\apache.conf)
rem  4. Ollama (IA de vision du module Depot) : le modele gemma3:4b est precharge
rem  Puis ouvre http://localhost:8090 . Fermer les fenêtres réduites arrête le site.
rem =============================================================================
cd /d "%~dp0"

tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul || (
    echo Demarrage de MySQL...
    start "TexTileCycle - MySQL" /min "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
)

rem Mise en cache des routes, des vues et des evenements : chaque page se charge plus vite.
rem La configuration n est PAS mise en cache : le fichier .env reste lu et php artisan test garde sa base de test.
echo Preparation des caches Laravel...
call php artisan config:clear >nul
call php artisan route:cache >nul
call php artisan view:cache >nul
call php artisan event:cache >nul

rem 4 processus PHP : « php artisan serve » ne traite qu'une requete a la fois, Apache les repartit.
echo Demarrage de Laravel (ports 8000 a 8003)...
for %%p in (8000 8001 8002 8003) do start "TexTileCycle - Laravel %%p" /min php artisan serve --port=%%p --no-reload

rem Traite les taches en arriere-plan (analyse de risque des dons) : sans lui, elles restent en attente.
start "TexTileCycle - File d attente" /min php artisan queue:work --tries=2 --sleep=3

echo Demarrage d'Apache (port 8090)...
start "TexTileCycle - Apache" /min "C:\xampp\apache\bin\httpd.exe" -d "%cd%" -f "%cd%\serveur\apache.conf"

echo Demarrage de l'IA Ollama et prechargement du modele gemma3:4b...
tasklist /FI "IMAGENAME eq ollama.exe" | find /I "ollama.exe" >nul || (
    start "TexTileCycle - Ollama" /min "%LOCALAPPDATA%\Programs\Ollama\ollama.exe" serve
)
rem Une requete vide charge le modele sur la carte graphique : la premiere analyse de photo sera rapide.
rem Sa propre fenetre (et non /b) : sinon la fermeture de ce script interrompt le chargement.
start "TexTileCycle - Prechargement IA" /min curl -s -o nul http://127.0.0.1:11434/api/generate -d "{\"model\":\"gemma3:4b\",\"keep_alive\":\"60m\"}"

timeout /t 3 /nobreak >nul
start "" http://localhost:8090
echo Site ouvert sur http://localhost:8090
