<?php

return [

    'class_namespace' => 'App\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'components.layouts.app',

    'lazy_placeholder' => null,

    'temporary_file_upload' => [
        'disk'        => null,
        'rules'       => ['required', 'file', 'max:12288'],
        'directory'   => null,
        'middleware'  => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
    ],

    'back_button_cache' => false,

    'render_on_redirect' => false,

    'legacy_model_binding' => false,

    'inject_assets' => true,

    'inject_morph_markers' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#6366f1',
    ],

    'pagination_theme' => 'tailwind',

    'html_morph_markers' => true,

    'enable_turbo' => false,

];
