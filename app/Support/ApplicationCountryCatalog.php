<?php

namespace App\Support;

class ApplicationCountryCatalog
{
    /**
     * @return array<int, array{code:string,label:string}>
     */
    public static function options(): array
    {
        return [
            ['code' => 'DE', 'label' => 'Almanya'],
            ['code' => 'AT', 'label' => 'Avusturya'],
            ['code' => 'NL', 'label' => 'Hollanda'],
            ['code' => 'BE', 'label' => 'Belcika'],
            ['code' => 'FR', 'label' => 'Fransa'],
            ['code' => 'IT', 'label' => 'Italya'],
            ['code' => 'ES', 'label' => 'Ispanya'],
            ['code' => 'PL', 'label' => 'Polonya'],
            ['code' => 'CZ', 'label' => 'Cekya'],
            ['code' => 'HU', 'label' => 'Macaristan'],
            ['code' => 'CH', 'label' => 'Isvicre'],
            ['code' => 'TR', 'label' => 'Turkiye'],
        ];
    }
}

