<?php
// Konfigurationsdaten für die Datenbankverbindung
include_once '/home/adem.ismaili/web/adem.6it.ch/public_html/drive_erp/ASEngine/ASConfig.php';

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

// Datenbankverbindung herstellen
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// Prüfen, ob die Verbindung erfolgreich hergestellt wurde
if ($conn->connect_error) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
}    else {
        echo "Verbindung zur Datenbank erfolgreich hergestellt." . PHP_EOL;
}

// URL zur herunterzuladenden Textdatei
$file_url = 'https://ivz-opendata.ch/opendata/2000-Typengenehmigungen_TG_TARGA/2200-Basisdaten_TG_ab_1995/TG-Automobil.txt';

// Temporäre Datei erstellen
$temp_file = tempnam(sys_get_temp_dir(), 'downloaded_file');

// Datei herunterladen und in die temporäre Datei schreiben
$ch = curl_init($file_url);
$temp_file_handle = fopen($temp_file, 'w');
echo "Datei herunterladen..." . PHP_EOL;


curl_setopt($ch, CURLOPT_FILE, $temp_file_handle);

if (curl_exec($ch) === false) {
    die("Fehler beim Herunterladen der Datei: " . curl_error($ch));
} else {
    echo "Datei erfolgreich heruntergeladen." . PHP_EOL;
}

curl_close($ch);
fclose($temp_file_handle);

// Datei öffnen und Daten zeilenweise in die Datenbanktabelle "tg_data" einlesen
$handle = fopen($temp_file, "r");
if ($handle) {
    // Erste Zeile lesen, um Spaltennamen zu extrahieren
    $header = fgets($handle);
    $header = iconv("ISO-8859-1", "UTF-8", $header); // Ändern Sie die Quell-Zeichencodierung entsprechend
    $header_columns = explode("\t", $header);

    // Spaltenmapping aus der Datenbanktabelle "tg_mapping" abrufen
    $mapping_query = "SELECT source_column, destination_column FROM tg_mapping";
    $mapping_result = $conn->query($mapping_query);

    // Array für das Spaltenmapping erstellen
    $column_mapping = array();

    if ($mapping_result->num_rows > 0) {
        while ($row = $mapping_result->fetch_assoc()) {
            $source_column = $row["source_column"];
            $destination_column = $row["destination_column"];

            // Überprüfen, ob der Spaltenname in der Zeilenkopf vorhanden ist
            if (in_array($source_column, $header_columns)) {
                $column_mapping[$source_column] = $destination_column;
            } else {
                // Spalte nicht gefunden, Meldung ausgeben mit Zeilenumbruch
                echo "Spalte '$source_column' wurde in der Eingabedatei nicht gefunden. Mapping übersprungen" . PHP_EOL;
            }
        }
    }

    // Solange es Zeilen in der Datei gibt
    while (($line = fgets($handle)) !== false) {
        $data = explode("\t", $line);

        // Erstellen Sie ein Array für die Daten
        $insert_data = array();
        foreach ($data as $key => $value) {
            $source_column = trim($header_columns[$key]);
            if (isset($column_mapping[$source_column])) {
                $destination_column = $column_mapping[$source_column];
                // Zeichencodierung konvertieren
                $value = iconv("ISO-8859-1", "UTF-8", $value);
                if ($value !== '' && $value !== '0') {
                    $insert_data[$destination_column] = $conn->real_escape_string($value);
                } else {
                    $insert_data[$destination_column] = 'NULL';
                }
            }
        }

        // Prüfen, ob ein Datensatz mit dem gleichen "tg_codes" bereits existiert
        $tg_codes = $insert_data['tg_codes'];
        $existing_check_query = "SELECT tg_codes FROM tg_data WHERE tg_codes = '$tg_codes'";
        $existing_check_result = $conn->query($existing_check_query);

        if ($existing_check_result->num_rows == 0) {
            // Führen Sie die Datenbankabfrage aus, um die Daten in die Tabelle "tg_data" einzufügen
            if (!empty($insert_data)) {
                $columns = implode(", ", array_keys($insert_data));
                $values = "'" . implode("', '", $insert_data) . "'";
                $insert_query = "INSERT INTO tg_data ($columns) VALUES ($values)";
                $conn->query($insert_query);
            }
        }
    }

    fclose($handle);
}

// Datenbankverbindung schließen
$conn->close();

// Temporäre Datei löschen
unlink($temp_file);
?>