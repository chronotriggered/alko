<?php
use Shuchkin\SimpleXLSX;

// Jossei päivitetty viimeiseen 24 tuntiin niin päivitetään
function needs_update(string $file, int $ttl = 86400): bool {
    return !file_exists($file) || (time() - filemtime($file) > $ttl);
}

// Lataa excelin urlista ja muuntaa CSV
function excel_to_csv(string $uri, string $output_csv): void {
    $excel_content = @file_get_contents($uri);
    if ($excel_content === false) throw new Exception("Lataus epäonnistui.");
    file_put_contents('temp.xlsx', $excel_content);

    if ($xlsx = SimpleXLSX::parse('temp.xlsx')) {
        $f = fopen($output_csv, 'wb');
        foreach ($xlsx->readRows() as $r) {
            fputcsv($f, $r);
        }
        fclose($f);
    } else {
        unlink('temp.xlsx');
        throw new Exception(SimpleXLSX::parseError());
    }
    unlink('temp.xlsx');
}

// Vastaanottaa CSV ja vie sen sisältämät rivit tietokantaan
function csvToDB(PDO $connect, string $LOCAL_CSV_FILE, string $TABLE_NAME): void {
    $connect->exec("TRUNCATE TABLE `$TABLE_NAME`");
    $connect->beginTransaction();

    $params = $connect->prepare("INSERT INTO `$TABLE_NAME` (numero, nimi, valmistaja, pullokoko, hinta, litrahinta, tyyppi, valmistusmaa, vuosikerta, alkoholi_prosentti, energia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (($handle = fopen($LOCAL_CSV_FILE, 'r')) === false) {
        throw new Exception("CSV-avaus epäonnistui.");
    }
    // Skippaa header rivit eli turhat otsikot
    for ($i=0; $i<4; $i++) fgetcsv($handle, 2000, ",");

    while (($row = fgetcsv($handle, 2000, ",")) !== false) {
        $clean = function($val) {
            $v = trim((string)$val);
            $v = str_replace([' l', ' %', ' kcal', '€'], '', $v);
            return $v === '' ? null : $v;
        };
        // Päätetään mitkä rivit otetaan talteen eli (numero, nimi, valmistaja, pullokoko, hinta, litrahinta, tyyppi, valmistusmaa, vuosikerta, alkoholi_prosentti, energia)
        $data = [
            $clean($row[0] ?? null),
            $clean($row[1] ?? null),
            $clean($row[2] ?? null),
            $clean($row[3] ?? null),
            $clean($row[4] ?? null),
            $clean($row[5] ?? null),
            $clean($row[8] ?? null),
            $clean($row[12] ?? null),
            $clean($row[14] ?? null),
            $clean($row[21] ?? null),
            $clean($row[27] ?? null),
        ];
        $params->execute($data);
    }
    fclose($handle);
    $connect->commit();
}