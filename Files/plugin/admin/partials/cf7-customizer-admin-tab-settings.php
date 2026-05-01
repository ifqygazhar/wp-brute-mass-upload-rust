<?php
$style_scheme_slug = 'default';
$style_schemes = get_option('cf7cstmzr_style_schemes', array());

if (empty($style_schemes)) {
    delete_option('cf7cstmzr_enabled_globally');
    $style_scheme_slug = 'default';
} else {
    if (empty($style_schemes[$style_scheme_slug])) {
        $style_scheme_slug = 'default';
    }
}

$style_scheme = Cf7_Style_Scheme::normalize_style_sheme($style_schemes, $style_scheme_slug);