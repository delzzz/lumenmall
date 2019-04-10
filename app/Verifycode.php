<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class Verifycode extends Model
{
    public function checkVerifycode($chnr)
    {
        header("content-type:image/jpeg");
//绘图
//创建一块画布设置宽高度
//验证码的生成字符数和不同组合情况
        $fontSize = 20;
        $fontHigh = $fontSize * 1.1;
        $width = ceil(strlen($chnr) * ($fontSize + 1.5));
        $height = $fontHigh * 1.8;
        $im = imagecreate($width, $height);
        imagecolorallocate($im, 181, 214, 249);  //背景色
        $borCo = imagecolorallocate($im, 204, 255, 0);//边框色
        $pixCo = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));//点色
        $lineCo = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));//线色
        $fontCo = imagecolorallocate($im, rand(0, 255), rand(0, 5), rand(0, 250));//文本色
//画边框
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $borCo);
//画点
        for ($i = 1; $i < 50; $i++) {
            imagesetpixel($im, rand(1, $width), rand(1, $height), $pixCo);
        }
//画线
        for ($i = 1; $i < 3; $i++) {
            imageline($im, 0, rand(1, $height - 1), $width, rand(1, $height - 1),
                $lineCo);
        }
        $font = __DIR__.'/../public/css/consola.ttf';

        for ($i = 0; $i <= strlen($chnr); $i++) {
            $sum = mb_substr($chnr, $i, 1, 'utf-8');
            imagettftext($im, $fontSize, 5, $i * $fontSize + rand(2, 5),
                rand($height / 1.2, $height / 2.5), $fontCo, $font, $sum);

        }
//生成图片
        imagejpeg($im);
//释放资源
        imagedestroy($im);
    }

    /*
    Describe:生成验证码的代码
    Paramters：$count生成验证码的位数
    return：生成的验证码

    */
    function getVerifycode($count = 4, $st = 1)
    {
        $checkNum = "";//生成的验证码

        $str = "";
        $str1 = "0 1 2 3 4 5 6 7 8 9";
        $str2 = "a b c d e f g h i j k l m n o p q r s t u v w x y z 
A B C D E F G H I J K L M N O P Q R S T U V W X Y Z";
        $str3 = "我 是 验 证 码 分 隔 符 为 空 格";
        switch ($st) {
            case 1:
                $str = $str1;
                break;
            case 2:
                $str = $str2;
                break;
            case 3:
                $str = $str3;
                break;
            case 4:
                $str = $str1 . ' ' . $str2;
                break;
            case 5:
                $str = $str1 . ' ' . $str3;
                break;
            case 6:
                $str = $str2 . ' ' . $str3;
                break;
            case 7:
                $str = $str1 . ' ' . $str2 . ' ' . $str3;
                break;
        }
        $arr = explode(" ", $str);
        for ($i = 0; $i < $count; $i++)
            $checkNum .= $arr[rand(0, count($arr) - 1)];
//处理大小写字母"o"成数字"0".
        $checkNum = str_replace("o", "0", str_replace("O", "0", $checkNum));
        $checkNum = str_replace('\r\n','',$checkNum);
        return $checkNum;
    }
}