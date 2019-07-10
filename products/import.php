<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog.php';

use Bitrix\Main\Loader;

function rus_in_translit($string)
{
	$set_of_letters = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v',
		'г' => 'g', 'д' => 'd', 'е' => 'e',
		'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
		'и' => 'i', 'й' => 'y', 'к' => 'k',
		'л' => 'l', 'м' => 'm', 'н' => 'n',
		'о' => 'o', 'п' => 'p', 'р' => 'r',
		'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
		'ь' => '', 'ы' => 'y', 'ъ' => '',
		'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

		'А' => 'A', 'Б' => 'B', 'В' => 'V',
		'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
		'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
		'И' => 'I', 'Й' => 'Y', 'К' => 'K',
		'Л' => 'L', 'М' => 'M', 'Н' => 'N',
		'О' => 'O', 'П' => 'P', 'Р' => 'R',
		'С' => 'S', 'Т' => 'T', 'У' => 'U',
		'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
		'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
		'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
		'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
	);
	return strtr($string, $set_of_letters);
}

function translit_in_url($str)
{
	$str = rus_in_translit($str);
	$str = strtolower($str);
	$str = preg_replace('~[^-a-zA-Z0-9_]+~u', '-', $str);
	$str = trim($str, '-');

	return $str;
}

function addProduct(array $product)
{
	Loader::includeModule('iblock');
	Loader::includeModule('catalog');

	if (empty($product['image'])) {
		$mainPicture = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/upload/catalog/not-image.png');
	} else {
		$mainPicture = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $product['image']);
	}

	if (count($product['other_images']) > 0) {
		$morePhoto = [];

		foreach ($product['other_images'] as $key => $img) {
			$morePhoto['n' . $key] = [
				'VALUE' => CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $img)
			];
		}
	} else {
		$morePhoto = [];
	}

	$arFields = [
		'IBLOCK_ID'          => 57,
		'IBLOCK_SECTION_ID'  => 477,
		'NAME'               => $product['name'],
		'CODE'               => translit_in_url($product['name']),
		'ACTIVE'             => 'Y',
		'DETAIL_PICTURE'     => $mainPicture,
		'PREVIEW_PICTURE'    => $mainPicture,
		'XML_ID'             => $product['code'],
		'PROPERTY_VALUES'    => [
			'MORE_PHOTO' => $morePhoto,
			690 => $product['name'],
			566 => $product['code'],
			692 => $mainPicture
		]
	];

	$obElement = new CIBlockElement();
	$ID = $obElement->Add($arFields);

	if ($ID < 1) {
		echo $obElement->LAST_ERROR;
	}

	$productID = CCatalogProduct::add([
		'ID'             => $ID,
		'QUANTITY'       => 1
	]);

	$arFields = [
		'CURRENCY'         => 'RUB',
		'PRICE'            => $product['price'],
		'CATALOG_GROUP_ID' => 1,
		'PRODUCT_ID'       => $ID,
	];

	CPrice::Add($arFields);
}

$db = new mysqli('localhost', 'host1540871_main', '37DSHGeyrf4gy', 'host1540871_main');

if ($db->connect_errno) {
	echo 'Ошибка №' . $db->connect_errno . '<br>';
	echo 'Описание: ' . $db->connect_error;
	exit;
}

// category: shop (multifoto.ru)
$category_id = 228;

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
	   LIMIT 500 OFFSET 5000";

if ($query_products = $db->query($sql_product)) {
	if ($query_products->num_rows > 0) {
		echo 'Всего товаров: ' . $query_products->num_rows . '<br>';

		$index = 0;
		while ($row_product = $query_products->fetch_assoc()) {
			$products[$index]['name']  = str_replace('&quot;', '"', $row_product['name']);
			$products[$index]['code']  = $row_product['code'];
			$products[$index]['price'] = empty($row_product['special_price']) ? $row_product['price'] : $row_product['special_price'];
			$products[$index]['image'] = $row_product['image'];

			$query_images = $db->query("SELECT `image` FROM `oc_product_image` WHERE `product_id` = '$row_product[p_id]'");

			if ($query_images->num_rows > 0) {
				while ($row_images = $query_images->fetch_assoc()) {
					$products[$index]['other_images'][] = $row_images['image'];
				}
			}

			addProduct($products[$index]);

			$query_images->free();
			$index++;
		}
	}

	$query_products->free();
}

$db->close();
