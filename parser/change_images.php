<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
    </head>
    <body>

        <?php

        function imageresize($outfile, $infile, $neww, $newh = 0, $quality = 75) {
            $ext = end(explode(".", $infile));
            $ext = strtolower($ext);
            if ($ext == 'png') {
                $im = imagecreatefrompng($infile);
            } elseif ($ext == 'gif') {
                $im = imagecreatefromgif($infile);
            } else {
                $im = imagecreatefromjpeg($infile);
            }
            if (!$newh) {
                $newh = (int) (($neww / imagesx($im)) * imagesy($im));
            }
            $im1 = imagecreatetruecolor($neww, $newh);
            imagecopyresampled($im1, $im, 0, 0, 0, 0, $neww, $newh, imagesx($im), imagesy($im));

            if ($ext == 'png') {
                imagepng($im1, $outfile);
            } elseif ($ext == 'gif') {
                imagegif($im1, $outfile);
            } else {
                imagejpeg($im1, $outfile, $quality);
            }

            imagedestroy($im);
            imagedestroy($im1);
        }

        function addProduct() {
            global $db;

            $image_names = $db->fetchCol("SELECT image_name FROM wle_jshopping_products_images");

            //images
            for ($i = 4000; $i < 5000; $i++) {
                $file_name = $image_names[$i];
                imageresize(dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . 'thumb_' . $file_name, dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . 'full_' . $file_name, 182);
            }
        }

        require_once(dirname(__FILE__) . '/mysql.class.php');
        require_once(dirname(__FILE__) . '/curl.class.php');
        require_once(dirname(__FILE__) . '/../configuration.php');
        $jconf = new jConfig();
        $db = new mysqldb($jconf->db, $jconf->host, $jconf->user, $jconf->password, $jconf->dbprefix);
        $res = $db->query("SET NAMES utf8");
        
        addProduct();
        ?>

    </body>
</html>
