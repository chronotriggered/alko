<?php
function build_filters(): array {
    // input filterit (f_nimi voi sisältää vaikka vodka tai viini jne.)
    $f_nimi = filter_input(INPUT_GET, 'f_nimi', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
    $f_valm = filter_input(INPUT_GET, 'f_valm', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
    $f_maa  = filter_input(INPUT_GET, 'f_maa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
    $f_tyyp = filter_input(INPUT_GET, 'f_tyyp', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

    // numero filterit (f_hinta_min voi olla esimerkiksi 13.5)
    $f_hinta_min   = filter_input(INPUT_GET, 'f_hinta_min', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $f_hinta_max   = filter_input(INPUT_GET, 'f_hinta_max', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $f_litra_min   = filter_input(INPUT_GET, 'f_litra_min', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $f_litra_max   = filter_input(INPUT_GET, 'f_litra_max', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $f_kcal_min    = filter_input(INPUT_GET, 'f_kcal_min', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $f_kcal_max    = filter_input(INPUT_GET, 'f_kcal_max', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // sallitut nimitykset järkkäämiselle, turvallisuuden vuoksi
    $allowed_sort = ['numero','nimi','valmistaja','pullokoko','hinta','litrahinta','tyyppi','valmistusmaa','vuosikerta','alkoholi_prosentti','energia'];
    $sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (!in_array($sort, $allowed_sort)) $sort = 'numero';
    // suunta oletuksena nouseva
    $dir = strtoupper(filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'ASC');
    // jos suunta on laskeva ni käytetään sitä, muuten asetetaan nousevaksi
    $dir = ($dir === 'DESC') ? 'DESC' : 'ASC';

    // where lauseke array, joka myöhemmin yhdistetään AND:lla tarvittaessa (jos on useampi filteri)
    $where = [];
    // parametrit joita voi olla useampi, esim. nimi LIKE :nimi ja hinta >= :hinta_min
    $params = [];

    // jos filtteri ei oo tyhjä niin lisätään lauseke where arrayhin ja arvo params arrayhin (arvo ei ole varsinainen arvo vaan sidottava parametri kuten :nimi)
    if ($f_nimi !== '') { $where[] = "nimi LIKE :nimi"; $params[':nimi'] = "%$f_nimi%"; }
    if ($f_valm !== '') { $where[] = "valmistaja LIKE :valm"; $params[':valm'] = "%$f_valm%"; }
    if ($f_maa  !== '') { $where[] = "valmistusmaa LIKE :maa"; $params[':maa'] = "%$f_maa%"; }
    if ($f_tyyp !== '') { $where[] = "tyyppi LIKE :tyyp"; $params[':tyyp'] = "%$f_tyyp%"; }

    // hinta range
    if ($f_hinta_min !== null && $f_hinta_min !== '' && is_numeric($f_hinta_min)) {
        $where[] = "hinta >= :hinta_min";
        $params[':hinta_min'] = (float)$f_hinta_min;
    }
    if ($f_hinta_max !== null && $f_hinta_max !== '' && is_numeric($f_hinta_max)) {
        $where[] = "hinta <= :hinta_max";
        $params[':hinta_max'] = (float)$f_hinta_max;
    }

    // litrahinta range
    if ($f_litra_min !== null && $f_litra_min !== '' && is_numeric($f_litra_min)) {
        $where[] = "litrahinta >= :litra_min";
        $params[':litra_min'] = (float)$f_litra_min;
    }
    if ($f_litra_max !== null && $f_litra_max !== '' && is_numeric($f_litra_max)) {
        $where[] = "litrahinta <= :litra_max";
        $params[':litra_max'] = (float)$f_litra_max;
    }

    // kcal range
    if ($f_kcal_min !== null && $f_kcal_min !== '' && is_numeric($f_kcal_min)) {
        $where[] = "energia >= :kcal_min";
        $params[':kcal_min'] = (float)$f_kcal_min;
    }
    if ($f_kcal_max !== null && $f_kcal_max !== '' && is_numeric($f_kcal_max)) {
        $where[] = "energia <= :kcal_max";
        $params[':kcal_max'] = (float)$f_kcal_max;
    }

    // tässä yhdistetään filterit yhdeksi where lausekkeeksi
    $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    // palautetaan tarvittavat tiedot
    return [
        'where_sql' => $where_sql,
        'params' => $params,
        'sort' => $sort,
        'dir' => $dir,
        'values' => [
            'f_nimi'=>$f_nimi, 'f_valm'=>$f_valm, 'f_maa'=>$f_maa, 'f_tyyp'=>$f_tyyp,
            'f_hinta_min'=>$f_hinta_min, 'f_hinta_max'=>$f_hinta_max,
            'f_litra_min'=>$f_litra_min, 'f_litra_max'=>$f_litra_max,
            'f_kcal_min'=>$f_kcal_min, 'f_kcal_max'=>$f_kcal_max
        ]
    ];
}