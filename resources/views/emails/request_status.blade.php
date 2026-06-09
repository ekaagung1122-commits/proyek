<h2>Pengajuan Admin Diproses</h2>

<p>
Status pengajuan admin kamu telah diproses dengan hasil:
<b>{{ strtoupper($req->status) }}</b>
</p>

<p>
Jenis Pengajuan:
<b>{{ strtoupper(str_replace('_', ' ', $req->request_type)) }}</b>
</p>

@if($req->reason)
<p>
Keterangan:
<b>{{ $req->reason }}</b>
</p>
@endif

<p>Terima kasih.</p>