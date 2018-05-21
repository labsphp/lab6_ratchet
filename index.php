<?php

$memcache = new Memcache;
$memcache->addServer('localhost', 11211);

$rand = random_int(127, 140);
echo "rand = " . $rand . "\n";

//Считываем сонеты с файла в массив
$sonnets = [];
$file = fopen('sonnets.txt', 'r');
$sonnet = '';
$num = 0;
while (!feof($file)) {
    $str = fgets($file);
    if (preg_match('#(?<num>\d+)#', $str, $matches)) {
        $num = $matches['num'];
        $sonnets[$num] = '';
    } elseif (!preg_match('#^\r\n$#', $str)) {
        $sonnet .= $str;
    } else {
        $sonnets[$num] = $sonnet;
        $sonnet = '';
    }
}
$memcache->set('sonnets', $sonnets);
echo '<pre>';
print_r($memcache->get('sonnets'));
echo '<pre>';
//echo $sonnets[$rand];

