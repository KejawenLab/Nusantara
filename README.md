# KejawenLab Nusantara

Nusantara adalah Script untuk mengambil data terbaru daerah di Indoensia mulai dari Propinsi hingga Desa/Kelurahan

## Instalasi

* Cloning

```bash
git clone https://github.com/KejawenLab/Nusantara
cd Nusantara
composer update --prefer-dist -vvv
```

* Untuk Existing Project

```bash
composer req kejawenlab/nusantara
```

## Cara Menggunakan

```bash
php nusantara
```

## Format Data

Data hasil crawling akan tersimpan di file `wilayah.json` dengan format sebagai berikut

```php
[KODE_PROPINSI] =>
    'name' => [NAMA_PROPINSI],
    'district' => [
        [KODE_KABUPATEN] => [
            'name' => [NAMA_KABUPATEN],
            'sub_district' => [
                [KODE_KECAMATAN] => [
                    'name' => [NAMA_KECAMATAN],
                    'village => [
                        [KODE_DESA] => [NAMA_DESA]
                    ] 
                ]
            ]
        ]
    ]
``` 

## Menggunakan `Nusantara` tanpa command line interface

```php
use KejawenLab\Nusantara\Nusantara;

$nusantara = new Nusantara();
//Data sebagai array dengan format seperti di atas
$result = $nusantara->fetch();
```
