<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
}

h2 {
    text-align:center;
}

table {
    width:100%;
    border-collapse: collapse;
    margin-top:15px;
}

th, td {
    border:1px solid #000;
    padding:6px;
    text-align:left;
}

th {
    background:#d9d9d9;
}
</style>

<h2>Laporan Admin Basecamp</h2>

<p>Tanggal Cetak: {{ now()->format('d-m-Y H:i:s') }}</p>
<p>Periode: {{ $from ?? '-' }} - {{ $to ?? '-' }}</p>

<hr>

<p>Total Basecamp: {{ $summary['total_basecamp'] }}</p>
<p>Total Booking: {{ $summary['total_bookings'] }}</p>
<p>Total Pendapatan: Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</p>

<hr>

<table>
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama Pendaki</th>
            <th>Basecamp</th>
            <th>Tanggal</th>
            <th>Jumlah Pendaki</th>
            <th>Status</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($basecampData as $data)
        <tr>
            <td>{{ $data->order_id ?? $data->id }}</td>
            <td>{{ $data->user->name ?? '-'}}</td>
            <td>{{ $data->basecamp->nama ?? '-' }}</td>
            <td>{{ $data->tanggal_naik }}</td>
            <td>{{ $data->jumlah_pendaki }}</td>
            <td>{{ ucfirst($data->status) }}</td>
            <td>Rp {{ number_format($data->total_price, 0, ',', '.') }}</
        </tr>
        @endforeach
    </tbody>
</table>

<br><br>
<p>Dicetak oleh: {{ $user->name }}</p>

<p style="text-align:center;font-size:10px;">
Sistem Booking Pendakian
</p>