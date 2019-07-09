<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

$db = new mysqli('localhost', 'host1540871_main', '37DSHGeyrf4gy', 'host1540871_main');

if ($db->connect_errno) {
    echo 'Ошибка №' . $db->connect_errno . '<br>';
    echo 'Описание: '.$db->connect_error;
    exit;
}

$category_id = 228; // category: shop (multifoto.ru)

$sql_product = "SELECT
       `oc_product`.`product_id` AS `p_id`,
       `oc_product_description`.`name`,
       `oc_product`.`model` AS `code`,
       `oc_product`.`price`,
       `oc_product_special`.`price` AS `special_price`,
       `oc_product`.`image`
       FROM `oc_product`

       INNER JOIN `oc_product_description`
       ON `oc_product`.`product_id` = `oc_product_description`.`product_id`

       INNER JOIN `oc_product_to_category`
       ON `oc_product`.`product_id` = `oc_product_to_category`.`product_id`

       INNER JOIN `oc_category_path`
       ON `oc_product_to_category`.`category_id` = `oc_category_path`.`category_id`

       LEFT JOIN `oc_product_special`
       ON `oc_product`.`product_id` = `oc_product_special`.`product_id`

       WHERE `oc_category_path`.`path_id` = '$category_id'
       GROUP BY `oc_product`.`product_id`
       LIMIT 10 OFFSET 0";

if ($query_products = $db->query($sql_product)) {
    if ($query_products->num_rows > 0) {
        echo 'Всего товаров: ' . $query_products->num_rows . PHP_EOL;

        $index = 0;
        while ($row_product = $query_products->fetch_assoc()) {
            $products[$index]['name']  = str_replace('&quot;', '"', $row_product['name']);
            $products[$index]['code']  = $row_product['code'];
            $products[$index]['price'] = empty($row_product['special_price']) ? $row_product['price'] : $row_product['special_price'];
            $products[$index]['image'] = $row_product['image'];
            // copyImages($row_product['image']);

            $query_images = $db->query("SELECT `image` FROM `oc_product_image` WHERE `product_id` = '$row_product[p_id]'");

            if ($query_images->num_rows > 0) {
                while ($row_images = $query_images->fetch_assoc()) {
                    $products[$index]['other_images'][] = $row_images['image'];
                    // copyImages($row_images['image']);
                }
            }

            $query_images->free();
            $index++;
        }

        print_r($products);
    }

    $query_products->free();
}

$db->close();

function copyImages($images)
{
    $path_images_new = './image';
    $path_images_old = '../image';
    $explode         = explode('/', $images);
    $count           = count($explode);

    if ($count == 1) {
        copy($path_images_old . '/' . $explode[0], $path_images_new . '/' . $explode[0]);
    } elseif ($count > 1) {
        $path_image = '';

        foreach ($explode as $index => $path) {
            if ($index < ($count - 1)) {
                $path_image .= '/' . $path;
            }
        }

        exec('mkdir -pm 777 ' . $path_images_new . $path_image, $output, $return);
        copy($path_images_old . $path_image . '/' . $explode[$count - 1], $path_images_new . '/' . $path_image . '/' . $explode[$count - 1]);
    }
}
