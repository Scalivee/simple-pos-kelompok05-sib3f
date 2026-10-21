# Pengukuran Memori: get() vs lazyById()

## Dataset

Pengukuran dilakukan menggunakan sekitar 52.500 baris pada tabel `transactions`.

## Hasil Pengukuran

| Cara membaca data | Peak memory (MB) |
|---|---:|
| `Transaction::lazyById()` | 40.3 MB |
| `Transaction::get()` | 98.6 MB |

## Kesimpulan Kelompok

Berdasarkan hasil pengukuran, metode `lazyById()` menggunakan peak memory sebesar 40.3 MB, sedangkan metode `get()` menggunakan 98.6 MB. Metode `lazyById()` lebih hemat memori pada pengujian ini karena data diproses secara bertahap, sedangkan `get()` memuat seluruh hasil query ke memori sekaligus.