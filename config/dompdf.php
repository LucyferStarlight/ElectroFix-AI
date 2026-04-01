<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dompdf Public Path
    |--------------------------------------------------------------------------
    |
    | En shared hosting la carpeta pública puede no ser base_path('public').
    | Esta ruta se usa para que Dompdf pueda resolver correctamente assets.
    |
    */
    'public_path' => env('DOMPDF_PUBLIC_PATH', '/home/elect152/public_html'),

    'options' => [
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),
    ],
];

