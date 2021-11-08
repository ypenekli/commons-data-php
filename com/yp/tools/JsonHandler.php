<?php
namespace com\yp\tools;

include_once CORE_PACKAGE_ROOT . '/core/FnParam.php';
include_once CORE_PACKAGE_ROOT . '/db/Pager.php';
include_once CORE_PACKAGE_ROOT . '/entity/DataEntity.php';

use com\yp\entity\DataEntity;
use com\yp\db\Pager;
use com\yp\core\FnParam;


class JsonHandler
{
    private static $CLASSES = [];
    
    public static function initialize(array $classes){
        JsonHandler::$CLASSES = $classes;
    }

    public static function parse(String $pJson)
    {
        if ($pJson != null) {
            $input = json_decode(trim($pJson, "'"));
            return JsonHandler::promote($input);
        }
    }

    //$jsonError = json_last_error();
    private static function handleError($jsonError){        
        if($jsonError != JSON_ERROR_NONE){
            $error = 'Could not decode JSON! ';
            
            //Use a switch statement to figure out the exact error.
            switch($jsonError){
                case JSON_ERROR_DEPTH:
                    $error .= 'Maximum depth exceeded!';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error .= 'Underflow or the modes mismatch!';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error .= 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error .= 'Malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $error .= 'Malformed UTF-8 characters found!';
                    break;
                default:
                    $error .= 'Unknown error!';
                    break;
            }
            error_log("jsonError!" . $error);
        }
        
    }
   
    public static function stringify($data)
    {
        if ($data != null) {
            //$jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE);
            $jsonStr = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
            self::handleError(json_last_error());
            return $jsonStr;
        }
    }

    public static function promoteArray($std_class){
        $x1 = $std_class[0];
        if (property_exists($x1, "state")) {
            $result = Array();
            foreach ($std_class as $x1) {
                $result[] = DataEntity::fromJson($x1);
            }
        } else if (property_exists($x1, "name")) {
            $result = Array();
            foreach ($std_class as $x1) {
                $result[] = FnParam::fromJson($x1);
            }
        }
        return $result;
    }
    
    public static function promote($std_class)
    {
        if (is_array($std_class)) {
            $x1 = $std_class[0];
            if (! is_array($x1)) {
                $result = JsonHandler::promoteArray($std_class);
            } else{
                JsonHandler::promote($x1);
            }
        } else {           
            if (property_exists($std_class, "state")){
                $result = DataEntity::fromJson($std_class);
            }else if (property_exists($std_class, "name")){
                $result = FnParam::fromJson($std_class);
            }else if (property_exists($std_class, "pageIndex")){
                $result = Pager::fromJson($std_class);
            }
        }

        return $result;
    }    
    

    public static function isValidJson($std_class)
    {
        if (is_array($std_class) || is_object($std_class)) {
            return true;
        }
        return false;
    }
    
    public static function isValidJson2($string)
    {
        json_decode($string);
        if (json_last_error() == JSON_ERROR_NONE) {
            
            if ($string[0] == "{" || $string[0] == "[") {
                $first = $string[0];
                
                if (substr($string, - 1) == "}" || substr($string, - 1) == "]") {
                    $last = substr($string, - 1);
                    
                    if ($first == "{" && $last == "}") {
                        return true;
                    }
                    
                    if ($first == "[" && $last == "]") {
                        return true;
                    }
                    
                    return false;
                }
                return false;
            }
            
            return false;
        }
        
        return false;
    }
    
    static function get_class(string $target_class) {
        $pos = strripos($target_class, '\\');
        if($pos !== false){
            $target_class = substr($target_class, $pos + 1);
        }
        return JsonHandler::$CLASSES[$target_class];
    }    
}