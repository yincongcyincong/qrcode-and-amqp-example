<?php
    include './phpqrcode.php'; 
    ob_start();
    QRcode::png('http://www.sina.com', false, 6);
    $pngContent = ob_get_clean();
    $name = 'qr';
    header("Content-type: image/png");
    file_put_contents('php://output',$pngContent);