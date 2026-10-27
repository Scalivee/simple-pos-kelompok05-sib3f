<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #999; padding: 4px 8px; text-align: left; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Penjualan</h1>
    <p>Periode {{ $from }} s.d. {{ $to }}</p>
    <p>Kategori: {{ $categoryName }}</p>
    <p>Total mencakup seluruh nilai transaksi yang sesuai filter.</p>
    <p>Total penjualan: <strong>Rp {{ number_format($totalPenjualan) }}</strong> dari {{ $jumlahTransaksi }} transaksi</p>

    
    <h2 style="font-size: 15px; margin-top: 24px;">10 Produk Terlaris</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">No.</th>
                <th>Nama Produk</th>
                <th class="right" style="width: 30%;">Jumlah Terjual</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($produkTerlaris as $index => $produk)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $produk->name }}</td>
                    <td class="right">{{ number_format($produk->total_terjual) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">
                        Tidak ada data penjualan produk pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p>50 transaksi terbaru pada periode ini:</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $t->user->name }}</td>
                    <td class="right">Rp {{ number_format($t->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
