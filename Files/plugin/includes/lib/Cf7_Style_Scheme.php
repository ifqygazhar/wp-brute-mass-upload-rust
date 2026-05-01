<?php


class Cf7_Style_Scheme {
    public static function get_default_style_scheme() {
        return array(
            'title' => __('Default Scheme', 'cf7-styler'),
            'scheme' => array(
                'form' => array(
                    'padding' => array(
                        'top' => '20',
                        'right' => '20',
                        'bottom' => '20',
                        'left' => '20',
                    ),
                    'margin' => array(
                        'top' => '15',
                        'bottom' => '15',
                    ),
                    'border' => array(
                        'width' => array(
                            'top' => '1',
                            'right' => '1',
                            'bottom' => '1',
                            'left' => '5',
                        ),
                        'radius' => '10',
                        'color' => '#1e73be',
                    ),
                ),
                'input' => array(
                    'full-width' => 'yes',
                    'text' => array(
                        'line-height' => '1.6'
                    ),
                    'bg' => array(
                        'color' => '#ffffff'
                    ),
                    'padding' => array(
                        'top' => '5',
                        'right' => '10',
                        'bottom' => '5',
                        'left' => '10',
                    ),
                    'border' => array(
                        'width' => array(
                            'top' => '1',
                            'right' => '1',
                            'bottom' => '1',
                            'left' => '3',
                        ),
                        'radius' => '5',
                        'color' => '#1e73be',
                    ),
                ),
                'button' => array(
                    'text' => array(
                        'color' => '#ffffff',
                        'color-hover' => '#1e73be',
                        'line-height' => '1.6',
                    ),
                    'bg' => array(
                        'color' => '#1e73be',
                        'color-hover' => '#ffffff',
                    ),
                    'padding' => 5,
                    'border' => array(
                        'width' => '2',
                        'radius' => '10',
                        'color' => '#1e73be',
                        'color-hover' => '#1e73be',
                    ),
                    'shadow' => array(
                        'opacity' => '0.5',
                        'vertical-length' => '5',
                        'blur-radius' => '5',
                        'spread-radius' => '-5',
                        'color' => '#000000',
                        'position' => 'outline',
                    )
                ),
            ),
        );
    }

    public static function normalize_style_sheme($style_schemes, $slug) {
        $style_scheme_settings = !empty($style_schemes[$slug]['scheme']) ? $style_schemes[$slug]['scheme'] : array();
        $style_scheme = array();

        // General Form Styles
        $style_scheme['form_text_color'] = !empty($style_scheme_settings["form"]["text"]["color"]) ? $style_scheme_settings["form"]["text"]["color"] : '';
        $style_scheme['form_text_label_color'] = !empty($style_scheme_settings["form"]["text"]["label-color"]) ? $style_scheme_settings["form"]["text"]["label-color"] : '';
        $style_scheme['form_text_link_color'] = !empty($style_scheme_settings["form"]["text"]["link-color"]) ? $style_scheme_settings["form"]["text"]["link-color"] : '';
        $style_scheme['form_text_link_hover_color'] = !empty($style_scheme_settings["form"]["text"]["link-hover-color"]) ? $style_scheme_settings["form"]["text"]["link-hover-color"] : '';
        $style_scheme['form_text_font_family'] = !empty($style_scheme_settings["form"]["text"]["font-family"]) ? $style_scheme_settings["form"]["text"]["font-family"] : '';


        $style_scheme['form_bg_color'] = !empty($style_scheme_settings["form"]["bg"]["color"]) ? $style_scheme_settings["form"]["bg"]["color"]: '';
        $style_scheme['form_bg_img'] = !empty($style_scheme_settings["form"]["bg"]["img"]) ? $style_scheme_settings["form"]["bg"]["img"] : '';
        $style_scheme['form_bg_img_opacity'] = !empty($style_scheme_settings["form"]["bg"]["img-opacity"]) ? $style_scheme_settings["form"]["bg"]["img-opacity"] : '';
        $style_scheme['form_bg_img_size'] = !empty($style_scheme_settings["form"]["bg"]["img-size"]) ? $style_scheme_settings["form"]["bg"]["img-size"] : '';
        $style_scheme['form_bg_img_position'] = !empty($style_scheme_settings["form"]["bg"]["img-position"]) ? $style_scheme_settings["form"]["bg"]["img-position"] : '';
        $style_scheme['form_text_size'] = !empty($style_scheme_settings["form"]["text"]["size"]) ? $style_scheme_settings["form"]["text"]["size"] : '';
        $style_scheme['form_text_label_size'] = !empty($style_scheme_settings["form"]["text"]["label-size"]) ? $style_scheme_settings["form"]["text"]["label-size"] : '';
        $style_scheme['form_text_label_weight'] = !empty($style_scheme_settings["form"]["text"]["label-weight"]) ? $style_scheme_settings["form"]["text"]["label-weight"] : '';
        $style_scheme['form_text_label_style'] = !empty($style_scheme_settings["form"]["text"]["label-style"]) ? $style_scheme_settings["form"]["text"]["label-style"] : '';

        $style_scheme['form_padding'] = !empty($style_scheme_settings["form"]["padding"]) && !is_array($style_scheme_settings["form"]["padding"]) ? $style_scheme_settings["form"]["padding"] : '';
        $style_scheme['form_padding_top'] = !empty($style_scheme_settings["form"]["padding"]['top']) ? $style_scheme_settings["form"]["padding"]['top'] : '';
        $style_scheme['form_padding_right'] = !empty($style_scheme_settings["form"]["padding"]['right']) ? $style_scheme_settings["form"]["padding"]['right'] : '';
        $style_scheme['form_padding_bottom'] = !empty($style_scheme_settings["form"]["padding"]['bottom']) ? $style_scheme_settings["form"]["padding"]['bottom'] : '';
        $style_scheme['form_padding_left'] = !empty($style_scheme_settings["form"]["padding"]['left']) ? $style_scheme_settings["form"]["padding"]['left'] : '';

        $style_scheme['form_margin'] = !empty($style_scheme_settings["form"]["margin"]) && !is_array($style_scheme_settings["form"]["margin"]) ? $style_scheme_settings["form"]["margin"] : '';
        $style_scheme['form_margin_top'] = !empty($style_scheme_settings["form"]["margin"]['top']) ? $style_scheme_settings["form"]["margin"]['top'] : '';
        $style_scheme['form_margin_right'] = !empty($style_scheme_settings["form"]["margin"]['right']) ? $style_scheme_settings["form"]["margin"]['right'] : '';
        $style_scheme['form_margin_bottom'] = !empty($style_scheme_settings["form"]["margin"]['bottom']) ? $style_scheme_settings["form"]["margin"]['bottom'] : '';
        $style_scheme['form_margin_left'] = !empty($style_scheme_settings["form"]["margin"]['left']) ? $style_scheme_settings["form"]["margin"]['left'] : '';

        $style_scheme['form_border_width'] = !empty($style_scheme_settings["form"]["border"]["width"]) && !is_array($style_scheme_settings["form"]["border"]["width"]) ? $style_scheme_settings["form"]["border"]["width"] : '';
        $style_scheme['form_border_width_top'] = !empty($style_scheme_settings["form"]["border"]["width"]['top']) ? $style_scheme_settings["form"]["border"]["width"]['top'] : '';
        $style_scheme['form_border_width_right'] = !empty($style_scheme_settings["form"]["border"]["width"]['right']) ? $style_scheme_settings["form"]["border"]["width"]['right'] : '';
        $style_scheme['form_border_width_bottom'] = !empty($style_scheme_settings["form"]["border"]["width"]['bottom']) ? $style_scheme_settings["form"]["border"]["width"]['bottom'] : '';
        $style_scheme['form_border_width_left'] = !empty($style_scheme_settings["form"]["border"]["width"]['left']) ? $style_scheme_settings["form"]["border"]["width"]['left'] : '';

        $style_scheme['form_border_type'] = !empty($style_scheme_settings["form"]["border"]["type"]) ? $style_scheme_settings["form"]["border"]["type"] : '';
        $style_scheme['form_border_radius'] = !empty($style_scheme_settings["form"]["border"]["radius"]) ? $style_scheme_settings["form"]["border"]["radius"] : '';
        $style_scheme['form_border_color'] = !empty($style_scheme_settings["form"]["border"]["color"]) ? $style_scheme_settings["form"]["border"]["color"] : '';

        $style_scheme['form_border_shadow_horizontal_length'] = !empty($style_scheme_settings["form"]["shadow"]["horizontal-length"]) ? $style_scheme_settings["form"]["shadow"]["horizontal-length"] : '';
        $style_scheme['form_border_shadow_vertical_length'] = !empty($style_scheme_settings["form"]["shadow"]["vertical-length"]) ? $style_scheme_settings["form"]["shadow"]["vertical-length"] : '';
        $style_scheme['form_border_shadow_blur_radius'] = !empty($style_scheme_settings["form"]["shadow"]["blur-radius"]) ? $style_scheme_settings["form"]["shadow"]["blur-radius"] : '';
        $style_scheme['form_border_shadow_spread_radius'] = !empty($style_scheme_settings["form"]["shadow"]["spread-radius"]) ? $style_scheme_settings["form"]["shadow"]["spread-radius"] : '';
        $style_scheme['form_border_shadow_color'] = !empty($style_scheme_settings["form"]["shadow"]["color"]) ? $style_scheme_settings["form"]["shadow"]["color"] : '';
        $style_scheme['form_border_shadow_opacity'] = !empty($style_scheme_settings["form"]["shadow"]["opacity"]) ? $style_scheme_settings["form"]["shadow"]["opacity"] : '';
        $style_scheme['form_border_shadow_position'] = !empty($style_scheme_settings["form"]["shadow"]["position"]) ? $style_scheme_settings["form"]["shadow"]["position"] : '';


        $style_scheme['input_full_width'] = !empty($style_scheme_settings["input"]["full-width"]) ? $style_scheme_settings["input"]["full-width"] : '';
        $style_scheme['input_text_color'] = !empty($style_scheme_settings["input"]["text"]["color"]) ? $style_scheme_settings["input"]["text"]["color"] : '';
        $style_scheme['input_bg_color'] = !empty($style_scheme_settings["input"]["bg"]["color"]) ? $style_scheme_settings["input"]["bg"]["color"] : '';
        $style_scheme['input_bg_color_opacity'] = !empty($style_scheme_settings["input"]["bg"]["color-opacity"]) ? $style_scheme_settings["input"]["bg"]["color-opacity"] : '';

        $style_scheme['input_padding'] = !empty($style_scheme_settings["input"]["padding"]) && !is_array($style_scheme_settings["input"]["padding"]) ? $style_scheme_settings["input"]["padding"] : '';
        $style_scheme['input_padding_top'] = !empty($style_scheme_settings["input"]["padding"]['top']) ? $style_scheme_settings["input"]["padding"]['top'] : '';
        $style_scheme['input_padding_right'] = !empty($style_scheme_settings["input"]["padding"]['right']) ? $style_scheme_settings["input"]["padding"]['right'] : '';
        $style_scheme['input_padding_bottom'] = !empty($style_scheme_settings["input"]["padding"]['bottom']) ? $style_scheme_settings["input"]["padding"]['bottom'] : '';
        $style_scheme['input_padding_left'] = !empty($style_scheme_settings["input"]["padding"]['left']) ? $style_scheme_settings["input"]["padding"]['left'] : '';

        $style_scheme['input_margin'] = !empty($style_scheme_settings["input"]["margin"]) && !is_array($style_scheme_settings["input"]["margin"]) ? $style_scheme_settings["input"]["margin"] : '';
        $style_scheme['input_margin_top'] = !empty($style_scheme_settings["input"]["margin"]['top']) ? $style_scheme_settings["input"]["margin"]['top'] : '';
        $style_scheme['input_margin_right'] = !empty($style_scheme_settings["input"]["margin"]['right']) ? $style_scheme_settings["input"]["margin"]['right'] : '';
        $style_scheme['input_margin_bottom'] = !empty($style_scheme_settings["input"]["margin"]['bottom']) ? $style_scheme_settings["input"]["margin"]['bottom'] : '';
        $style_scheme['input_margin_left'] = !empty($style_scheme_settings["input"]["margin"]['left']) ? $style_scheme_settings["input"]["margin"]['left'] : '';

        $style_scheme['input_border_width'] = !empty($style_scheme_settings["input"]["border"]["width"]) && !is_array($style_scheme_settings["input"]["border"]["width"]) ? $style_scheme_settings["input"]["border"]["width"] : '';
        $style_scheme['input_border_width_top'] = !empty($style_scheme_settings["input"]["border"]["width"]['top']) ? $style_scheme_settings["input"]["border"]["width"]['top'] : '';
        $style_scheme['input_border_width_right'] = !empty($style_scheme_settings["input"]["border"]["width"]['right']) ? $style_scheme_settings["input"]["border"]["width"]['right'] : '';
        $style_scheme['input_border_width_bottom'] = !empty($style_scheme_settings["input"]["border"]["width"]['bottom']) ? $style_scheme_settings["input"]["border"]["width"]['bottom'] : '';
        $style_scheme['input_border_width_left'] = !empty($style_scheme_settings["input"]["border"]["width"]['left']) ? $style_scheme_settings["input"]["border"]["width"]['left'] : '';

        $style_scheme['input_text_size'] = !empty($style_scheme_settings["input"]["text"]["size"]) ? $style_scheme_settings["input"]["text"]["size"] : '';
        $style_scheme['input_text_line_height'] = !empty($style_scheme_settings["input"]["text"]["line-height"]) ? $style_scheme_settings["input"]["text"]["line-height"] : '';
        $style_scheme['input_border_radius'] = !empty($style_scheme_settings["input"]["border"]["radius"]) ? $style_scheme_settings["input"]["border"]["radius"] : '';
        $style_scheme['input_border_color'] = !empty($style_scheme_settings["input"]["border"]["color"]) ? $style_scheme_settings["input"]["border"]["color"] : '';

        $style_scheme['input_border_shadow_horizontal_length'] = !empty($style_scheme_settings["input"]["shadow"]["horizontal-length"]) ? $style_scheme_settings["input"]["shadow"]["horizontal-length"] : '';
        $style_scheme['input_border_shadow_vertical_length'] = !empty($style_scheme_settings["input"]["shadow"]["vertical-length"]) ? $style_scheme_settings["input"]["shadow"]["vertical-length"] : '';
        $style_scheme['input_border_shadow_blur_radius'] = !empty($style_scheme_settings["input"]["shadow"]["blur-radius"]) ? $style_scheme_settings["input"]["shadow"]["blur-radius"] : '';
        $style_scheme['input_border_shadow_spread_radius'] = !empty($style_scheme_settings["input"]["shadow"]["spread-radius"]) ? $style_scheme_settings["input"]["shadow"]["spread-radius"] : '';
        $style_scheme['input_border_shadow_color'] = !empty($style_scheme_settings["input"]["shadow"]["color"]) ? $style_scheme_settings["input"]["shadow"]["color"] : '';
        $style_scheme['input_border_shadow_opacity'] = !empty($style_scheme_settings["input"]["shadow"]["opacity"]) ? $style_scheme_settings["input"]["shadow"]["opacity"] : '';
        $style_scheme['input_border_shadow_position'] = !empty($style_scheme_settings["input"]["shadow"]["position"]) ? $style_scheme_settings["input"]["shadow"]["position"] : '';


        $style_scheme['checkbox_full_width'] = !empty($style_scheme_settings["checkbox"]["full-width"]) ? $style_scheme_settings["checkbox"]["full-width"] : '';
        $style_scheme['radiobutton_full_width'] = !empty($style_scheme_settings["radiobutton"]["full-width"]) ? $style_scheme_settings["radiobutton"]["full-width"] : '';
        $style_scheme['checkbox_text_label_size'] = !empty($style_scheme_settings["checkbox"]["text"]["label-size"]) ? $style_scheme_settings["checkbox"]["text"]["label-size"] : '';


        $style_scheme['button_full_width'] = !empty($style_scheme_settings["button"]["full-width"]) ? $style_scheme_settings["button"]["full-width"] : '';
        $style_scheme['button_text_color'] = !empty($style_scheme_settings["button"]["text"]["color"]) ? $style_scheme_settings["button"]["text"]["color"] : '';
        $style_scheme['button_bg_color'] = !empty($style_scheme_settings["button"]["bg"]["color"]) ? $style_scheme_settings["button"]["bg"]["color"] : '';
        $style_scheme['button_text_color_hover'] = !empty($style_scheme_settings["button"]["text"]["color-hover"]) ? $style_scheme_settings["button"]["text"]["color-hover"] : '';
        $style_scheme['button_bg_color_hover'] = !empty($style_scheme_settings["button"]["bg"]["color-hover"]) ? $style_scheme_settings["button"]["bg"]["color-hover"] : '';
        $style_scheme['button_text_size'] = !empty($style_scheme_settings["button"]["text"]["size"]) ? $style_scheme_settings["button"]["text"]["size"] : '';
        $style_scheme['button_text_line_height'] = !empty($style_scheme_settings["button"]["text"]["line-height"]) ? $style_scheme_settings["button"]["text"]["line-height"] : '';
        $style_scheme['button_padding'] = !empty($style_scheme_settings["button"]["padding"]) ? $style_scheme_settings["button"]["padding"] : '';
        $style_scheme['button_border_width'] = !empty($style_scheme_settings["button"]["border"]["width"]) ? $style_scheme_settings["button"]["border"]["width"] : '';
        $style_scheme['button_border_radius'] = !empty($style_scheme_settings["button"]["border"]["radius"]) ? $style_scheme_settings["button"]["border"]["radius"] : '';
        $style_scheme['button_border_color'] = !empty($style_scheme_settings["button"]["border"]["color"]) ? $style_scheme_settings["button"]["border"]["color"] : '';
        $style_scheme['button_border_color_hover'] = !empty($style_scheme_settings["button"]["border"]["color-hover"]) ? $style_scheme_settings["button"]["border"]["color-hover"] : '';

        $style_scheme['button_border_shadow_horizontal_length'] = !empty($style_scheme_settings["button"]["shadow"]["horizontal-length"]) ? $style_scheme_settings["button"]["shadow"]["horizontal-length"] : '';
        $style_scheme['button_border_shadow_vertical_length'] = !empty($style_scheme_settings["button"]["shadow"]["vertical-length"]) ? $style_scheme_settings["button"]["shadow"]["vertical-length"] : '';
        $style_scheme['button_border_shadow_blur_radius'] = !empty($style_scheme_settings["button"]["shadow"]["blur-radius"]) ? $style_scheme_settings["button"]["shadow"]["blur-radius"] : '';
        $style_scheme['button_border_shadow_spread_radius'] = !empty($style_scheme_settings["button"]["shadow"]["spread-radius"]) ? $style_scheme_settings["button"]["shadow"]["spread-radius"] : '';
        $style_scheme['button_border_shadow_color'] = !empty($style_scheme_settings["button"]["shadow"]["color"]) ? $style_scheme_settings["button"]["shadow"]["color"] : '';
        $style_scheme['button_border_shadow_opacity'] = !empty($style_scheme_settings["button"]["shadow"]["opacity"]) ? $style_scheme_settings["button"]["shadow"]["opacity"] : '';
        $style_scheme['button_border_shadow_position'] = !empty($style_scheme_settings["button"]["shadow"]["position"]) ? $style_scheme_settings["button"]["shadow"]["position"] : '';

        $style_scheme['custom_css'] = !empty($style_scheme_settings["custom"]["css"]) ? $style_scheme_settings["custom"]["css"] : '';

        return $style_scheme;
    }

    public static function get_inline_style_scheme($style_schemes, $slug, $form_id = array(), $form_excluded = array(), $form_prefix = '') {
        $style_scheme = self::normalize_style_sheme($style_schemes, $slug);
        $web_safe_fonts = Cf7_Style_Scheme::get_web_safe_fonts();


        $form_class = '';
//        if (!empty($form_id)) {
//            $form_class = '.cf7cstmzr-form-' . $form_id;
//        }

        $wrappers = array();

        if (!empty($form_excluded)) {
            foreach ($form_excluded as $form_excluded_id => $form_excluded_style) {
                $wrappers[] = $form_prefix . '#cf7cstmzr-form:not(.cf7cstmzr-form-'.$form_excluded_id.') ';
            }
        } elseif (!empty($form_id)) {
            foreach ($form_id as $item) {
                $wrappers[] = $form_prefix . '#cf7cstmzr-form.cf7cstmzr-form-'.$item.' ';
            }
        } else {
            $wrappers[] = $form_prefix . '#cf7cstmzr-form ';
        }

        ob_start();
        ?>
<style>
<?php
$selectors = array(
    '.wpcf7-form',
);

$selectors_wrapper = array();

foreach ($selectors as $selector) {
    foreach ($wrappers as $wrapper) {
        $selectors_wrapper[] = $wrapper . $selector;
    }
}
?>
<?php echo implode(',' . PHP_EOL, $selectors_wrapper) ?> {<?php
    if (!empty($style_scheme["form_text_color"])) echo PHP_EOL . "\t" . 'color: ' . $style_scheme["form_text_color"] . '!important;';
    if (!empty($style_scheme["form_bg_color"])) echo PHP_EOL . "\t" . 'background-color: ' . $style_scheme["form_bg_color"] . '!important;';
    if (!empty($style_scheme["form_text_size"])) echo PHP_EOL . "\t" . 'font-size: ' . $style_scheme["form_text_size"] . 'px!important;';
    if (!empty($style_scheme['form_text_font_family']) && !empty($web_safe_fonts[$style_scheme['form_text_font_family']][1])) echo PHP_EOL . "\t" . 'font-family: ' . $web_safe_fonts[$style_scheme['form_text_font_family']][1] . '!important;';

    if (!empty($style_scheme["form_padding_top"])) {
        echo PHP_EOL . "\t" . 'padding-top: ' . $style_scheme["form_padding_top"] . 'px!important;';
    }
    if (!empty($style_scheme["form_padding_right"])) {
        echo PHP_EOL . "\t" . 'padding-right: ' . $style_scheme["form_padding_right"] . 'px!important;';
    }
    if (!empty($style_scheme["form_padding_bottom"])) {
        echo PHP_EOL . "\t" . 'padding-bottom: ' . $style_scheme["form_padding_bottom"] . 'px!important;';
    }
    if (!empty($style_scheme["form_padding_left"])) {
        echo PHP_EOL . "\t" . 'padding-left: ' . $style_scheme["form_padding_left"] . 'px!important;';
    }

    if (!empty($style_scheme["form_margin_top"])) {
        echo PHP_EOL . "\t" . 'margin-top: ' . $style_scheme["form_margin_top"] . 'px!important;';
    }
    if (!empty($style_scheme["form_margin_right"])) {
        echo PHP_EOL . "\t" . 'margin-right: ' . $style_scheme["form_margin_right"] . 'px!important;';
    }
    if (!empty($style_scheme["form_margin_bottom"])) {
        echo PHP_EOL . "\t" . 'margin-bottom: ' . $style_scheme["form_margin_bottom"] . 'px!important;';
    }
    if (!empty($style_scheme["form_margin_left"])) {
        echo PHP_EOL . "\t" . 'margin-left: ' . $style_scheme["form_margin_left"] . 'px!important;';
    }

    if (!empty($style_scheme["form_border_radius"])) echo PHP_EOL . "\t" . 'border-radius: ' . $style_scheme["form_border_radius"] . 'px!important;';

    if (!empty($style_scheme['form_border_type'])) {
        $form_border_type = $style_scheme['form_border_type'];
    } else {
        $form_border_type = 'solid';
    }

    if (!empty($style_scheme["form_border_width_top"])) {
        echo PHP_EOL . "\t" . 'border-top-style: '.$form_border_type.'!important;';
        echo PHP_EOL . "\t" . 'border-top-width: ' . $style_scheme["form_border_width_top"] . 'px!important;';

        if (!empty($style_scheme["form_border_color"])) {
            echo PHP_EOL . "\t" . 'border-top-color: ' . $style_scheme["form_border_color"] . '!important;';
        } else {
            echo PHP_EOL . "\t" . 'border-top-color: transparent!important;';
        }
    } else {
        echo PHP_EOL . "\t" . 'border-top: none!important;';
    }

    if (!empty($style_scheme["form_border_width_right"])) {
        echo PHP_EOL . "\t" . 'border-right-style: '.$form_border_type.'!important;';
        echo PHP_EOL . "\t" . 'border-right-width: ' . $style_scheme["form_border_width_right"] . 'px!important;';

        if (!empty($style_scheme["form_border_color"])) {
            echo PHP_EOL . "\t" . 'border-right-color: ' . $style_scheme["form_border_color"] . '!important;';
        } else {
            echo PHP_EOL . "\t" . 'border-right-color: transparent!important;';
        }
    } else {
        echo PHP_EOL . "\t" . 'border-right: none!important;';
    }

    if (!empty($style_scheme["form_border_width_bottom"])) {
        echo PHP_EOL . "\t" . 'border-bottom-style: '.$form_border_type.'!important;';
        echo PHP_EOL . "\t" . 'border-bottom-width: ' . $style_scheme["form_border_width_bottom"] . 'px!important;';

        if (!empty($style_scheme["form_border_color"])) {
            echo PHP_EOL . "\t" . 'border-bottom-color: ' . $style_scheme["form_border_color"] . '!important;';
        } else {
            echo PHP_EOL . "\t" . 'border-bottom-color: transparent!important;';
        }
    } else {
        echo PHP_EOL . "\t" . 'border-bottom: none!important;';
    }

    if (!empty($style_scheme["form_border_width_left"])) {
        echo PHP_EOL . "\t" . 'border-left-style: '.$form_border_type.'!important;';
        echo PHP_EOL . "\t" . 'border-left-width: ' . $style_scheme["form_border_width_left"] . 'px!important;';

        if (!empty($style_scheme["form_border_color"])) {
            echo PHP_EOL . "\t" . 'border-left-color: ' . $style_scheme["form_border_color"] . '!important;';
        } else {
            echo PHP_EOL . "\t" . 'border-left-color: transparent!important;';
        }
    } else {
        echo PHP_EOL . "\t" . 'border-left: none!important;';
    }

    if ($style_scheme["form_bg_img"]) {
        $image_attributes = wp_get_attachment_image_src( $style_scheme['form_bg_img'], 'full' );

        if (!empty($image_attributes[0])) {
            echo PHP_EOL . "\t" . 'position: relative!important;';
        }
    }

    $form_border_shadow_horizontal_length = 0;
    $form_border_shadow_vertical_length = 0;
    $form_border_shadow_blur_radius = 0;
    $form_border_shadow_spread_radius = 0;
    $form_border_shadow_opacity = 0;
    $form_border_shadow_color = '#000000';
    $form_border_shadow_position = '';

    if (!empty($style_scheme['form_border_shadow_horizontal_length'])) $form_border_shadow_horizontal_length = $style_scheme['form_border_shadow_horizontal_length'];
    if (!empty($style_scheme['form_border_shadow_vertical_length'])) $form_border_shadow_vertical_length = $style_scheme['form_border_shadow_vertical_length'];
    if (!empty($style_scheme['form_border_shadow_blur_radius'])) $form_border_shadow_blur_radius = $style_scheme['form_border_shadow_blur_radius'];
    if (!empty($style_scheme['form_border_shadow_spread_radius'])) $form_border_shadow_spread_radius = $style_scheme['form_border_shadow_spread_radius'];
    if (!empty($style_scheme['form_border_shadow_opacity'])) $form_border_shadow_opacity = $style_scheme['form_border_shadow_opacity'];
    if (!empty($style_scheme['form_border_shadow_position']) && 'inset' === $style_scheme['form_border_shadow_position']) $form_border_shadow_position = 'inset ';
    if (!empty($style_scheme['form_border_shadow_color'])) {
        $form_border_shadow_color = $style_scheme['form_border_shadow_color'];
    } else {
        $form_border_shadow_opacity = 0;
    }

    list($r, $g, $b) = sscanf($form_border_shadow_color, "#%02x%02x%02x");
    $form_border_shadow_color = $r.','.$g.','.$b.','

    ?>

    -webkit-box-shadow: <?php echo $form_border_shadow_position; ?><?php echo $form_border_shadow_horizontal_length ?>px <?php echo $form_border_shadow_vertical_length ?>px <?php echo $form_border_shadow_blur_radius ?>px <?php echo $form_border_shadow_spread_radius ?>px rgba(<?php echo $form_border_shadow_color; ?><?php echo $form_border_shadow_opacity ?>);
    -moz-box-shadow: <?php echo $form_border_shadow_position; ?><?php echo $form_border_shadow_horizontal_length ?>px <?php echo $form_border_shadow_vertical_length ?>px <?php echo $form_border_shadow_blur_radius ?>px <?php echo $form_border_shadow_spread_radius ?>px rgba(<?php echo $form_border_shadow_color; ?><?php echo $form_border_shadow_opacity ?>);
    box-shadow: <?php echo $form_border_shadow_position; ?><?php echo $form_border_shadow_horizontal_length ?>px <?php echo $form_border_shadow_vertical_length ?>px <?php echo $form_border_shadow_blur_radius ?>px <?php echo $form_border_shadow_spread_radius ?>px rgba(<?php echo $form_border_shadow_color; ?><?php echo $form_border_shadow_opacity ?>);
}
<?php
$selectors = array(
    '.wpcf7-form *',
);

$selectors_wrapper = array();

foreach ($selectors as $selector) {
    foreach ($wrappers as $wrapper) {
        $selectors_wrapper[] = $wrapper . $selector;
    }
}
?>
<?php echo implode(',' . PHP_EOL, $selectors_wrapper) ?> {<?php
    if (!empty($style_scheme['form_text_font_family']) && !empty($web_safe_fonts[$style_scheme['form_text_font_family']][1])) echo PHP_EOL . "\t" . 'font-family: ' . $web_safe_fonts[$style_scheme['form_text_font_family']][1] . '!important;';
?>}
<?php

/**
 * Background image styles
 */
$selectors = array(
    '.wpcf7-form:before',
);

$selectors_wrapper = array();

foreach ($selectors as $selector) {
    foreach ($wrappers as $wrapper) {
        $selectors_wrapper[] = $wrapper . $selector;
    }
}
?>
<?php echo implode(',' . PHP_EOL, $selectors_wrapper)?> {<?php
    if ($style_scheme["form_bg_img"]) {
        $image_attributes = wp_get_attachment_image_src( $style_scheme['form_bg_img'], 'full' );

        if (!empty($image_attributes[0])) {
            echo PHP_EOL . "\t" . "display: block;";
            echo PHP_EOL . "\t" . "content: ' ';";
            echo PHP_EOL . "\t" . "position: absolute!important;";
            echo PHP_EOL . "\t" . "top: 0!important;";
            echo PHP_EOL . "\t" . "bottom: 0!important;";
            echo PHP_EOL . "\t" . "left: 0!important;";
            echo PHP_EOL . "\t" . "right: 0!important;";
            echo PHP_EOL . "\t" . 'background-image: url("'.$image_attributes[0].'")!important;';

            if (!empty($style_scheme['form_bg_img_size'])) {
                if ('cover' === $style_scheme['form_bg_img_size']) {
                    echo PHP_EOL . "\t" . 'background-size: cover!important;';
                    echo PHP_EOL . "\t" . 'background-repeat: no-repeat!important;';
                } elseif ('contain' === $style_scheme['form_bg_img_size']) {
                    echo PHP_EOL . "\t" . 'background-size: contain!important;';
                    echo PHP_EOL . "\t" . 'background-repeat: no-repeat!important;';
                } elseif ('repeat-y' === $style_scheme['form_bg_img_size']) {
                    echo PHP_EOL . "\t" . 'background-size: initial!important;';
                    echo PHP_EOL . "\t" . 'background-repeat: repeat-y!important;';
                } elseif ('repeat-x' === $style_scheme['form_bg_img_size']) {
                    echo PHP_EOL . "\t" . 'background-size: initial!important;';
                    echo PHP_EOL . "\t" . 'background-repeat: repeat-x!important;';
                } elseif ('repeat' === $style_scheme['form_bg_img_size']) {
                    echo PHP_EOL . "\t" . 'background-size: initial!important;';
                    echo PHP_EOL . "\t" . 'background-repeat: repeat!important;';
                } else {
                    echo PHP_EOL . "\t" . 'background-size: initial!important;';
                    echo PHP_EOL . "\t" . 'background-repeat: no-repeat!important;';
                }
            } else {
                echo PHP_EOL . "\t" . 'background-size: initial!important;';
                echo PHP_EOL . "\t" . 'background-repeat: no-repeat!important;';
            }

            if (!empty($style_scheme['form_bg_img_position'])) {
                if ('top' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: center top!important;';
                } elseif ('right' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: right center!important;';
                } elseif ('bottom' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: center bottom!important;';
                } elseif ('left' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: left center!important;';
                } elseif ('top-right' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: right top!important;';
                } elseif ('right-bottom' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: right bottom!important;';
                } elseif ('bottom-left' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: left bottom!important;';
                } elseif ('left-top' === $style_scheme['form_bg_img_position']) {
                    echo PHP_EOL . "\t" . 'background-position: left top!important;';
                } else {
                    echo PHP_EOL . "\t" . 'background-position: center center!important;';
                }
            } else {
                echo PHP_EOL . "\t" . 'background-position: center center!important;';
            }

            if (empty($style_scheme["form_bg_img_opacity"])) {
                echo PHP_EOL . "\t" . 'opacity: 0!important;';
            } else {
                echo PHP_EOL . "\t" . 'opacity: '.$style_scheme["form_bg_img_opacity"].'!important;';
            }
        }
    } else {
        echo PHP_EOL . "\t" . "display: none;";
        echo PHP_EOL . "\t" . "content: ' ';";
        echo PHP_EOL . "\t" . "position: absolute!important;";
        echo PHP_EOL . "\t" . "top: 0!important;";
        echo PHP_EOL . "\t" . "bottom: 0!important;";
        echo PHP_EOL . "\t" . "left: 0!important;";
        echo PHP_EOL . "\t" . "right: 0!important;";
        echo PHP_EOL . "\t" . "background-image: none!important;";
        echo PHP_EOL . "\t" . "opacity: 0!important;";
    }
?>}

<?php $selectors = array('.wpcf7-form > *'); ?>

<?php self::style_selectors($selectors, $wrappers) ?>{<?php
    if ($style_scheme["form_bg_img"]) {
        echo PHP_EOL . "\t" . 'position: relative!important;';
    } else {
        echo PHP_EOL . "\t" . 'position: static!important;';
    }
    ?>
}

/* Style Form Checkboxes Width */
<?php $selectors = array('.wpcf7-form .wpcf7-checkbox > span'); ?>

<?php self::style_selectors($selectors, $wrappers) ?>{<?php
    if (!empty($style_scheme["checkbox_full_width"]) && 'yes' === $style_scheme["checkbox_full_width"]) {
        echo PHP_EOL . "\t" . "display: block;";
        echo PHP_EOL . "\t" . "width: 100%;";
    } elseif (!empty($style_scheme["checkbox_full_width"]) && 'no' === $style_scheme["checkbox_full_width"]) {
        echo PHP_EOL . "\t" . "display: inline-block;";
        echo PHP_EOL . "\t" . "width: auto;";
    }
?>
}

/* Style Form Radiobuttons Width */
<?php $selectors = array('.wpcf7-form .wpcf7-radio > span'); ?>

<?php self::style_selectors($selectors, $wrappers) ?>{
    <?php
    if (!empty($style_scheme["radiobutton_full_width"]) && 'yes' === $style_scheme["radiobutton_full_width"]) {
        echo PHP_EOL . "\t" . "display: block;";
        echo PHP_EOL . "\t" . "width: 100%;";
    } elseif (!empty($style_scheme["radiobutton_full_width"]) && 'no' === $style_scheme["radiobutton_full_width"]) {
        echo PHP_EOL . "\t" . "display: inline-block;";
        echo PHP_EOL . "\t" . "width: auto;";
    }
    ?>
}

/* Style Form Checkboxes Labels */
<?php
$selectors = array(
    '.wpcf7-form .wpcf7-checkbox label',
    '.wpcf7-form .wpcf7-checkbox .wpcf7-list-item-label',
    '.wpcf7-form .wpcf7-radio label',
    '.wpcf7-form .wpcf7-radio .wpcf7-list-item-label',
);
?>
<?php self::style_selectors($selectors, $wrappers) ?>{
<?php
    if (!empty($style_scheme["checkbox_text_label_size"])) {
        echo PHP_EOL . "\t" . "font-size: " . $style_scheme["checkbox_text_label_size"] . "px!important;" ;
    }
?>
}

/* Style Form Labels */
<?php $selectors = array('.wpcf7-form label'); ?>
<?php self::style_selectors($selectors, $wrappers) ?>{
    <?php
    if (!empty($style_scheme["form_text_label_color"])) {
        echo PHP_EOL . "\t" . 'color: ' . $style_scheme["form_text_label_color"] . '!important;';
    } else {
        if (!empty($style_scheme["form_text_color"])) echo PHP_EOL . "\t" . 'color: ' . $style_scheme["form_text_color"] . '!important;';
    }
    if (!empty($style_scheme["form_text_label_size"])) echo PHP_EOL . "\t" . 'font-size: ' . $style_scheme["form_text_label_size"] . 'px!important;';
    if (!empty($style_scheme['form_text_label_weight'])) echo PHP_EOL . "\t" . 'font-weight: ' . $style_scheme['form_text_label_weight'] . '!important;';
    if (!empty($style_scheme['form_text_label_style'])) echo PHP_EOL . "\t" . 'font-style: ' . $style_scheme['form_text_label_style'] . '!important;';
    ?>
}
<?php $selectors = array('.wpcf7-form a'); ?>
<?php self::style_selectors($selectors, $wrappers) ?>{
    <?php
    if (!empty($style_scheme["form_text_link_color"])) {
        echo PHP_EOL . "\t" . 'color: ' . $style_scheme["form_text_link_color"] . '!important;';
    }
    ?>
}

<?php $selectors = array('.wpcf7-form a:hover'); ?>
<?php self::style_selectors($selectors, $wrappers) ?>{
    <?php
    if (!empty($style_scheme["form_text_link_hover_color"])) echo PHP_EOL . "\t" . 'color: ' . $style_scheme["form_text_link_hover_color"] . '!important;';
    ?>
}

<?php
$selectors = array(
    '.wpcf7-form input[type="text"]',
    '.wpcf7-form input[type="email"]',
    '.wpcf7-form input[type="number"]',
    '.wpcf7-form input[type="tel"]',
    '.wpcf7-form input[type="url"]',
    '.wpcf7-form input[type="password"]',
    '.wpcf7-form input[type="date"]',
    '.wpcf7-form input[type="range"]',
    '.wpcf7-form select',
    '.wpcf7-form textarea',
);
?>
<?php self::style_selectors($selectors, $wrappers) ?>{
    <?php
    if (!empty($style_scheme["input_full_width"]) && 'yes' === $style_scheme["input_full_width"]) {
        echo 'display: block!important;';
        echo 'width: 100%!important;';
        echo 'box-sizing: border-box!important;';
    } elseif (!empty($style_scheme["input_full_width"]) && 'no' === $style_scheme["input_full_width"]) {
        echo 'display: inline-block!important;';
        echo 'max-width: 100%!important;';
    }

    if (!empty($style_scheme['form_text_font_family']) && !empty($web_safe_fonts[$style_scheme['form_text_font_family']][1])) echo 'font-family: ' . $web_safe_fonts[$style_scheme['form_text_font_family']][1] . '!important;';

    if (!empty($style_scheme["input_text_color"])) echo 'color: ' . $style_scheme["input_text_color"] . '!important;';
    if (!empty($style_scheme["input_bg_color"])) {
        $input_bg_color_opacity = 1;
        $input_bg_color = $style_scheme["input_bg_color"];
        if (!empty($style_scheme['input_bg_color_opacity'])) $input_bg_color_opacity = $style_scheme['input_bg_color_opacity'];

        list($r, $g, $b) = sscanf($input_bg_color, "#%02x%02x%02x");
        $input_bg_color = $r.','.$g.','.$b.',';

        echo 'background-color: rgba('.$r.', '.$g.', '.$b.', '.$input_bg_color_opacity.')!important;';
    }

    if (!empty($style_scheme["input_text_size"])) echo 'font-size: ' . $style_scheme["input_text_size"] . 'px!important;';
    if (!empty($style_scheme["input_text_line_height"])) echo 'line-height: ' . $style_scheme["input_text_line_height"] . '!important;';

    if (!empty($style_scheme["input_padding_top"])) echo 'padding-top: ' . $style_scheme["input_padding_top"] . 'px!important;';
    if (!empty($style_scheme["input_padding_right"])) echo 'padding-right: ' . $style_scheme["input_padding_right"] . 'px!important;';
    if (!empty($style_scheme["input_padding_bottom"])) echo 'padding-bottom: ' . $style_scheme["input_padding_bottom"] . 'px!important;';
    if (!empty($style_scheme["input_padding_left"])) echo 'padding-left: ' . $style_scheme["input_padding_left"] . 'px!important;';

    if (!empty($style_scheme["input_margin_top"])) {
        echo 'margin-top: ' . $style_scheme["input_margin_top"] . 'px!important;';
    } else {
        echo 'margin-top: 0px!important;';
    }
    if (!empty($style_scheme["input_margin_right"])) {
        echo 'margin-right: ' . $style_scheme["input_margin_right"] . 'px!important;';
    } else {
        echo 'margin-right: 0px!important;';
    }
    if (!empty($style_scheme["input_margin_bottom"])) {
        echo 'margin-bottom: ' . $style_scheme["input_margin_bottom"] . 'px!important;';
    } else {
        echo 'margin-bottom: 0px!important;';
    }
    if (!empty($style_scheme["input_margin_left"])) {
        echo 'margin-left: ' . $style_scheme["input_margin_left"] . 'px!important;';
    } else {
        echo 'margin-left: 0px!important;';
    }

    if (!empty($style_scheme["input_border_radius"])) {
        echo 'border-radius: ' . $style_scheme["input_border_radius"] . 'px!important;';
    } else {
        echo 'border-radius: 0px!important;';
    }

    if (!empty($style_scheme["input_border_width_top"])) {
        echo 'border-top-style: solid!important;';
        echo 'border-top-width: ' . $style_scheme["input_border_width_top"] . 'px!important;';

        if (!empty($style_scheme["input_border_color"])) {
            echo 'border-top-color: ' . $style_scheme["input_border_color"] . '!important;';
        } else {
            echo 'border-top-color: transparent!important;';
        }
    } else {
        echo 'border-top: none;';
    }

    if (!empty($style_scheme["input_border_width_right"])) {
        echo 'border-right-style: solid!important;';
        echo 'border-right-width: ' . $style_scheme["input_border_width_right"] . 'px!important;';

        if (!empty($style_scheme["input_border_color"])) {
            echo 'border-right-color: ' . $style_scheme["input_border_color"] . '!important;';
        } else {
            echo 'border-right-color: transparent!important;';
        }
    } else {
        echo 'border-right: none;';
    }

    if (!empty($style_scheme["input_border_width_bottom"])) {
        echo 'border-bottom-style: solid!important;';
        echo 'border-bottom-width: ' . $style_scheme["input_border_width_bottom"] . 'px!important;';

        if (!empty($style_scheme["input_border_color"])) {
            echo 'border-bottom-color: ' . $style_scheme["input_border_color"] . '!important;';
        } else {
            echo 'border-bottom-color: transparent!important;';
        }
    } else {
        echo 'border-bottom: none;';
    }

    if (!empty($style_scheme["input_border_width_left"])) {
        echo 'border-left-style: solid!important;';
        echo 'border-left-width: ' . $style_scheme["input_border_width_left"] . 'px!important;';

        if (!empty($style_scheme["input_border_color"])) {
            echo 'border-left-color: ' . $style_scheme["input_border_color"] . '!important;';
        } else {
            echo 'border-left-color: transparent!important;';
        }
    } else {
        echo 'border-left: none;';
    }

    self::show_shadow_css_rules(
            $style_scheme['input_border_shadow_horizontal_length'],
            $style_scheme['input_border_shadow_vertical_length'],
            $style_scheme['input_border_shadow_blur_radius'],
            $style_scheme['input_border_shadow_spread_radius'],
            $style_scheme['input_border_shadow_opacity'],
            $style_scheme['input_border_shadow_color'],
            $style_scheme['input_border_shadow_position']
    );
    ?>

    outline: none!important;
}

<?php
$selectors = array(
    '.wpcf7-form input[type="text"]',
    '.wpcf7-form input[type="email"]',
    '.wpcf7-form input[type="number"]',
    '.wpcf7-form input[type="tel"]',
    '.wpcf7-form input[type="url"]',
    '.wpcf7-form input[type="password"]',
    '.wpcf7-form input[type="date"]',
    '.wpcf7-form select',
);
?>
<?php self::style_selectors($selectors, $wrappers) ?>{
    <?php
    echo PHP_EOL . "\t" . 'height: auto!important;';
    echo PHP_EOL . "\t" . 'overflow: auto!important;';
    echo PHP_EOL . "\t" . 'vertical-align: top!important;';
    ?>
}

<?php
$selectors = array(
    '.wpcf7-form input[type="reset"]',
    '.wpcf7-form input[type="button"]',
    '.wpcf7-form input[type="submit"]',
    '.wpcf7-form button',
);
?>
<?php self::style_selectors($selectors, $wrappers) ?>{
    position: relative!important;
    <?php
    if (!empty($style_scheme["button_text_color"])) echo 'color: ' . $style_scheme["button_text_color"] . '!important;';
    if (!empty($style_scheme["button_bg_color"])) echo 'background-color: ' . $style_scheme["button_bg_color"] . '!important;';
    if (!empty($style_scheme["button_text_size"])) echo 'font-size: ' . $style_scheme["button_text_size"] . 'px!important;';
    if (!empty($style_scheme["button_text_line_height"])) echo 'line-height: ' . $style_scheme["button_text_line_height"] . '!important;';
    if (!empty($style_scheme["button_padding"])) echo 'padding-top: ' . $style_scheme["button_padding"] . 'px!important;';
    if (!empty($style_scheme["button_padding"])) echo 'padding-bottom: ' . $style_scheme["button_padding"] . 'px!important;';

    if (!empty($style_scheme['form_text_font_family']) && !empty($web_safe_fonts[$style_scheme['form_text_font_family']][1])) echo 'font-family: ' . $web_safe_fonts[$style_scheme['form_text_font_family']][1] . '!important;';

    if (!empty($style_scheme["button_full_width"]) && 'yes' === $style_scheme["button_full_width"]) {
        echo 'display: block!important;';
        echo 'width: 100%!important;';
    } elseif (!empty($style_scheme["button_full_width"]) && 'no' === $style_scheme["button_full_width"]) {
        echo 'display: inline-block!important;';
        echo 'max-width: 100%!important;';
    }

    echo 'border-style: solid;';
    if (!empty($style_scheme["button_border_width"])) {
        echo 'border-width: ' . $style_scheme["button_border_width"] . 'px!important;';
    } else {
        echo 'border: none!important;';
    }
    if (!empty($style_scheme["button_border_color"])) echo 'border-color: ' . $style_scheme["button_border_color"] . '!important;';
    if (!empty($style_scheme["button_border_radius"])) {
        echo 'border-radius: ' . $style_scheme["button_border_radius"] . 'px!important;';
    } else {
        echo 'border-radius: 0px!important;';
    }

    self::show_shadow_css_rules(
            $style_scheme['button_border_shadow_horizontal_length'],
            $style_scheme['button_border_shadow_vertical_length'],
            $style_scheme['button_border_shadow_blur_radius'],
            $style_scheme['button_border_shadow_spread_radius'],
            $style_scheme['button_border_shadow_opacity'],
            $style_scheme['button_border_shadow_color'],
            $style_scheme['button_border_shadow_position']
    );
    ?>
}

<?php
$selectors = array(
    '.wpcf7-form input[type="reset"]:hover',
    '.wpcf7-form input[type="button"]:hover',
    '.wpcf7-form input[type="submit"]:hover',
    '.wpcf7-form button:hover',
);
?>
<?php self::style_selectors($selectors, $wrappers) ?> {
    <?php
    if (!empty($style_scheme["button_text_color_hover"])) echo 'color: ' . $style_scheme["button_text_color_hover"] . '!important;';
    if (!empty($style_scheme["button_bg_color_hover"])) echo 'background-color: ' . $style_scheme["button_bg_color_hover"] . '!important;';
    if (!empty($style_scheme["button_border_color_hover"])) echo 'border-color: ' . $style_scheme["button_border_color_hover"] . '!important;';

    self::show_shadow_css_rules(
            $style_scheme['button_border_shadow_horizontal_length'],
            $style_scheme['button_border_shadow_vertical_length'],
            $style_scheme['button_border_shadow_blur_radius'],
            $style_scheme['button_border_shadow_spread_radius'],
            $style_scheme['button_border_shadow_opacity'],
            $style_scheme['button_border_shadow_color'],
            $style_scheme['button_border_shadow_position']
    );
    ?>
}

    <?php
    if (!empty($style_scheme['custom_css'])) {
        $custom_css = explode('}', $style_scheme['custom_css']);

        if (count($custom_css) > 0) {
            foreach ($custom_css as $custom_style) {
                $custom_style = trim(stripslashes ( $custom_style ));
                if (!empty($custom_style)) {
                    $selectors = array(
                        '.wpcf7-form',
                    );
                    $custom_style = trim($custom_style);
                    ?>
<?php self::style_selectors($selectors, $wrappers) ?> <?php echo $custom_style; ?>}
                    <?php

                }
            }
        }

    }
    ?>
</style>
        <?php
        $style = ob_get_clean();

        $style = str_replace('<style>', '', $style);
        $style = str_replace('</style>', '', $style);

        return $style;

    }

    public static function form_preview($id) {
        ?>
        <div id="form-preview-container">
            <iframe id="formPreviewFrame"
                    title="Inline Frame Example"
                    width="300"
                    height="600"
                    style="width:100%"
                    src="http://cf7-customizer.loc/cf7cstmzr-form-customizer/<?php echo $id; ?>">
            </iframe>
        <div id="form-preview-container">
        <?php
    }

    public static function get_form_preview($id) {
        ob_start();

        self::form_preview($id);

        return ob_get_clean();
    }

    public static function get_forms_with_style_schemes() {
        $cf7_scheme_args = array (
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_type'   => 'wpcf7_contact_form',
            'post_status'   => 'publish',
            'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
            'meta_query' => array(
                'relation' => 'EXISTS',
                array(
                    'key' => 'cf7cstmzr_style_scheme',
                )
            )
        );

        return get_posts($cf7_scheme_args);
    }

    public static function get_forms_group_by_style_scheme() {
        $style_schemes = get_option('cf7cstmzr_style_schemes', array());
        $forms_group_by_style_scheme = array();
        $forms_with_style_schemes = self::get_forms_with_style_schemes();

        if (!empty($forms_with_style_schemes)) {
            foreach ($forms_with_style_schemes as $form) {
                $cf7cstmzr_style_scheme = get_post_meta( $form->ID, 'cf7cstmzr_style_scheme', true );

                if (empty($style_schemes[$cf7cstmzr_style_scheme])) {
                    delete_post_meta( $form->ID, 'cf7cstmzr_style_scheme' );
                } else {
                    if (!empty($cf7cstmzr_style_scheme)) {
                        $forms_group_by_style_scheme[$cf7cstmzr_style_scheme][] = $form->ID;
                    }
                }
            }
        }

        return $forms_group_by_style_scheme;
    }

    public static function get_web_safe_fonts() {
        return array(
            'georgia' => array (
                'Georgia',
                'Georgia, serif'
            ),
            'palatino' => array (
                'Palatino Linotype',
                '"Palatino Linotype", "Book Antiqua", Palatino, serif'
            ),
            'times' => array (
                'Times New Roman',
                '"Times New Roman", Times, serif'
            ),
            'arial' => array (
                'Arial',
                'Arial, Helvetica, sans-serif'
            ),
            'tahoma' => array (
                'Tahoma',
                'Tahoma, Geneva, sans-serif'
            ),
            'verdana' => array (
                'Verdana',
                'Verdana, Geneva, sans-serif'
            ),
        );
    }

    public static function show_shadow_css_rules($horizontal_length, $vertical_length, $blur_radius, $spread_radius, $opacity, $color, $position) {
        $default_horizontal_length = 0;
        $default_vertical_length = 0;
        $default_blur_radius = 0;
        $default_spread_radius = 0;
        $default_opacity = 0;
        $default_color = '#000000';
        $default_position = '';

        if (!empty($horizontal_length)) $default_horizontal_length = $horizontal_length;
        if (!empty($vertical_length)) $default_vertical_length = $vertical_length;
        if (!empty($blur_radius)) $default_blur_radius = $blur_radius;
        if (!empty($spread_radius)) $default_spread_radius = $spread_radius;
        if (!empty($opacity)) $default_opacity = $opacity;
        if (!empty($position) && 'inset' === $position) $default_position = 'inset ';
        if (!empty($color)) {
            $default_color = $color;
        } else {
            $default_opacity = 0;
        }

        list($r, $g, $b) = sscanf($default_color, "#%02x%02x%02x");
        $default_color = $r.','.$g.','.$b.',';

        $rules = array (
            '-webkit-box-shadow',
            '-moz-box-shadow',
            'box-shadow',
        );

        foreach ($rules as $rule) {
            ?>
            <?php echo $rule ?>: <?php echo $default_position; ?><?php echo $default_horizontal_length ?>px <?php echo $default_vertical_length ?>px <?php echo $default_blur_radius ?>px <?php echo $default_spread_radius ?>px rgba(<?php echo $default_color; ?><?php echo $default_opacity ?>);
            <?php
        }
    }

    public static function get_individually_styled_forms() {
        $plugin_version = Cf7_License::get_license_version();
        $forms = array();

        if ('free' !== $plugin_version) {
            $args = array (
                'numberposts' => -1,
                'orderby'     => 'title',
                'order'       => 'ASC',
                'post_type'   => 'wpcf7_contact_form',
                'post_status'   => 'publish',
                'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
                'meta_query' => array(
                    'relation' => 'EXISTS',
                    array(
                        'key' => 'cf7cstmzr_style_scheme',
                    )
                )
            );

            $cf7_forms = get_posts($args);

            if (!empty($cf7_forms)) {
                foreach ($cf7_forms as $cf_7_form) {
                    $cf7cstmzr_style_scheme = get_post_meta( $cf_7_form->ID, 'cf7cstmzr_style_scheme', true );

                    $forms[$cf_7_form->ID] = $cf7cstmzr_style_scheme;
                }
            }
        } else {
            $args = array (
                'numberposts' => -1,
                'orderby'     => 'title',
                'order'       => 'ASC',
                'post_type'   => 'wpcf7_contact_form',
                'post_status'   => 'publish',
                'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
                'meta_query' => array(
                    'relation' => 'EXISTS',
                    array(
                        'key' => 'cf7cstmzr_style_scheme',
                    )
                )
            );

            $cf7_forms = get_posts($args);

            if (!empty($cf7_forms)) {
                $i = 1;
                $styled_scheme = false;

                foreach ($cf7_forms as $cf_7_form) {
                    if (!$styled_scheme) {
                        $cf7cstmzr_style_scheme = get_post_meta( $cf_7_form->ID, 'cf7cstmzr_style_scheme', true );

                        if ('default' !== $cf7cstmzr_style_scheme) {
                            delete_post_meta( $cf_7_form->ID, 'cf7cstmzr_style_scheme' );
                        } else {
                            $forms[$cf_7_form->ID] = $cf7cstmzr_style_scheme;
                            $styled_scheme = true;
                        }
                    } else {
                        delete_post_meta( $cf_7_form->ID, 'cf7cstmzr_style_scheme' );
                    }
                }
            }
        }

        return $forms;
    }

    public static function get_globally_styled_forms() {
        $forms = array();

        $args = array (
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_type'   => 'wpcf7_contact_form',
            'post_status'   => 'publish',
            'suppress_filters' => false, // подавление работы фильтров изменения SQL запроса
        );

        $cf7_forms = get_posts($args);

        if (!empty($cf7_forms)) {
            foreach ($cf7_forms as $cf_7_form) {
                $forms[$cf_7_form->ID] = 'default';
            }
        }

        if (!empty($forms)) {
            $individual_forms = self::get_individually_styled_forms();

            if (!empty($individual_forms)) {
                foreach ($individual_forms as $individual_form_id => $individual_form_style) {
                    unset($forms[$individual_form_id]);
                }
            }
        }

        return $forms;
    }

    public static function style_selectors($selectors, $wrappers) {
        $selectors_wrapper = array();

        foreach ($selectors as $selector) {
            foreach ($wrappers as $wrapper) {
                $selectors_wrapper[] = $wrapper . $selector;
            }
        }

        echo implode( ',' . PHP_EOL, $selectors_wrapper );
    }
}