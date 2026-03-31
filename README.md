# 🐙 Oktopus DoS PHP Load Tester 

<img width="543" height="675" alt="{AD6A8E84-109E-4D24-9572-F3144A0AF488}" src="https://github.com/user-attachments/assets/86a615fc-0694-41c4-b1e0-17053ee8d4f7" />
<img width="973" height="937" alt="{99752AA9-8C7C-4BB9-BA17-204C782732DE}" src="https://github.com/user-attachments/assets/1b32adf1-b706-4ae5-95fe-432cff609063" />




**Una herramienta potente y fácil de usar para realizar pruebas de carga (Load Testing) desde PHP.**

---

## ✨ Características Principales

- **Interfaz intuitiva** por consola con menú interactivo
- **Soporte completo de proxies** (carga automática desde lista externa)
- **Gran variedad de User-Agents** realistas (Chrome, Firefox, Edge, Safari, móviles...)
- **Cabeceras HTTP variadas** para simular navegadores reales
- **Simulación de cookies** con ejemplos realistas incluidos
- **Múltiples métodos HTTP** (GET, POST, HEAD)
- **Simulación de navegación** con cookies persistentes
- **Control de tasa** de peticiones para evitar bloqueos
- **Reintentos automáticos** de peticiones fallidas (opcional)
- **Ajuste automático** de parámetros según el rendimiento
- **Exportación de resultados** en **HTML bonito** y fácil de leer
- **Progreso en tiempo real** con barra visual

---

## 🚀 ¿Para qué sirve?

Este script es ideal para:

- Probar la resistencia de tu servidor o aplicación web
- Realizar pruebas de carga antes de un lanzamiento
- Simular tráfico real desde diferentes navegadores y ubicaciones
- Detectar cuellos de botella en tu infraestructura
- Realizar pruebas con proxies para distribuir el tráfico

---

## 📋 Requisitos

- PHP 7.4 o superior
- Extensión **cURL** habilitada (`php_curl`)
- Permisos de escritura en la carpeta (para guardar proxies y resultados)

---

## 📥 Instalación

1. Clona este repositorio o descarga el archivo `octopusv6.php`
2. Colócalo en la carpeta deseada
3. Asegúrate de que la extensión cURL esté activada
4. (Opcional) Ejecuta el script por primera vez para crear `proxies.txt`


🛠️ Uso
Ejecuta el script con:
```bash
php oktopus.php
```
Recomendación:
Empieza con la opción 1 (definir URLs) y luego usa la opción 12 (ajuste automático) antes de lanzar la prueba.

📊 Resultados
Al finalizar cada prueba se genera automáticamente un archivo:
textresultados_load_test_YYYY-MM-DD_HH-MM-SS.html
El archivo HTML incluye:

Resumen claro de resultados
Estadísticas de éxito/fallo
Configuración utilizada
Diseño limpio y responsive


📁 Archivos del proyecto

oktopus.php → Script principal
proxies.txt → Lista de proxies (se crea automáticamente)
resultados_load_test_*.html → Reportes generados


⚠️ Advertencias

El uso de proxies gratuitos puede ocasionar perdida de peticiones por la calidad de los mismos y se recomienda proxies de pago en lo posible.
Usa esta herramienta solo en sitios de los que tengas permiso para hacer pruebas de carga.
Un uso excesivo o malintencionado puede ser considerado ataque DDoS.
Respeta las leyes y términos de servicio de cada sitio.


📌 Próximas mejoras (posibles)

Soporte para autenticación básica / tokens
Modo silencioso / por línea de comandos
Gráficos en los reportes HTML
Soporte para multipart/form-data y subida de archivos


⭐ Contribuciones
¡Las contribuciones son bienvenidas! Si quieres añadir nuevas funcionalidades, mejorar el diseño del HTML o agregar más User-Agents, abre un Pull Request.

Hecho con ❤️  por Aisurf3r. 


