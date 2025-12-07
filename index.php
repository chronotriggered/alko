<?php
require 'vendor/autoload.php';
require 'config.php'; // DB_HOST, DB_USER, DB_PASS, DB_NAME

// tietoja
$TABLE_NAME = 'alko_tuotteet';
$ALKO_URI = "https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx";
$LOCAL_CSV_FILE = 'hinnasto.csv';

// moduulit
require __DIR__ . '/modules/db.php';
require __DIR__ . '/modules/import.php';
require __DIR__ . '/modules/filters.php';
require __DIR__ . '/modules/pagination.php';
require __DIR__ . '/modules/template.php';
require __DIR__ . '/modules/list.php';

/// jos tietokantaa ei ole päivitetty viimeiseen 24 tuntiin, päivitetään
$status_msg = '';
if ( needs_update($LOCAL_CSV_FILE, 86400) ) {
    try {
        excel_to_csv($ALKO_URI, $LOCAL_CSV_FILE);
        csvToDB($connect, $LOCAL_CSV_FILE, $TABLE_NAME);
        $status_msg = "<p style='color:green'>Tietokanta päivitetty onnistuneesti.</p>";
    } catch (Exception $e) {
        $status_msg = "<p style='color:red'>Päivitys epäonnistui: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// viimesin päivityspäivä perustuen CSV tiedoston muokkausaikaan 
$update_date = null;
if (file_exists($LOCAL_CSV_FILE)) {
    $timestamp = filemtime($LOCAL_CSV_FILE);
    $update_date = new DateTime();
    $update_date->setTimestamp(filemtime($LOCAL_CSV_FILE));
}           

// hae filterit ja luo sivutus
$filters = build_filters(); // palauttaa where_sql, params, sort, dir, filter_values
$pagination = paginate($connect, $TABLE_NAME, $filters['where_sql'], $filters['params'], 25);

// listan renderöinti eli tuotteiden näyttö sivulla
render_list($connect, $TABLE_NAME, $filters, $pagination, $status_msg, $update_date ?? null);