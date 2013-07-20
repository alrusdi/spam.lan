<?php

    function smarty_modifier_format_date($string)
    {
        if (!$string) {return '';}
        $string = explode(' ', trim($string));
        return (!empty($string[0])) ? $string[0] : '';
    } 

?>
