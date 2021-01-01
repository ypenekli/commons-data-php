<?php
namespace com\yp\tools;

class Configuration
{

    protected static $configs = array();

    function __construct($filename)
    {
        
        Configuration::readConfig($filename);
    }
    
    public static function getConfig($filename)
    {
        Configuration::readConfig($filename);
        return Configuration::$configs[$filename];
    }

    public static function getSubConfig($filename, $subgroupname)
    {
        Configuration::readConfig($filename);
        if (! isset(Configuration::$configs[$subgroupname])){
            $properties = array();
            foreach (Configuration::$configs[$filename] as $x1 => $x2) {
                $pos = strpos($x1, '.');
                $key = substr($x1, 0, $pos);
                if($key == $subgroupname){
                    $key2 = substr($x1, $pos + 1);
                    $properties[$key2] = $x2;
                }
            }            
            Configuration::$configs[$subgroupname] = $properties;
        }
        return Configuration::$configs[$subgroupname];
    }
    
    private static function readConfig($filename)
    {
        if (! isset(Configuration::$configs[$filename])) {
            $handle = fopen($filename, "r");
            if ($handle) {
                $properties = array();
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if (Configuration::startsWith($line, '#') == false) {
                        $pos = strpos($line, '=');
                        $key = substr($line, 0, $pos);
                        $query = substr($line, $pos + 1);
                        $properties[trim($key)] = trim($query);
                    }   
                }
                fclose($handle);
                Configuration::$configs[$filename] = $properties;
        } else {
            error_log("error oppening file! :" . $filename, 0);
        }
    }
   }

public static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, - strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
}
?>