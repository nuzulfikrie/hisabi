<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Pengesahan
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut mengandungi mesej ralat lalai yang digunakan oleh
    | kelas pengesah. Sesetengah peraturan ini mempunyai versi berbilang seperti
    | peraturan saiz. Jangan segan untuk mengubah setiap mesej ini di sini.
    |
    */

    'accepted' => ':attribute mesti diterima.',
    'accepted_if' => ':attribute mesti diterima apabila :other ialah :value.',
    'active_url' => ':attribute bukan URL yang sah.',
    'after' => ':attribute mesti tarikh selepas :date.',
    'after_or_equal' => ':attribute mesti tarikh selepas atau sama dengan :date.',
    'alpha' => ':attribute mesti mengandungi huruf sahaja.',
    'alpha_dash' => ':attribute mesti mengandungi huruf, nombor, sengkang dan garis bawah sahaja.',
    'alpha_num' => ':attribute mesti mengandungi huruf dan nombor sahaja.',
    'array' => ':attribute mesti jujukan.',
    'before' => ':attribute mesti tarikh sebelum :date.',
    'before_or_equal' => ':attribute mesti tarikh sebelum atau sama dengan :date.',
    'between' => [
        'numeric' => ':attribute mesti antara :min dan :max.',
        'file' => ':attribute mesti antara :min dan :max kilobait.',
        'string' => ':attribute mesti antara :min dan :max aksara.',
        'array' => ':attribute mesti mengandungi antara :min dan :max item.',
    ],
    'boolean' => 'Medan :attribute mesti benar atau palsu.',
    'confirmed' => 'Pengesahan :attribute tidak sepadan.',
    'current_password' => 'Kata laluan tidak betul.',
    'date' => ':attribute bukan tarikh yang sah.',
    'date_equals' => ':attribute mesti tarikh sama dengan :date.',
    'date_format' => ':attribute tidak sepadan dengan format :format.',
    'declined' => ':attribute mesti ditolak.',
    'declined_if' => ':attribute mesti ditolak apabila :other ialah :value.',
    'different' => ':attribute dan :other mesti berbeza.',
    'digits' => ':attribute mesti :digits digit.',
    'digits_between' => ':attribute mesti antara :min dan :max digit.',
    'dimensions' => ':attribute mempunyai dimensi imej yang tidak sah.',
    'distinct' => 'Medan :attribute mempunyai nilai pendua.',
    'email' => ':attribute mesti alamat e-mel yang sah.',
    'ends_with' => ':attribute mesti berakhir dengan salah satu daripada: :values.',
    'enum' => ':attribute yang dipilih tidak sah.',
    'exists' => ':attribute yang dipilih tidak sah.',
    'file' => ':attribute mesti fail.',
    'filled' => 'Medan :attribute mesti mempunyai nilai.',
    'gt' => [
        'numeric' => ':attribute mesti lebih besar daripada :value.',
        'file' => ':attribute mesti lebih besar daripada :value kilobait.',
        'string' => ':attribute mesti lebih besar daripada :value aksara.',
        'array' => ':attribute mesti mengandungi lebih daripada :value item.',
    ],
    'gte' => [
        'numeric' => ':attribute mesti lebih besar daripada atau sama dengan :value.',
        'file' => ':attribute mesti lebih besar daripada atau sama dengan :value kilobait.',
        'string' => ':attribute mesti lebih besar daripada atau sama dengan :value aksara.',
        'array' => ':attribute mesti mengandungi :value item atau lebih.',
    ],
    'image' => ':attribute mesti imej.',
    'in' => ':attribute yang dipilih tidak sah.',
    'in_array' => 'Medan :attribute tidak wujud dalam :other.',
    'integer' => ':attribute mesti integer.',
    'ip' => ':attribute mesti alamat IP yang sah.',
    'ipv4' => ':attribute mesti alamat IPv4 yang sah.',
    'ipv6' => ':attribute mesti alamat IPv6 yang sah.',
    'mac_address' => ':attribute mesti alamat MAC yang sah.',
    'json' => ':attribute mesti rentetan JSON yang sah.',
    'lt' => [
        'numeric' => ':attribute mesti kurang daripada :value.',
        'file' => ':attribute mesti kurang daripada :value kilobait.',
        'string' => ':attribute mesti kurang daripada :value aksara.',
        'array' => ':attribute mesti mengandungi kurang daripada :value item.',
    ],
    'lte' => [
        'numeric' => ':attribute mesti kurang daripada atau sama dengan :value.',
        'file' => ':attribute mesti kurang daripada atau sama dengan :value kilobait.',
        'string' => ':attribute mesti kurang daripada atau sama dengan :value aksara.',
        'array' => ':attribute mesti tidak mengandungi lebih daripada :value item.',
    ],
    'max' => [
        'numeric' => ':attribute mesti tidak lebih besar daripada :max.',
        'file' => ':attribute mesti tidak lebih besar daripada :max kilobait.',
        'string' => ':attribute mesti tidak lebih besar daripada :max aksara.',
        'array' => ':attribute mesti tidak mengandungi lebih daripada :max item.',
    ],
    'mimes' => ':attribute mesti fail jenis: :values.',
    'mimetypes' => ':attribute mesti fail jenis: :values.',
    'min' => [
        'numeric' => ':attribute mesti sekurang-kurangnya :min.',
        'file' => ':attribute mesti sekurang-kurangnya :min kilobait.',
        'string' => ':attribute mesti sekurang-kurangnya :min aksara.',
        'array' => ':attribute mesti mengandungi sekurang-kurangnya :min item.',
    ],
    'multiple_of' => ':attribute mesti gandaan :value.',
    'not_in' => ':attribute yang dipilih tidak sah.',
    'not_regex' => 'Format :attribute tidak sah.',
    'numeric' => ':attribute mesti nombor.',
    'password' => 'Kata laluan tidak betul.',
    'present' => 'Medan :attribute mesti wujud.',
    'prohibited' => 'Medan :attribute dilarang.',
    'prohibited_if' => 'Medan :attribute dilarang apabila :other ialah :value.',
    'prohibited_unless' => 'Medan :attribute dilarang melainkan :other berada dalam :values.',
    'prohibits' => 'Medan :attribute melarang :other daripada wujud.',
    'regex' => 'Format :attribute tidak sah.',
    'required' => 'Medan :attribute diperlukan.',
    'required_if' => 'Medan :attribute diperlukan apabila :other ialah :value.',
    'required_unless' => 'Medan :attribute diperlukan melainkan :other berada dalam :values.',
    'required_with' => 'Medan :attribute diperlukan apabila :values wujud.',
    'required_with_all' => 'Medan :attribute diperlukan apabila :values wujud.',
    'required_without' => 'Medan :attribute diperlukan apabila :values tidak wujud.',
    'required_without_all' => 'Medan :attribute diperlukan apabila tiada :values wujud.',
    'same' => ':attribute dan :other mesti sepadan.',
    'size' => [
        'numeric' => ':attribute mesti :size.',
        'file' => ':attribute mesti :size kilobait.',
        'string' => ':attribute mesti :size aksara.',
        'array' => ':attribute mesti mengandungi :size item.',
    ],
    'starts_with' => ':attribute mesti bermula dengan salah satu daripada: :values.',
    'string' => ':attribute mesti rentetan.',
    'timezone' => ':attribute mesti zon masa yang sah.',
    'unique' => ':attribute telah diambil.',
    'uploaded' => ':attribute gagal dimuat naik.',
    'url' => ':attribute mesti URL yang sah.',
    'uuid' => ':attribute mesti UUID yang sah.',

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Pengesahan Tersuai
    |--------------------------------------------------------------------------
    |
    | Di sini anda boleh menentukan mesej pengesahan tersuai untuk atribut
    | menggunakan konvensyen "attribute.rule" untuk menamakan baris. Ini
    | membolehkan anda menentukan mesej bahasa tersuai dengan pantas.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atribut Pengesahan Tersuai
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan untuk menukar placeholder atribut dengan
    | sesuatu yang lebih mesra pembaca seperti "Alamat E-Mel" dan bukannya
    | "email". Ini membantu kami membuat mesej lebih ekspresif.
    |
    */

    'attributes' => [],

];
