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

<h2>Laporan Admin Gunung</h2>

<p>Tanggal Cetak: {{ now()->format('d-m-Y H:i:s') }}</p>
<p>Periode: {{ $from ?? '-' }} - {{ $to ?? '-' }}</p>

<hr>

<p>Total Gunung: {{ $summary['total_gunung'] }}</p>
<p>Total Basecamp: {{ $summary['total_basecamp'] }}</p>
<p>Total Booking: {{ $summary['total_bookings'] }}</p>
<p>Total Pendapatan: Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</p>

<hr>

<table>
    <thead>
        <tr>
            <th>Nama Gunung</th>
            <th>Jumlah Basecamp</th>
            <th>Total Bookings</th>
            <th>Total Pendapatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($gunungData as $data)
        <tr>
            <td>{{ $data['nama'] }}</td>
            <td>{{ $data['basecamp_count'] }}</td>
            <td>{{ $data['booking_count'] }}</td>
            <td>Rp {{ number_format($data['income'], 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br><br>
<p>Dicetak oleh: {{ $user->name }}</p>

<p style="text-align:center;font-size:10px;">
Sistem Booking Pendakian
</p>