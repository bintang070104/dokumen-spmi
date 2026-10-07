<?php
/**
 * Algoritma Knuth-Morris-Pratt (KMP) untuk Pencarian String
 * Digunakan dalam fitur pencarian dokumen SPMI
 */

/**
 * Membuat Longest Prefix Suffix (LPS) array
 * Kompleksitas: O(m) dimana m = panjang pattern
 */
function kmp_computeLPS(string $pattern): array {
    $m = strlen($pattern);
    $lps = array_fill(0, $m, 0);
    $len = 0; // panjang prefix suffix terpanjang sebelumnya
    $i = 1;

    while ($i < $m) {
        if ($pattern[$i] === $pattern[$len]) {
            $len++;
            $lps[$i] = $len;
            $i++;
        } else {
            if ($len !== 0) {
                // fallback ke prefix sebelumnya
                $len = $lps[$len - 1];
            } else {
                $lps[$i] = 0;
                $i++;
            }
        }
    }
    return $lps;
}

/**
 * Mencari pattern dalam text menggunakan KMP
 * Kompleksitas: O(n + m) dimana n = panjang text, m = panjang pattern
 * 
 * @return bool true jika pattern ditemukan, false jika tidak
 */
function kmp_search(string $text, string $pattern): bool {
    $n = strlen($text);
    $m = strlen($pattern);

    if ($m === 0) return true;
    if ($n === 0 || $m > $n) return false;

    $lps = kmp_computeLPS($pattern);
    $i = 0; // index untuk text
    $j = 0; // index untuk pattern

    while ($i < $n) {
        if ($pattern[$j] === $text[$i]) {
            $i++;
            $j++;
        }

        if ($j === $m) {
            return true; // pattern ditemukan di index ($i - $j)
        } elseif ($i < $n && $pattern[$j] !== $text[$i]) {
            if ($j !== 0) {
                $j = $lps[$j - 1];
            } else {
                $i++;
            }
        }
    }
    return false;
}

/**
 * Filter array dokumen menggunakan KMP
 * Digunakan setelah query SQL untuk pencarian yang lebih akurat
 */
function kmp_filter_documents(array $documents, string $keyword): array {
    if (empty($keyword)) {
        return $documents;
    }

    $keyword_lower = strtolower($keyword);
    $filtered = [];

    foreach ($documents as $doc) {
        $judul_lower = strtolower($doc['judul_dokumen'] ?? '');
        
        // Cari keyword di judul dokumen
        if (kmp_search($judul_lower, $keyword_lower)) {
            $filtered[] = $doc;
        }
    }

    return $filtered;
}
?>