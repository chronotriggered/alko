<?php
// Renderöidään lista tuotteista taulukkona
function render_list(PDO $connect, string $TABLE_NAME, array $filters, array $pagination, string $status_msg = '', ?DateTime $update_date = null): void {
    render_header("Alko Hinnasto");
    echo $status_msg; 

    // Filterit
    $vals = $filters['values'];
    ?>
    <h1 style="text-align:center;">ALKON HINNASTO (<?php echo "Tuotteita: " . (int)$pagination['product_count'] . " / " . " Päivitetty viimeksi: " . ($update_date ? $update_date->format('d.m.Y') : '?'); ?>)</h1>
    <div style="margin-bottom:20px; text-align:center;">
        <!-- Form jossa input kentät suodattimille -->
        <form method="GET" action="">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($filters['sort']); ?>">
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($filters['dir']); ?>">
            <!-- Nämä teksti inputit tekevät LIKE suodatuksen, joten voivat olla osittaisia arvoja -->
            <input name="f_nimi" placeholder="Nimi" value="<?php echo htmlspecialchars($vals['f_nimi'] ?? ''); ?>">
            <input name="f_valm" placeholder="Valmistaja" value="<?php echo htmlspecialchars($vals['f_valm'] ?? ''); ?>">
            <input name="f_maa" placeholder="Maa" value="<?php echo htmlspecialchars($vals['f_maa'] ?? ''); ?>">
            <input name="f_tyyp" placeholder="Tyyppi" value="<?php echo htmlspecialchars($vals['f_tyyp'] ?? ''); ?>">

            <!-- Nämä numeeriset inputit tekevät suodatuksen minimi ja maksimiarvojen avulla -->
            <input name="f_hinta_min" placeholder="Hinta min" style="width:90px" value="<?php echo htmlspecialchars($vals['f_hinta_min'] ?? ''); ?>">
            <input name="f_hinta_max" placeholder="Hinta max" style="width:90px" value="<?php echo htmlspecialchars($vals['f_hinta_max'] ?? ''); ?>">

            <input name="f_litra_min" placeholder="L-Hinta min" style="width:110px" value="<?php echo htmlspecialchars($vals['f_litra_min'] ?? ''); ?>">
            <input name="f_litra_max" placeholder="L-Hinta max" style="width:110px" value="<?php echo htmlspecialchars($vals['f_litra_max'] ?? ''); ?>">

            <input name="f_kcal_min" placeholder="Kcal min" style="width:90px" value="<?php echo htmlspecialchars($vals['f_kcal_min'] ?? ''); ?>">
            <input name="f_kcal_max" placeholder="Kcal max" style="width:90px" value="<?php echo htmlspecialchars($vals['f_kcal_max'] ?? ''); ?>">

            <button type="submit">Hae</button>
            <?php if (array_filter($vals)): ?>
                <a href="index.php"><button type="button">Tyhjennä</button></a>
            <?php endif; ?>
        </form>
    </div>
    <?php

     // Sivutukset
    $qp = $_GET; // nykyiset parametrit sisältäen sivunumeron
    unset($qp['page']); // poistetaan sivunumero sillä se lisätään erikseen
    $base = http_build_query($qp); // ilman sivunumeroa oleva query
    $page = $pagination['page']; // nykyinen sivunumero joka määritettiin paginate funktiossa
    $page_count = $pagination['page_count']; // sivujen määrä kokonaisuudessaan joka määritettiin paginate funktiossa

    echo '<div class="pagination">';
    if ($page > 1) {
        // base meinaa esim "?f_nimi=viski&sort=hinta&dir=ASC" ja ne säilyy linkeissä
        echo '<a href="?page=1'.($base ? '&'.$base : '').'">« First</a> ';
        echo '<a href="?page='.($page-1).($base ? '&'.$base : '').'">‹ Previous</a> ';
    }
    echo "<span>Sivu $page / $page_count</span>";
    if ($page < $page_count) {
        echo ' <a href="?page='.($page+1).($base ? '&'.$base : '').'">Next ›</a>';
        echo ' <a href="?page='.$page_count.($base ? '&'.$base : '').'">Last »</a>';
    }
    echo '</div>';


    // SELECT kysely jossa sidotaan esim. f_hinta_min arvoon 30.5 ja järjestetään hinnan mukaan nousevasti
    $sql = "SELECT * FROM `$TABLE_NAME` {$filters['where_sql']} ORDER BY {$filters['sort']} {$filters['dir']} LIMIT :limit OFFSET :offset";
    $stmt = $connect->prepare($sql);
    foreach ($filters['params'] as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $pagination['items_per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();

    // Funktio järjestystä varten
    $make_sort_link = function($col, $label) use ($filters) {
        $current_sort = $filters['sort'];
        $current_dir = $filters['dir'];
        
        // Suunnan määritys
        $new_dir = ($current_sort === $col && $current_dir === 'ASC') ? 'DESC' : 'ASC';
        
        // Nuoli avittamaan havainnointia
        $arrow = '';
        if ($current_sort === $col) {
            $arrow = ($current_dir === 'ASC') ? ' &uarr;' : ' &darr;';
        }
        
        // Hakuehdot järjestyksen lisäksi
        $params = $filters['values'];
        $params['sort'] = $col;
        $params['dir'] = $new_dir;
        $params['page'] = 1;
        
        // Tyhjät arvot pois
        $params = array_filter($params, function($v) { return $v !== ''; });
        
        $url = '?' . http_build_query($params);
        return '<a href="' . htmlspecialchars($url) . '" style="color:black; text-decoration:none; font-weight:bold;">' . $label . $arrow . '</a>';
    };

    // Taulukko ja otsikkolinkit
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>' . $make_sort_link('numero', 'Numero') . '</th>';
    echo '<th>' . $make_sort_link('nimi', 'Nimi') . '</th>';
    echo '<th>' . $make_sort_link('valmistaja', 'Valmistaja') . '</th>';
    echo '<th>' . $make_sort_link('pullokoko', 'Koko') . '</th>';
    echo '<th>' . $make_sort_link('hinta', 'Hinta') . '</th>';
    echo '<th>' . $make_sort_link('litrahinta', 'Litrahinta') . '</th>';
    echo '<th>' . $make_sort_link('tyyppi', 'Tyyppi') . '</th>';
    echo '<th>' . $make_sort_link('valmistusmaa', 'Maa') . '</th>';
    echo '<th>' . $make_sort_link('vuosikerta', 'Vuosi') . '</th>';
    echo '<th>' . $make_sort_link('alkoholi_prosentti', 'Alk %') . '</th>';
    echo '<th>' . $make_sort_link('energia', 'Energia') . '</th>';
    echo '</tr></thead><tbody>';

    // Rivit tulostetaan taulukkoon ja ne näytetään sivulla. Filteröinti yms on jo tehty tässä vaiheessa.
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['numero']).'</td>';
        echo '<td style="text-align:left;">'.htmlspecialchars($row['nimi']).'</td>';
        echo '<td style="text-align:left;">'.htmlspecialchars($row['valmistaja']).'</td>';
        echo '<td>'.htmlspecialchars($row['pullokoko']).'</td>';
        echo '<td>'.htmlspecialchars($row['hinta']).'</td>';
        echo '<td>'.htmlspecialchars($row['litrahinta']).'</td>';
        echo '<td>'.htmlspecialchars($row['tyyppi']).'</td>';
        echo '<td>'.htmlspecialchars($row['valmistusmaa']).'</td>';
        echo '<td>'.htmlspecialchars($row['vuosikerta']).'</td>';
        echo '<td>'.htmlspecialchars($row['alkoholi_prosentti']).'</td>';
        echo '<td>'.htmlspecialchars($row['energia']).'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    echo '<div class="pagination">';
    if ($page > 1) {
        // base meinaa esim "?f_nimi=viski&sort=hinta&dir=ASC" ja ne säilyy linkeissä
        echo '<a href="?page=1'.($base ? '&'.$base : '').'">« First</a> ';
        echo '<a href="?page='.($page-1).($base ? '&'.$base : '').'">‹ Previous</a> ';
    }
    echo "<span>Sivu $page / $page_count</span>";
    if ($page < $page_count) {
        echo ' <a href="?page='.($page+1).($base ? '&'.$base : '').'">Next ›</a>';
        echo ' <a href="?page='.$page_count.($base ? '&'.$base : '').'">Last »</a>';
    }
    echo '</div>';

    render_footer();
}