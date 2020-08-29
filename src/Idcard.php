<?php
namespace mfunc;

class Idcard
{
	/**
	 * 地址数据
	 */
	protected $region;

    /**
     * 加权因子
     */
    static protected $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

    /**
     * 校验码对应值
     */
    static protected $verify = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

    public function __construct ()
    {
    	$file   = __DIR__ . '/region.json';
    	if (!is_file($file)) throw new \Exception("region.json文件缺失");
    
    	$region = file_get_contents($file);
    	$this->region = json_decode($region, true);
    }

    /**
     * 随机生成身份证号
     * @param int $nums 生成数量
     * @param int $min  最小岁数
     * @param int $max  最大岁数
     * @return array
     */
    public function makeCardNum ($nums = 5, $min = 18, $max = 100)
    {
        // 获取区域代码
        $region = array_map('array_shift', $this->region);
        // 区域代码数量
        $regionCount = count($region);

        $cardNums = [];
        $start = mktime(0, 0, 0, 1, 1, date('Y') - $max);
        $end   = mktime(0, 0, 0, 12, 31, date('Y') - $min);

        for ($i = 0; $i < $nums; $i++) {
            $key = mt_rand(0, $regionCount);
            $birthday = date('Ymd', mt_rand($start, $end));

            $suffix_a = mt_rand(0, 9);
            $suffix_b = mt_rand(0, 9);
            $suffix_c = mt_rand(0, 9);

            // 拼接前17位
            $cardNumBase = $region[$key] . $birthday . $suffix_a . $suffix_b . $suffix_c;
            $cardNums[]  = $cardNumBase . self::_idcardVerifyNumber($cardNumBase);
        }

        // 去重
        $cardNums = array_unique($cardNums);
        // 去重后补充
        while (count($cardNums) != $nums) {
        	$cardNums = array_unique(array_merge($cardNums, $this->makeCardNum($nums - count($cardNums), $min, $max)));
        }
        return $cardNums;
    }

    /**
    * 根据出生日期计算年龄、生肖、星座
    * @param string $cardNum 身份证号
    * @throws \Exception
    * @return array
    */
    public function getInfo ($cardNum)
    {
    	if (!self::_checkIdCardNum($cardNum)) throw new \Exception("身份证号码格式有误!");
    	
        $info = [];
        // 获取出生年月日
        $y = (int)substr($cardNum, 6, 4);
        $m = (int)substr($cardNum, 10, 2);
        $d = (int)substr($cardNum, 12, 2);

        // 出生日期
        $info['birthday'] = implode('-', [$y, $m, $d]);

        // 计算年龄
        $_m = date('n');  
        $_d = date('j');       
        $age = date('Y') - $y - 1;  
        if ($_m > $m || $_m == $m && $_d > $d) $age++;   
        $info['age'] = $age;

        // 计算生肖
        $animals = ['猴','鸡','狗','猪','鼠','牛','虎','兔','龙','蛇','马','羊'];
        $info['shengxiao'] = $animals[$y % 12];
        
        // 计算星座
        $zodiacs = ['水瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手', '摩羯'];
        $info['xingzuo'] = $d <= 22 ? (1 !== $m ? $zodiacs[$m-2] : $zodiacs[11]) : $zodiacs[$m-1];

        // 获取性别
        $info['gender'] = $cardNum[16] % 2 === 0 ? '女' : '男';

        // 获取归属地
        $addr = '';
        foreach ($this->region as $val) {
            if ($val[0] == substr($cardNum, 0, 6)) {
                $addr = $val[1].$val[2].$val[3];
                break;
            }
        }
        $info['address'] = $addr;

        return $info;
    }

    /**
     * 计算身份证校验码，根据国家标准GB 11643-1999
     * @param string $cardNumBase 身份证号前17位
     * @return string 身份证号最后一位校验码
     */
    static private function _idcardVerifyNumber($cardNumBase)
    {
        if(strlen($cardNumBase) != 17) return false;

        $checksum = 0; 
        for($i = 0; $i < strlen($cardNumBase); $i++){ 
            $checksum += $cardNumBase[$i] * self::$factor[$i]; 
        } 

        $mod = $checksum % 11; 
        $verifyNumber = self::$verify[$mod]; 

        return $verifyNumber; 
    }

    /**
     * 检测身份证号格式
     * @param string $cardNum 身份证号码
     * @return boolen
     */
    static private function _checkIdCardNum($cardNum)
    {
        $pattern="/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/";

        return preg_match($pattern, $cardNum);
    }

    /**
     * 将15位身份证升级到18位
     * @param string $cardNum 15位身份证号
     * @return string 18位身份证号
     */
    public static function cardNumTo18($cardNum)
    { 
        if(strlen($cardNum) != 15 || !self::_checkIdCardNum($cardNums)) throw new \Exception('身份证号校验失败');

        # 获取年份前两位 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码 
        $y = array_search(substr($cardNum, 12, 3), ['996', '997', '998', '999']) !== false ? '18' : '19';
        # 重新组合前17位号码
        $cardNum = substr($cardNum, 0, 6) . $y . substr($cardNum, 6, 9); 
        # 前17位号码与最后一位校验码组合为18位号码
        $cardNum = $cardNum . self::_idcardVerifyNumber($cardNum);

        return $cardNum; 
    }

    /**
     * 验证身份证号
     * @param string $cardNum 15,18位身份证号
     * @return boolen
     */
    public static function checkIdCardNum ($cardNum)
    {
        if (!self::_checkIdCardNum($cardNum)) return false;

        # 强制转化大写
        $cardNum = strtoupper($cardNum);

        # 18位身份证号验证
        if ( 18 == strlen($cardNum) ) {
            # 得到最后一位身份证号码
            $cardNumLast = $cardNum[17];
            # 用计算出的验证码与最后一位身份证号码匹配，如果一致，说明通过，否则是无效的身份证号码
            return $cardNumLast == self::_idcardVerifyNumber(substr($cardNum, 0, 17));
        }

        # 15位身份证号验证
        if ( 15 == strlen($cardNum) ) {
            $newCardNum = self::cardNumTo18($cardNum);
            return self::_checkIdCardNum($newCardNum);
        }
        return false;
    }
}