<?php

return [
    /**
     * Path to save compiled and minified files
     */
    'path' => 'theme/compiled',

    /**
     * If true, all files will be minified in one for each type
     */
    'enable_minify' => true,

    /**
     * Override `enable_minify` setting for site managers
     */
    'enable_minify_for_managers' => false,

    /**
     * If true, time suffix will be appended
     * to resulting filenames to prevent caching by browser
     */
    'disable_caching' => true,

    /**
     * Override `disable_caching` setting for site managers
     */
    'disable_caching_for_managers' => false,
];
