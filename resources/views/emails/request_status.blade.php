Hallo {{ $user->name }},

status pengajuan admin gunung kamu telah diproses dengan hasil: {{ strtoupper($req->status) }}.

jenis pengajuan: {{ $req->request_type }}

keterangan: {{ $req->reason }}

Terima Kasih