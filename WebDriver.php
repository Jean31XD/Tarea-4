<?php 
require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

// 1. Iniciar sesión con Selenium (Chrome)
$driver = RemoteWebDriver::create('http://localhost:4444', DesiredCapabilities::chrome());

// 2. Abrir la página de login
$driver->get('http://localhost/Xampp/Proyecto-CRUD-funcional/index.php');

// 3. Ingresar usuario y contraseña
$driver->findElement(WebDriverBy::name('usuario'))->sendKeys('Pedrito');
$driver->findElement(WebDriverBy::name('password'))->sendKeys('123');

// 4. Presionar el botón de iniciar sesión
$driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

// 5. Esperar a que cargue la página después del login
sleep(3);

// 6. Crear carpeta si no existe
if (!file_exists('resultados')) {
    mkdir('resultados', 0777, true);
}

// 7. Captura de pantalla después del login
$driver->takeScreenshot('resultados/login_captura.png');
$html = $driver->getPageSource();
file_put_contents('resultados/login_html.html', $html);

// 8. Análisis del HTML para generar un reporte
$reporte = '';
if (strpos($html, 'Bienvenido') !== false || strpos($html, 'dashboard') !== false || strpos($html, 'Cerrar sesión') !== false) {
    $reporte = "✅ Inicio de sesión exitoso para el usuario Pedrito.";
} elseif (strpos($html, 'Usuario o contraseña incorrectos') !== false) {
    $reporte = "❌ Error: Credenciales incorrectas.";
} else {
    $reporte = "⚠️ Resultado incierto. Revisar manualmente la captura.";
}

// 9. Acciones luego del login
try {
    // Esperar a que esté el botón Asignar y hacer clic
    $asignarBtn = $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Asignar')]"));
    $asignarBtn->click();
    sleep(1); // Esperar animaciones o recarga

    // Seleccionar opción "Verificación de pedido" del menú desplegable
    $select = $driver->findElement(WebDriverBy::name("estado")); // Ajustar si el name es distinto
    $options = $select->findElements(WebDriverBy::tagName("option"));
    foreach ($options as $option) {
        if (trim($option->getText()) === "Verificación de pedido") {
            $option->click();
            break;
        }
    }
    sleep(1);

    // Hacer clic dos veces en botón "Retener"
    $retenerBtn = $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Retener')]"));
    $retenerBtn->click();
    sleep(1);
    $retenerBtn->click();
    sleep(1);

    $reporte .= "\n✅ Acciones posteriores al login realizadas correctamente.";
} catch (Exception $e) {
    $reporte .= "\n❌ Error en las acciones posteriores al login: " . $e->getMessage();
}

// 10. Guardar reporte
file_put_contents('resultados/reporte.txt', $reporte);

// 11. Cerrar navegador
$driver->quit();

echo $reporte . "\n📝 Resultados guardados en la carpeta 'resultados'.\n";
