<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Booking</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .box {
            border: 1px solid #000;
            padding: 20px;
        }

        h2 {
            text-align: center;
        }

        p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Ticket Booking</h2>

        <p><strong>Kode Booking:</strong> {{ $booking->order_id ?? $booking->id }}</p>
        <p><strong>Nama Pemesan:</strong> {{ $user->name }}</p>
        <p><strong>Basecamp:</strong> {{ $booking->basecamp->nama }}</p>
        <p><strong>Tanggal Naik:</strong> {{ $booking->tanggal_naik }}</p>
        <p><strong>Jumlah Pendaki:</strong> {{ $booking->jumlah_pendaki }}</p>
        <p><strong>Harga Per Orang:</strong> Rp {{ number_format($booking->harga_per_orang, 0, ',', '.') }}</p>
        <p><strong>Total Harga:</strong> Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($booking->status) }}</p>

        <p style="margin-top: 20px; font-size: 14px;">
            Harap Tunjukan tiket ini kepada petugas saat check-in di basecamp. Terima kasih telah memesan dengan kami!
        </p>
    </div>
</body>
</html>