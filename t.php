<?php


$array = ['a' => '', 'b' => '', 'c' => ''];


$new = ['a' => 1, 'b' => 2, 'c' => ''];

var_dump(array_diff_assoc($new, $array));
