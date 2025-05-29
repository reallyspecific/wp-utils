<?php

namespace ReallySpecific\SamplePlugin\Utils\Text;

function array_to_attr_string($attributes = [])
{
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if (is_null($value)) {
            continue;
        }
        if ($value === \true) {
            $value = 'true';
        }
        if ($value === \false) {
            $value = 'false';
        }
        $attr_string .= ' ' . $key . '="' . esc_attr($value) . '"';
    }
    return trim($attr_string);
}
