<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
    </head>
    <body>

        <?php
        /*
         * To change this license header, choose License Headers in Project Properties.
         * To change this template file, choose Tools | Templates
         * and open the template in the editor.
         */

        function getProductInfo($url) {
            $page = file_get_contents('http://dev1.wwwlab.biz/proxy.php?url=' . $url);

            //category
            preg_match_all('/<div[^>]*class="[^"]*bs-item[^"]*"[^>]*>.*<\/div>/sUi', $page, $matches);
            $elem = $matches[0][sizeof($matches[0]) - 3];
            preg_match_all('/<a[^>]*>(.*)<\/a>/sUi', $elem, $matches);
            $product->category = trim($matches[1][0]);


            //price 
            preg_match_all('/<p[^>]*itemprop="[^"]*price[^"]*"[^>]*>(.*)<\/p>/sUi', $page, $matches);
            $result = trim($matches[1][0]);
            $result = preg_replace('/[^0-9]/sUi', '', $result);
            $product->price = $result;

            //name
            preg_match_all('/<h1[^>]*itemprop="[^"]*name[^"]*"[^>]*>(.*)<\/h1>/sUi', $page, $matches);
            $result = trim($matches[1][0]);
            $product->name = $result;

            //description
            preg_match_all('/<div[^>]*itemprop="[^"]*description[^"]*"[^>]*>(.*)<\/div>/sUi', $page, $matches);
            $result = trim($matches[1][0]);
            $result = preg_replace('/<span[^>]*>.*<\/span>[^<]*(<h2)/sUi', '\1', $result);
            $result = strip_tags($result, '<p><span><li><ul><ol><img><table><tr><td><th>');
            $product->description = $result;

            //brand
            preg_match_all('/<span[^>]*class="[^"]*b-form-checkbox__label-text[^"]*"[^>]*>(.*)<\/span>/sUi', $page, $matches);
            $result = trim($matches[1][0]);
            $product->brand = $result;

            //params
            preg_match_all('/<table[^>]*class="[^"]*b-product-info[^"]*"[^>]*>(.*)<\/table>/sUi', $page, $matches);
            $result = trim($matches[1][0]);
            preg_match_all('/<tr[^>]*>(.*)<\/tr>/sUi', $result, $matches);
            $trs = $matches[1];
            for ($i = 0; $i < sizeof($trs); $i++) {
                if (strstr($trs[$i], 'b-product-info__header') !== false) {
                    if (is_array($group)) {
                        $params[] = $group;
                    }
                    $group = array();
                    preg_match_all('/<th[^>]*>(.*)<\/th>/sUi', $trs[$i], $matches);
                    $group['name'] = trim($matches[1][0]);
                } else {
                    preg_match_all('/<td[^>]*>(.*)<\/td>/sUi', $trs[$i], $matches);
                    $group['params'][] = array('name' => trim(strip_tags(str_replace('&nbsp;', '', $matches[1][0]))), 'value' => str_replace("\n", "", trim($matches[1][1])));
                }
            }
            $params[] = $group;
            $product->params = $params;

            //images
            preg_match_all('/<a[^>]*class="[^"]*b-centered-image[^"]*"[^>]*href="([^"]*)"[^>]*>.*<\/a>/sUi', $page, $matches);
            $result = $matches[1];
            $product->images = $result;

            return $product;
        }

        function getProductsLinks($url) { 
            $page = file_get_contents($url);
            preg_match_all('/<a[^>]*class="[^"]*b-pager__link_pos_last[^"]*"[^>]*href="([^"]*)"[^>]*>/sUi', $page, $matches);
            $next_url = "http://dev1.wwwlab.biz/proxy.php?url=http://pechiikamini.ru{$matches[1][0]}";
            if ($next_url != 'http://dev1.wwwlab.biz/proxy.php?url=http://pechiikamini.ru') {
                echo "<a href='?url={$next_url}'>Следующая страница</a>";
            } else {
                echo "<form action='' method='get'><input type='text' name='url'/><input type='submit' value='go'/></form>";
            }


            //category
            preg_match_all('/<a[^>]*href="([^"]*)"[^>]*class="[^"]*b-product-line__product-name-link[^"]*"[^>]*>.*<\/a>/sUi', $page, $matches);
            $links = $matches[1];
            $names = $matches[0];
            foreach ($links as $key => $link) {
                echo "Обработка " . strip_tags($names[$key]) . "<br/>\n";
                $product = getProductInfo($link);
                addProduct($product);
//                break;
            }
        }

        function imageresize($outfile, $infile, $neww, $newh = 0, $quality = 75) {
            $ext = end(explode(".", $infile));
            if ($ext == 'gif') {
                $im = imagecreatefromgif($infile);
            } else {
                $im = imagecreatefromjpeg($infile);
            }
            if (!$newh) {
                $newh = (int) (($neww / imagesx($im)) * imagesy($im));
            }
            $im1 = imagecreatetruecolor($neww, $newh);
            imagecopyresampled($im1, $im, 0, 0, 0, 0, $neww, $newh, imagesx($im), imagesy($im));

            imagejpeg($im1, $outfile, $quality);
            imagedestroy($im);
            imagedestroy($im1);
        }

        function addProduct($product) {
            global $db;


            //category
            $sql = "SELECT category_id FROM wle_jshopping_categories WHERE `name_ru-RU`='{$product->category}'";
            $category_id = $db->fetchOne($sql);
            if (!$category_id) {
                $insert = array(
                    'category_publish' => 1,
                    'category_ordertype' => 1,
                    'ordering' => 1,
                    'category_add_date' => date('Y-m-d H:i:s'),
                    'products_page' => 12,
                    'products_row' => 3,
                    'access' => 1,
                    'name_ru-RU' => $product->category
                );
                $category_id = $db->insert('wle_jshopping_categories', $insert);
            }
            $product->category_id = $category_id;

            //brand
            $sql = "SELECT manufacturer_id FROM wle_jshopping_manufacturers WHERE `name_ru-RU`='{$product->brand}'";
            $manufacturer_id = $db->fetchOne($sql);
            if (!$manufacturer_id) {
                $insert = array(
                    'manufacturer_publish' => 1,
                    'ordering' => 1,
                    'products_page' => 12,
                    'products_row' => 3,
                    'name_ru-RU' => $product->brand
                );
                $manufacturer_id = $db->insert('wle_jshopping_manufacturers', $insert);
            }

            //product
            $sql = "SELECT product_id FROM wle_jshopping_products WHERE `name_ru-RU`='{$product->name}'";
            $product_id = $db->fetchOne($sql);
            if (!$product_id) {
                $insert = array(
                    'parent_id' => 0,
                    'product_quantity' => 1,
                    'product_date_added' => date("Y-m-d H:i:s"),
                    'product_publish' => 1,
                    'product_tax_id' => 1,
                    'currency_id' => 2,
                    'product_template' => 'default',
                    'product_old_price' => 0,
                    'product_buy_price' => 0,
                    'product_price' => $product->price,
                    'min_price' => $product->price,
                    'different_prices' => 0,
                    'product_weight' => 0,
                    'image' => '',
                    'product_manufacturer_id' => $manufacturer_id,
                    'product_is_add_price' => 0,
                    'add_price_unit_id' => 1,
                    'average_rating' => 0,
                    'reviews_count' => 0,
                    'delivery_times_id' => 0,
                    'hits' => 0,
                    'weight_volume_units' => 0,
                    'basic_price_unit_id' => 0,
                    'label_id' => 0,
                    'vendor_id' => 0,
                    'access' => 1,
                    'name_ru-RU' => $product->name,
                    'description_ru-RU' => $product->description
                );
                $product_id = $db->insert('wle_jshopping_products', $insert);
            }
            $product->id = $product_id;

            if ($product_id) {

                //products_to_categories
                $insert = array(
                    'product_id' => $product_id,
                    'category_id' => $category_id,
                    'product_ordering' => 1
                );
                $db->insert('wle_jshopping_products_to_categories', $insert);

                //images
                foreach ($product->images as $key => $image) {
                    $file_name = end(explode("/", $image));
                    file_put_contents(dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . 'full_' . $file_name, file_get_contents($image));
                    imageresize(dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . $file_name, dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . 'full_' . $file_name, 200);
                    imageresize(dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . 'thumb_' . $file_name, dirname(__FILE__) . '/../components/com_jshopping/files/img_products/' . 'full_' . $file_name, 100);
                    $insert = array( 
                        'product_id' => $product_id,
                        'image_name' => $file_name,
                        'ordering' => 1
                    );
                    $db->insert('wle_jshopping_products_images', $insert);
                    if (!$key) {
                        $db->update('wle_jshopping_products', array('image' => $file_name), 'product_id=' . $product_id);
                    }
                }

                //params
                addParams($product, $product->params);
            }
        }

        function addParams($product, $groups) {
            global $db;
            $product_id = $product->id;

//            var_dump($groups);

            foreach ($groups as $group) {
                //param_group
                $sql = "SELECT id FROM wle_jshopping_products_extra_field_groups WHERE `name_ru-RU`='{$group['name']}'";
                $group_id = $db->fetchOne($sql);
                if (!$group_id) {
                    $insert = array(
                        'ordering' => 1,
                        'name_ru-RU' => $group['name']
                    );
                    $group_id = $db->insert('wle_jshopping_products_extra_field_groups', $insert);
                }

                foreach ($group['params'] as $param) {
                    $sql = "SELECT id FROM wle_jshopping_products_extra_fields WHERE `name_ru-RU` LIKE '{$param['name']}%'";
                    $param_id = $db->fetchOne($sql);
                    if (!$param_id) {
                        $cats = array($product->category_id);
                        $cats = serialize($cats);
                        $insert = array(
                            'allcats' => 0,
                            'cats' => $cats,
                            'type' => 0,
                            'multilist' => 0,
                            'group' => $group_id,
                            'ordering' => 1,
                            'name_ru-RU' => $param['name']
                        );
                        $param_id = $db->insert('wle_jshopping_products_extra_fields', $insert);
                        $sql = "ALTER TABLE `wle_jshopping_products` ADD `extra_field_{$param_id}` VARCHAR(100) NOT NULL;";
                        $db->query($sql);
                    } else {
                        $sql = "SELECT cats FROM wle_jshopping_products_extra_fields WHERE `name_ru-RU` LIKE '{$param['name']}%'";
                        $cats = $db->fetchOne($sql);
                        $cats = unserialize($cats);
                        if (!in_array($product->category_id, $cats)) {
                            $cats[] = $product->category_id;
                        }
                        $cats = serialize($cats);
                        $db->update('wle_jshopping_products_extra_fields', array('cats' => $cats), 'id=' . $param_id);
                    }


                    $value_id = null;
                    if (preg_match('/[0-9]+/sUi', $param['value'], $matches)) {
//                            $additional = '';
                        preg_match_all('/[0-9\.]+(.*)$/si', $param['value'], $matches);
//                            echo 'с цифры';
//                            echo trim((float) $param['value']);
                        $additional = trim($matches[1][0]);
//                            $db->update('wle_jshopping_products_extra_fields', )
//                            echo "<br/>\n";
                        $param_value = trim((float) $param['value']) . ' ' . $additional;
                        $db->update('wle_jshopping_products_extra_fields', array('type' => 1), 'id=' . $param_id);
                        $db->update('wle_jshopping_products', array('extra_field_' . $param_id => $param_value), 'product_id=' . $product_id);
                    } elseif (preg_match('/<span/sUi', $param['value'], $matches)) {
                        $new_values = array();
                        preg_match_all('/<span[^>]*>([^<]*)<\/span>/sUi', $param['value'], $matches);
                        foreach ($matches[1] as $match) {
                            $param_value = trim(str_replace(array(',', '&nbsp;'), '', $match));
//                                var_dump($param_value);
                            $sql = "SELECT id FROM wle_jshopping_products_extra_field_values WHERE `name_ru-RU`='{$param_value}' AND field_id={$param_id}";
                            $value_id = $db->fetchOne($sql);
                            if (!$value_id) {
                                $insert = array(
                                    'field_id' => $param_id,
                                    'ordering' => 1,
                                    'name_ru-RU' => $param_value
                                );
                                $value_id = $db->insert('wle_jshopping_products_extra_field_values', $insert);
                            }
                            $value_ids [] = $value_id;
                        }
                        $db->update('wle_jshopping_products_extra_fields', array('multilist' => 1), 'id=' . $param_id);
                        $db->update('wle_jshopping_products', array('extra_field_' . $param_id => implode(',', $value_ids)), 'product_id=' . $product_id);

//                            echo 'с span';
//                            echo "<br/>\n";
                    } else {
//                            echo 'обычный';
//                            echo $param['value'];
//                            echo "<br/>\n";
                        $param_value = trim($param['value']);
                        $sql = "SELECT id FROM wle_jshopping_products_extra_field_values WHERE `name_ru-RU`='{$param_value}' AND field_id={$param_id}";
                        $value_id = $db->fetchOne($sql);
                        if (!$value_id) {
                            $insert = array(
                                'field_id' => $param_id,
                                'ordering' => 1,
                                'name_ru-RU' => $param_value
                            );
                            $value_id = $db->insert('wle_jshopping_products_extra_field_values', $insert);
                        }
                        $db->update('wle_jshopping_products', array('extra_field_' . $param_id => $value_id), 'product_id=' . $product_id);
                    }
                }
            }
        }

        require_once(dirname(__FILE__) . '/mysql.class.php');
//        require_once(dirname(__FILE__) . '/curl.class.php');
        require_once(dirname(__FILE__) . '/../configuration.php');
        $jconf = new jConfig();
        $db = new mysqldb($jconf->db, $jconf->host, $jconf->user, $jconf->password, $jconf->dbprefix);
        $res = $db->query("SET NAMES utf8");
        
//        $curl = new Curl();


//        $url = 'http://pechiikamini.ru/g247686-pechi-kaminy';
        $url = 'http://dev1.wwwlab.biz/proxy.php?url=' . $_REQUEST['url'];
        getProductsLinks($url);
        ?>

    </body>
</html>
