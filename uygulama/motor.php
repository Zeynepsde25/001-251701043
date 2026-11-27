<?php
function adim(array $s, string $a): array {
    if (!empty($s['bitti'])) {
        return $s;
    }

    $no = (int)$s['no'];
    $x = $no % 10;              // Öğrenci numaranızın son rakamı
    $y = intdiv($no, 10) % 10;  // Öğrenci numaranızın sondan ikinci rakamı

    // ==========================================
    // 1. GRUP: MÜŞTERİ GELDİĞİNDE ('M')
    // ==========================================
    if ($a === 'M') {
        $s['musteri_sira'] = (int)$s['musteri_sira'] + 1;
        $s['gunluk_musteri'] = (int)$s['gunluk_musteri'] + 1;

        $m = musteri($no, $s['musteri_sira']);
        $urun = $m['urun'];
        $fiyat = $s['urunler'][$urun]['fiyat'];
        $stok = $s['urunler'][$urun]['stok'];

        // Müşteri tipine göre indirim oranlarını buraya yazın
        if ($m['tip'] === 'Burslu Öğrenci') {
            $fiyat = (int)round($fiyat * 0.5);
        } elseif ($m['tip'] === 'Öğrenci') {
            $fiyat = (int)round($fiyat * 0.8);
        }

        // Satış şartları (Stok var mı, bütçe yetiyor mu, elektrik kesik mi?)
        if ($stok <= 0 || $m['butce'] < $fiyat) {
            $satis = false;
        } elseif ($s['elektrik'] > 0 && ($urun === 'tost' || $urun === 'kahve')) {
            $satis = false;
        } else {
            $satis = true;
        }

        if ($satis) {
            // BAŞARILI SATIŞ
            $s['urunler'][$urun]['stok']--;
            $s['kasa'] += $fiyat;
            $s['memnuniyet'] = min(100, $s['memnuniyet'] + 2);
            $s['seri']++;
            $s['ret_sayac'][$urun] = 0;
        } else {
            // RET DURUMU
            $s['memnuniyet'] -= 4;
            $s['seri'] = 0;
            $s['gunluk_ret']++;
            $s['ret_sayac'][$urun]++;

            // Üst üste ret olunca zam kuralı
            if ($s['ret_sayac'][$urun] >= 2 && !$s['zam'][$urun]) {
                $s['urunler'][$urun]['fiyat'] += 5;
                $s['zam'][$urun] = true;
            }
        }

        if ($s['elektrik'] > 0) {
            $s['elektrik']--;
        }

        // ==========================================
        // 2. GRUP: MÜŞTERİ SONRASI OLAYLAR
        // ==========================================
        $olay = olay($no, $s['musteri_sira']);
        if ($olay === 'elektrik') {
            $s['elektrik'] = 3;
            $s['memnuniyet'] -= 5;
        } elseif ($olay === 'yogunluk') {
            $s['yogunluk'] = true;
        } elseif ($olay === 'tedarik') {
            $s['urunler'][$urun]['stok'] += 2;
        } elseif ($olay === 'bahsis') {
            $s['kasa'] += 20;
            $s['memnuniyet'] = min(100, $s['memnuniyet'] + 2);
        }
    }

    // ==========================================
    // 3. GRUP: TEDARİK HAMLESİ ('T-urun-adet')
    // ==========================================
    elseif (strpos($a, 'T-') === 0) {
        list(, $urun, $adetStr) = explode('-', $a);
        $adet = (int)$adetStr;

        $birimMaliyet = (int)round($s['urunler'][$urun]['fiyat'] * 0.5);
        $toplamMaliyet = $birimMaliyet * $adet;

        if ($s['kasa'] >= $toplamMaliyet) {
            $s['kasa'] -= $toplamMaliyet;
            $s['urunler'][$urun]['stok'] += $adet;
        }
    }

    // ==========================================
    // 4. GRUP: GÜN SONU ('G') VE OYUN BİTİŞİ
    // ==========================================
    elseif ($a === 'G') {
        $s['kasa'] -= 100;
        $s['gun']++;
        $s['gunluk_musteri'] = 0;
        $s['gunluk_ret'] = 0;
        $s['yogunluk'] = false;
        $s['seri'] = 0;
    }

    if ($s['kasa'] <= 0 || $s['memnuniyet'] <= 0) {
        $s['bitti'] = true;
        $s['kazandi'] = false;
    } elseif ($s['gun'] > 5) {
        $s['bitti'] = true;
        $s['kazandi'] = true;
    }

    return $s;
}
