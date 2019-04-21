<?php

namespace ArrowWorker\Lib;

/**
 * Class Verifier
 */
class Verifier
{
    /**
     * @var string
     */
    private static $dateTimePattern = '/^\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}$/';

    /**
     * @var string
     */
    private static $timePattern = '/^(([0-1][0-9])|(2[0-3])):([0-5][0-9])$/';

    /**
     * @var string
     */
    private static $datePattern = '/^((^((1[8-9]\d{2})|([2-9]\d{3}))([-\/\._])(10|12|0?[13578])([-\/\._])(3[01]|[12][0-9]|0?[1-9])$)|(^((1[8-9]\d{2})|([2-9]\d{3}))([-\/\._])(11|0?[469])([-\/\._])(30|[12][0-9]|0?[1-9])$)|(^((1[8-9]\d{2})|([2-9]\d{3}))([-\/\._])(0?2)([-\/\._])(2[0-8]|1[0-9]|0?[1-9])$)|(^([2468][048]00)([-\/\._])(0?2)([-\/\._])(29)$)|(^([3579][26]00)([-\/\._])(0?2)([-\/\._])(29)$)|(^([1][89][0][48])([-\/\._])(0?2)([-\/\._])(29)$)|(^([2-9][0-9][0][48])([-\/\._])(0?2)([-\/\._])(29)$)|(^([1][89][2468][048])([-\/\._])(0?2)([-\/\._])(29)$)|(^([2-9][0-9][2468][048])([-\/\._])(0?2)([-\/\._])(29)$)|(^([1][89][13579][26])([-\/\._])(0?2)([-\/\._])(29)$)|(^([2-9][0-9][13579][26])([-\/\._])(0?2)([-\/\._])(29)$))$/';

    /**
     * @var string
     */
    private static $tokenPattern = '/^[a-zA-Z0-9_\-]{32}$/';

    /**
     * @var string
     */
    private static $stringPattern = '/^[a-zA-Z0-9,\s\_@，。：;；？?\/、.【】()\+（）《》“”‘’\_\=\-:\x{4e00}-\x{9fa5}]{1,}$/u';

    /**
     * @var string
     */
    private static $accountPattern = '/^[a-zA-Z0-9\_]{1,}$/u';

    /**
     * @var string
     */
    private static $characterPattern = '/^[\x{4e00}-\x{9fa5}]{1,}$/u';

    /**
     * @var string
     */
    private static $passwordPattern = '/^([a-zA-Z0-9]*[_!`\*&^%=$#_@+|\\;\':"<>\?\/\.,\-\[\]\{\}\(\)]+[a-zA-Z0-9]*)+$/';

    /**
     * @var string
     */
    private static $simplePattern = '/^[^\x{4e00}-\x{9fa5}]{6,}$/u';

    /**
     * @var string
     */
    private static $telPattern = '/^(^1[3456789]\d{9}$)|(^[0-9\-]+$)$/';

    /**
     * @var string
     */
    private static $mobilePattern = '/^1[3456789]\d{9}$/';

    /**
     * @var string
     */
    private static $fixedTelPattern = '/^[0-9\-]+$/';

    /**
     * @var string
     */
    private static $urlPattern = '/((http|https):\/{2})?[^\s]*$/';

    /**
     * @var string
     */
    private static $textPattern = '/^[a-zA-Z0-9,_，。：“”‘’\n\r\s-:\x{4e00}-\x{9fa5}]{1,}$/u';

    /**
     * @var array
     */
    private static $specialSymbol = [
        [
            '"',
            '\'',
            '=',
            '%',
            '_'
        ],
        [
            '\"',
            "\'",
            '\=',
            '\%',
            '\_',
        ]

    ];

    /**
     * @var string
     */
    private static $pricePattern = '/^[0-9]+(\.[0-9]{1,2}){0,1}$/';

    /**
     * @var string
     */
    private static $positiveIntPattern = '/^[1-9]+\d*$|^0$/';

    /**
     * @param string $datetime
     * @return bool
     */
    public static function IsDateTime(string $datetime): bool
    {
        $result = preg_match(static::$dateTimePattern, $datetime);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $date
     * @return bool
     */
    public static function IsDate(string $date): bool
    {
        $result = preg_match(static::$datePattern, $date);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $time
     * @return bool
     */
    public static function IsTime(string $time): bool
    {
        $result = preg_match(static::$timePattern, $time);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $string
     * @param int $maxLen
     * @return bool
     */
    public static function IsString(string $string, int $maxLen = 30)
    {
        if (mb_strlen($string, 'utf-8') > $maxLen) {
            return false;
        }

        $result = preg_match(static::$stringPattern, $string);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $string
     * @param int $maxLen
     * @return bool
     */
    public static function IsAccount(string $string, int $maxLen = 30)
    {
        if (strlen($string) > $maxLen) {
            return false;
        }

        $result = preg_match(static::$accountPattern, $string);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $password
     * @param int $minLen
     * @return bool
     */
    public static function IsPassword(string $password, int $minLen = 6)
    {
        if (strlen($password) < $minLen) {
            return false;
        }

        $result = preg_match(static::$passwordPattern, $password);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $password
     * @return bool
     */
    public static function IsSimplePassword(string $password)
    {
        $result = preg_match(static::$simplePattern, $password);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $text
     * @param int $maxLen
     * @return bool
     */
    public static function IsText(string $text, int $maxLen)
    {
        if (mb_strlen($text, 'utf-8') > $maxLen) {
            return false;
        }

        $result = preg_match(static::$textPattern, $text);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function IsToken(string $token): bool
    {
        if (preg_match(static::$tokenPattern, $token) === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param int $sex
     * @return bool
     */
    public static function IsSex(int $sex): bool
    {
        if ($sex >= 0 && $sex < 5) {
            return true;
        }
        return false;
    }

    /**
     * @param string $string
     * @param string $glue
     * @return bool
     */
    public static function IsSplitStr(string $string, string $glue = ','): bool
    {
        if (preg_match("/^(\d{1,}{$glue})*\d{1,}$/", $string) === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $tel
     * @param int $type
     * @return bool
     */
    public static function IsTel(string $tel, int $type = 0): bool
    {
        switch ($type) {
            case 1:
                $patten = static::$mobilePattern;
                break;
            case 2:
                $patten = static::$fixedTelPattern;
                break;
            default:
                $patten = static::$telPattern;
        }

        if (preg_match($patten, $tel) === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function IsUrl(string $url): bool
    {
        if (preg_match(static::$urlPattern, $url) === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $string
     * @return bool
     */
    public static function IsJson(string $string): bool
    {
        if( json_decode($string) !==null && ( strpos($string,'[')!==false || strpos($string,'{')!==false ) )
        {
            return true;
        }
        return false;
    }

    /**
     * @param string $character
     * @return bool
     */
    public static function IsCharacter(string $character): bool
    {
        if (preg_match(static::$characterPattern, $character) === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $data
     * @param array $symbolArray
     * @param array $normalArray
     * @return mixed
     */
    public static function ReplaceSpecialSymbol($data, array $symbolArray=[], array $normalArray=[])
    {
        if( count($symbolArray)==0 )
        {
            $symbolArray = static::$specialSymbol[0];
        }
        if( count($normalArray)==0 )
        {
            $normalArray = static::$specialSymbol[1];
        }
        return trim(str_replace($symbolArray, $normalArray, $data));
    }

    /**
     * @param string $oraString
     * @return string
     */
    public static function Html2Normal(string $oraString) : string
    {
        $oraString =  htmlspecialchars($oraString,ENT_QUOTES);
        return str_replace([PHP_EOL,'\b','\r\n', '\n', '\r'],'',$oraString);
    }

    /**
     * @param  $price
     * @return bool
     */
    public static function IsPrice(string $price ): bool
    {
        $result = preg_match(static::$pricePattern, $price);
        if ($result === 1) {
            $priceFloat = (float)$price;
            if($priceFloat<0 && $priceFloat>99999999)
            {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $positiveInt
     * @return bool
     */
    public static function IsPositiveInt(string $positiveInt): bool
    {
        $result = preg_match(static::$positiveIntPattern, $positiveInt);
        if ($result === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param string $positiveInt
     * @return bool
     */
    public static function IsXml(string $data): bool
    {
        $xmlParser = xml_parser_create();
        if( !xml_parse($xmlParser, $data, true) )
        {
            return false;
        }
        return true;
    }

    /**
     * 将\n替换为@@@@@ , 将\r替换为_____
     * @param string $data
     * @return string
     */
    public static function ReplaceLineBreak(string $data) : string
    {
        return preg_replace(['/'.PHP_EOL.'/','/\\\{1,}n/','/\\\{1,}r/'],["l_i_n_e__b_r_e_a_k_1",'l_i_n_e__b_r_e_a_k_2','l_i_n_e__b_r_e_a_k_3'],$data);
    }

    /**
     * 将@@@@@替换为\n , 将_____替换为\r
     * @param string $data
     * @return string
     */
    public static function RecoveryLineBreak(string $data) : string
    {
        return preg_replace(['/l_i_n_e__b_r_e_a_k_1/','/l_i_n_e__b_r_e_a_k_2/','/l_i_n_e__b_r_e_a_k_3/'], [PHP_EOL, '\n', '\r'], $data);
    }

}
