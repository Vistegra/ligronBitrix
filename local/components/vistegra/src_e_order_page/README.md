# Настройка постоянной сессии с сервером

# Установи Chocolatey если нет
# Запусти PowerShell от имени администратора и установи, если не установлен
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Установите mkcert
choco install mkcert -y

#cd \local\components\vistegra\src_e_order_page/

#Создай сертификаты
mkcert -key-file localhost-key.pem -cert-file localhost.pem localhost 127.0.0.1 ::1

# Выполните в PowerShell как АДМИНИСТРАТОР
mkcert -install

Скопируйте созданные файлы в корень Vite проекта из c:\Users\{user}\:

local.ligron.ru.pem
local.ligron.ru-key.pem

# добавьте строки в
notepad $env:windir\System32\drivers\etc\hosts

127.0.0.1 local.ligron.ru
::1 local.ligron.ru
