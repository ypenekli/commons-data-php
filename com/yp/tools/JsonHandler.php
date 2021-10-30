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
            }else if (property_exists($std_class, "offset")){
                $result = Pager::fromJson($std_class);
            }
        }

        return $result;
    }

    static function get_class(string $target_class) {
        $pos = strripos($target_class, '\\');
        if($pos !== false){
            $target_class = substr($target_class, $pos + 1);
        }
        return JsonHandler::$CLASSES[$target_class];
    }
    
    public function promote1($std_class, $target_class = null)
    {
        try {

            /*
             * << Establish the parent Model type >>
             * Allow the user to specify the target_class by providing a string or object.
             * Assume extending class when null.
             */
            if (is_null($target_class)) {
                $object = $this;
            } elseif (is_object($target_class)) {
                $object = $target_class;
            } elseif (is_string($target_class)) {
                $object = new $target_class(); // New up a Model from a user-supplied class name--usually during recursion
            } else {
                return $std_class; // Not playing nice? Return the data passed in.
            }

            /*
             * Prepare this array in case we encounter an array of related records nested in our stdClass
             * Each element will be an array of one or more...the children being instances of the related Model (related to the parent).
             */

            $related_entities = array(); // eventual array of arrays

            /* Loop through the stdClass, accessing properties like an array. */
            foreach ($std_class as $property => $value) {

                /*
                 * If an array is found as the value of a property we assume it is full of realted entities;
                 * with the property name being the Model type (case sensitive)
                 *
                 */
                if (is_array($value)) { // all of these are stdClass as well, so we recurse to handle each one

                    /*
                     * $property should be named to fit the model of the entities in the array
                     * This is dependent on the user building the JSON object correctly upstream.
                     *
                     */
                    $related_entities[$property] = array();

                    foreach ($value as $entity) { // Get each array element and treat it as an entity
                        /*
                         * For thought-simplicity sake, let's assume this promote() call doesn't find related entities inside this related entity (Yo Dawg...).
                         * This adds the related entity to an array named for its Model: $related_entities['related_model_name'] = $object_returned_from_promote().
                         * This WILL, of course, recurse to infinity building out the complete data model.
                         */
                        $related_entities[$property] = $this->promote($entity, $property);
                    }
                } else {
                    /* Just add the value found to the property of the Model object */
                    $object->{$property} = $value;
                }
            }

            /*
             * Add each array of whatever related entities were found, to the parent object/table
             * This depends on the Phalcon ORM Model convention: $MyTableObject->relatedSomthings = array_of_related_somethings
             */
            foreach ($related_entities as $related_model => $entity_data) {
                $object->{$related_model} = $entity_data;
            }
        } catch (\Exception $e) {
            /*
             * If the user supplied data (decoded JSON) that does not match the Model we are going to experience an exception
             * when trying to access a property that doesn't exist.
             *
             */
            throw $e;
        }
        return $object; /* Usually only important when we are using recursion. */
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
}