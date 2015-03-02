<?php


namespace Lovoo\Component\Tracedump;

/**
 * @author David Wolter <david@dampfer.net>
 */
class Cli
{
   public function tracedump()
    {
        $end = array();
        $args = func_get_args();
        foreach ($args as $a) {
            $end[] = $this->diear($a);
        }

        return implode("\n\n".$this->LCC(str_repeat("-", 40), 90)."\n", $end)."\n\n";
    }

   public function diear(&$ar)
    {
        //vars
        $end = array();

        //[ check ]
        if (is_object($ar)) {
            return $this->dieobj($ar);
        } elseif (is_array($ar)) {
            if ($count = count($ar)) {
                foreach ($ar as $name => $value) {
                    $end[] = array(
                        "name"  => "".$this->LCC("\"".$name."\"", 31)."",
                        "value" => $this->dievalue($value),
                        "type"  => gettype($value),
                    );
                }
                $table = $this->dietable($end);
            } else {
                $table = " = array()";
            }
        } elseif (is_resource($ar)) {
            $table = "Ressource: ".get_resource_type($ar);
        } else {
            $table = "\n".var_export($ar, 1);
        }

        return $table;
    }

   public function dieobj($class)
    {

        //[ check ]
        if (!is_object($class)) {
            $this->diear($class);
        }

        $refl = new \ReflectionClass($class);
        $end = $this->dieobj_class($refl, $class);

        //Properties
        if ($ar = $refl->getProperties()) {
            $end = array_merge($end, $this->dieobj_properties($ar, $class, $refl));
        }

        //Properties static
        if ($ar = $refl->getStaticProperties()) {
            $end = array_merge($end, $this->dieobj_properties($ar, $class, $refl));
        }

        //Methods
        if ($ar = $refl->getMethods()) {
            $end = array_merge($end, $this->dieobj_methods($ar, $class, $refl));
        }

        return $this->dietable($end);
    }

   public function dieobj_class($data, $class = null, $refl = null)
    {
        $end = array();

        $end[] = array(
            "prop" => " ",
            "name" => $this->LCC("class ".$this->dievalue_class($data, $class), "1;44"),

        );

        return $end;

    }

   public function dieobj_methods($data, $class = null, $refl = null)
    {
        $end = array();

        $className = $refl->getName();

        foreach ($data as $k => $v) {

            //name
            $funcname = $v->getName();

            //Method Parameter
            $ar_param = array();
            if ($param = $v->getParameters()) {
                foreach ($param as $vv) {
                    $ar_param[] = "$".$vv->getName()."";
                }
            }
            $param = $ar_param ? " ".implode(" , ", $ar_param)." " : "";

            //extends
            $extendsClass = $v->getDeclaringClass();
            if ($className == $extendsClass->getName()) {
                $extendsClass = "";
            }
            if ($extendsClass) {
                $extendsClass = $this->dievalue_class($extendsClass, $class);
            }

            $name = "function ";
            $name .= $this->LCC($funcname, 1);
            $name .= "(".$this->LCC($param, 93).")";

            $end[] = array(
                "prop"   => $this->dieaccess($v),
                "name"   => $name,
                "type"   => "func",
                "header" => $this->LCC($extendsClass ? $extendsClass : "", "0;104"),
            );
        }

        return $end;
    }

   public function dieobj_properties($data, $class = null, $refl = null)
    {
        $end = array();

        $className = $refl->getName();

        foreach ($data as $v) {

            if (!is_object($v)) {
                continue;
            }

            //Name
            $name = $v->getName();

            //Value
            $value = null;
            if (method_exists($v, "setAccessible")) {
                $v->setAccessible(true);
            }
            if (method_exists($v, "getValue")) {
                $value = $v->getValue($class);
            }

            //extends
            $extendsClass = null;
            if (method_exists($v, "getDeclaringClass")) {
                $extendsClass = $v->getDeclaringClass();
            }
            if ($extendsClass) {
                if ($className == $extendsClass->getName()) {
                    $extendsClass = "";
                }
            }
            if ($extendsClass) {
                $extendsClass = $this->dievalue_class($extendsClass, $class);
            }

            $end[] = array(
                "prop"   => $this->dieaccess($v),
                "name"   => $this->LCC("$".$name, 93),
                "value"  => $this->dievalue($value, $class),
                "type"   => gettype($value),
                "header" => $this->LCC($extendsClass ? $extendsClass : "", "0;104"),
            );
        }

        return $end;
    }

   public function dietable($data)
    {

        $trs = "";
        $ar_trs = array();

        foreach ($data as $fe) {
            $type = isset($fe["type"]) ? $fe["type"] : null;
            $prop = isset($fe["prop"]) ? $fe["prop"] : null;
            $name = isset($fe["name"]) ? $fe["name"] : null;
            $value = isset($fe["value"]) ? $fe["value"] : null;
            $header = isset($fe["header"]) ? $fe["header"] : null;

            if ($value) {
                if ($type == "array") {
                    $name = $name."\t".trim($value);
                } else {
                    $name =
                    $name = $name."\t= ".trim($value);
                }

            }

            $line = ($prop ? $prop."\t" : "").
                $name.
                "\n";

            if ($header) {
                if (!isset($ar_trs[$header])) {
                    $ar_trs[$header] = "";
                }

                $ar_trs[$header] .= $line;
            } else {
                $trs .= $line;
            }
        }

        if (count($ar_trs)) {
            foreach ($ar_trs as $header => $fe_trs) {
                $trs .= $header."\n".$fe_trs."\n";
            }
        }

        return "\n".$trs."\n";
    }

   public function dievalue($value, $class = null)
    {

        $type = gettype($value);

        switch ($type) {
            case "string":
                $l = strlen($value);
                $max = 100;
                if ($l > $max) {
                    $value = substr($value, 0, $max)." ... ";
                }
                $value = $this->LCC("\"".$value."\"", 31);
                break;

            case "integer":
            case "double":
                $value = $this->LCC($value, 92);
                break;

            case "boolean":
                $value = $value ? "true" : "false";
                $color = $value == "true" ? "1;42" : "1;41";
                $value = $this->LCC($value, $color);
                break;

            case "array":
                $value = " = array (\n".$this->diear($value, 1).")";
                break;

            case "NULL":
                $value = $this->LCC("null", 5);
                break;

            case "object":
                $refl = new \ReflectionClass($value);
                $value = $this->dievalue_class($refl, $class);
                break;

            default:
                $value = $this->LCC("(".$type.")", 5);
                break;
        }

        return $value;
    }

   public function dievalue_class($data, $class = null)
    {

        $className = $data->getName();

        return $className;
    }

   public function dieaccess($ref)
    {
        $ar = array("static", "public", "protected", "private", "abstract", "abstract");
        foreach ($ar as $fe) {
            $method = "is".ucfirst($fe);
            if (method_exists($ref, $method)) {
                if ($ref->$method()) {

                    $fe = " ".$fe." ";
                    $leer = str_repeat(" ", 13 - strlen($fe));

                    return $leer.$this->LCC($fe, 7);
                }
            }
        }

        return "unknown";
    }

   public function LCC($text, $command = "")
    {

        return "\033[".$command."m".$text."\033[0m";
    }
}
