<?php
function paginate(PDO $connect, string $table, string $where_sql, array $params, int $items_per_page = 25): array {
    // haetaan nykynen sivunumero GETistä, oletuksena 1
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    if ($page < 1) $page = 1;

    // kokonaisrivit riippuen filtereistä
    $count_sql = "SELECT COUNT(*) FROM `$table` $where_sql";
    $stmt = $connect->prepare($count_sql);
    // sidotaan parametrit kuten :nimi, :hinta_min arvoihin kuten "viini" ja 173
    // rivien määrä on filterien määrä. jos esim. litrahinta ja valmistusmaa niin rivejä on 2
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    // fetchColumn palauttaa ensimmäisen sarakkeen ensimmäisen rivin eli COUNT(*) arvon
    $total = (int)$stmt->fetchColumn();

    // jos sivumäärä on isompi ku 0 niin lasketaan sivumäärä ja offset
    $page_count = $total > 0 ? (int)ceil($total / $items_per_page) : 1;
    // jos sivunumero on isompi ku sivumäärä niin asetetaan se sivumääräksi
    if ($page > $page_count) $page = $page_count;
    // offset on tässä tapauksessa 25 eli montako riviä hypätään yli
    $offset = ($page - 1) * $items_per_page;

    return [
        'page' => $page,
        'items_per_page' => $items_per_page,
        'offset' => $offset,
        'product_count' => $total,
        'page_count' => $page_count
    ];
}