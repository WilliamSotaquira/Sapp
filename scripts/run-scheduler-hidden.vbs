' Lanzador oculto del scheduler de Laravel para SAPP.
'
' Ejecuta "php artisan schedule:run" sin mostrar ninguna ventana de consola.
' El tercer parametro de Run (0) oculta la ventana; el cuarto (False) hace que
' no espere. Lo invoca la Tarea Programada de Windows cada minuto.
'
' Si cambias la ruta de PHP o del proyecto, actualiza las dos constantes.

Option Explicit

Dim phpPath, projectPath, shell, command

phpPath = "C:\xampp\php\php.exe"
projectPath = "e:\sapp"

Set shell = CreateObject("WScript.Shell")
shell.CurrentDirectory = projectPath

command = """" & phpPath & """ artisan schedule:run"

' 0 = ventana oculta, False = no esperar a que termine
shell.Run command, 0, False

Set shell = Nothing
