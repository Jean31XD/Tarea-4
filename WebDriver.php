<?php
require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

$driver = RemoteWebDriver::create('http://localhost:4444', DesiredCapabilities::chrome());

function tomarCaptura($driver, $nombre)
{
    $path = "resultados/$nombre";
    $driver->takeScreenshot("$path.png");
    file_put_contents("$path.html", $driver->getPageSource());
}

function login($driver)
{
    $driver->get('http://localhost/Tarea-4-1/index.php');
    $driver->findElement(WebDriverBy::name('usuario'))->sendKeys('Pedrito');
    $driver->findElement(WebDriverBy::name('password'))->sendKeys('123');
    tomarCaptura($driver, 'login_formulario');
    $driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
    (new WebDriverWait($driver, 10))->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath("//button[contains(text(), 'Asignar')]"))
    );
    tomarCaptura($driver, 'inicio');
}

function probar_retencion($driver)
{
    $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Asignar')]"))->click();
    sleep(2);
    tomarCaptura($driver, 'click_asignar');

    $btn = $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Retención')]"));
    $btn->click();
    sleep(10);
    tomarCaptura($driver, 'retencion_1');

    $btn = $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Retención')]"));
    $btn->click();
    sleep(10);
    tomarCaptura($driver, 'retencion_2');
}

function probar_despacho_factura($driver)
{
    $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Despachar')]"))->click();



    $espera = new WebDriverWait($driver, 10);
    $input = $espera->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('facturaNumero'))
    );
    echo "Clase del input: " . $input->getAttribute('class') . PHP_EOL;
    echo "Está habilitado: " . ($input->isEnabled() ? "Sí" : "No") . PHP_EOL;
    echo "Está visible: " . ($input->isDisplayed() ? "Sí" : "No") . PHP_EOL;

    $input->click();
    usleep(500000);

    $input->clear();
    $input->sendKeys('F1234567862');

    tomarCaptura($driver, 'factura_ingresada');

    $boton = $espera->until(
        WebDriverExpectedCondition::elementToBeClickable(
            WebDriverBy::xpath("//div[contains(@class,'modal')]//button[contains(text(), 'Enviar')]")
        )
    );
    $boton->click();

    sleep(2);
    tomarCaptura($driver, 'factura_despachada');
    sleep(5);
}
function probar_despacho_se_fue($driver) {
    $wait = new WebDriverWait($driver, 10);

    $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Asignar')]"))->click();
    sleep(5);

    $driver->findElement(WebDriverBy::xpath("//button[contains(text(), 'Despachar')]"))->click();
    tomarCaptura($driver, 'abrir_modal_despachar');

    $checkbox = $wait->until(
        WebDriverExpectedCondition::elementToBeClickable(
            WebDriverBy::cssSelector("input[type='checkbox']#seFue, input[type='checkbox']#seFueCheckbox")
        )
    );
    $checkbox->click();
    tomarCaptura($driver, 'checkbox_se_fue_activado');

    $codigoInput = $wait->until(
        WebDriverExpectedCondition::elementToBeClickable(
            WebDriverBy::cssSelector("input#codigo_se_fue, input#codigoSeFue")
        )
    );
    $codigoInput->clear();
    $codigoInput->sendKeys('12345');
    tomarCaptura($driver, 'codigo_ingresado');

$wait->until(
    WebDriverExpectedCondition::elementToBeClickable(
        WebDriverBy::xpath("//button[contains(text(), 'Enviar')]")
    )
)->click();

try {
    $driver->switchTo()->alert()->accept();
    tomarCaptura($driver, 'confirm_alert_aceptado');
} catch (Exception $e) {
    echo "⚠️ No apareció el confirm(): " . $e->getMessage();
    tomarCaptura($driver, 'confirm_alert_fallo');
}


    sleep(2);
    tomarCaptura($driver, 'se_fue_final');
}

if (!file_exists('resultados')) {
    mkdir('resultados', 0777, true);
}

login($driver);
probar_retencion($driver);
probar_despacho_factura($driver);
probar_despacho_se_fue($driver);


$driver->quit();

$reporte = "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>Reporte de Pruebas</title><style>
    body{font-family:sans-serif;padding:20px;background:#f9f9f9;}
    h1{color:#b71c1c;}
    .card{background:white;padding:15px;margin-bottom:20px;border-radius:10px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
    img{max-width:100%;border:1px solid #ccc;margin-top:10px;}
    pre{background:#eee;padding:10px;overflow:auto;border-radius:5px;}
</style></head><body><h1>📋 Reporte Automatizado - Sistema de Tickets</h1>";

foreach (scandir('resultados') as $archivo) {
    if (str_ends_with($archivo, '.png')) {
        $nombre = pathinfo($archivo, PATHINFO_FILENAME);
        $html = htmlspecialchars(file_get_contents("resultados/$nombre.html"));
        $reporte .= "<div class='card'><h2>📌 $nombre</h2><img src='$archivo' alt='$nombre'><details><summary>Ver HTML</summary><pre>$html</pre></details></div>";
    }
}

$reporte .= "</body></html>";
file_put_contents("resultados/reporte.html", $reporte);

echo "✅ Pruebas ejecutadas y reporte generado en 'resultados/reporte.html'\n";