<?php
/* Change to the correct path if you copy this example! */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar si la solicitud es una opción (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Responder con un estado HTTP 200 (OK)
    http_response_code(200);
    exit; // Salir del script
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true); // Decodificar el JSON en un array asociativo

try {
    // Enter the share name for your USB printer here
    // $connector = null;
    $connector = new WindowsPrintConnector("NictomImpresora");

   if (!$connector) {
        throw new Exception("No se pudo establecer conexión con la impresora.");
   }

    if ($data !== null && isset($data['productos'])) {
        // Crear una instancia de la impresora
        $printer = new Printer($connector);

        // Establecer la zona horaria a Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');

        // Obtener la fecha actual en formato local de Argentina
        $fecha = date('d/m/Y H:i:s');
        
        // Imprimir la fecha en el ticket
        $printer->text("\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("S.B.C\n");
        $printer->text("\n");
        
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha: $fecha\n");
        $printer->text("\n");

        // Imprimir cabecera
        $printer->text("Cant. Producto           Precio\n");
        $printer->text("--------------------------------\n");
        
        foreach ($data['productos'] as $producto) {
            // Acceder a las propiedades individuales de cada producto
            $descripcion = $producto['descripcion'];
            $cantidad = $producto['cantidad'];
            $precioUnitario = $producto['precioUnitario'];
        
            // Truncar el nombre del producto si es demasiado largo
            if (strlen($descripcion) > 18) {
                $descripcion = substr($descripcion, 0, 18) . "...";
            }
        
            // Alinear el texto del precio
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
        
            // Imprimir detalles del producto en el recibo
            $printer->text("$cantidad");
        
            // Ajustar espacio para la alineación del texto de la cantidad
            $espacios = 4 - strlen("$cantidad");
            for ($i = 0; $i < $espacios; $i++) {
                $printer->text(" ");
            }
        
            // Alinear el texto del producto
            $printer->text("$descripcion");
        
            // Ajustar espacio para la alineación del texto del producto
            $espacios = 20 - strlen("$descripcion");
            for ($i = 0; $i < $espacios; $i++) {
                $printer->text(" ");
            }
        
            $precioEspacios = 5 - strlen("$precioUnitario");
            for ($i = 0; $i < $precioEspacios; $i++) {
                $printer->text(" ");
            }
            
            // Imprimir el precio del producto
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("$$precioUnitario\n");
        }
        

        // También puedes imprimir el precio total
        $printer->text("--------------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $precioTotal = number_format($data['precioTotal'],2);
        $printer->text("Total: $$precioTotal\n");
        $printer->text("\n\n");

        // Realizar el corte del papel
        $printer->cut();

        // Cerrar la conexión con la impresora
        $printer->close();
        echo "Ticket Impreso";
    } else {
        echo "No se recibieron datos válidos.";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage() . "\n";
}


