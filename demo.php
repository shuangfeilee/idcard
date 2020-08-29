<?php
require_once __DIR__ . '/vendor/autoload.php';

use mfunc\Idcard;

try {
	$idcard = new Idcard();

	echo '<pre>';
	// 生成省份证号码 生成4个，30-36岁的号码
	var_dump($idcard->makeCardNum(4, 30, 36));
	// 验证身份证号
	var_dump($idcard->checkIdCardNum('530324198411229342'));
	// 根据身份证号获取生日，年龄，生效，星座，性别，归属地信息
	var_dump($idcard->getInfo('45263119860919006X'));
} catch (Exception $e) {
	echo $e->getMessage();
}