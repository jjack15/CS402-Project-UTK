<?php

class LocalVar {
    private $name;
    private $value;
    private $type;
    private $is_initialized;

    function __construct($in_name, $in_type = "prim") {
        $this->name = $in_name;
        $this->type = $in_type;
        $this->is_initialized = false;
    }

    public function get_name() {
        return $this->name;
    }
    
    public function set_value($in_value) {
        $this->value = $in_value;
    }

    public function set_type($in_type) {
        $this->type = $in_type;
    }

    public function get_value() {
        $returnval = $this->value;
        if ($this->type == "int") $returnval = intval($this->value);
        elseif ($this->type == "double") $returnval = floatval($this->value);
        elseif ($this->type == "float") $returnval = floatval($this->value);
        elseif ($this->type == "bool") {
            //echo "THIS TYPE $this->type";
            if (str_replace(array(" ", "\n"), '', $this->value) == "true") $returnval = true;
            else $returnval = false;
        }
        return $returnval;
    }
    
    public function get_type() {
        return $this->type;
    }

    public function is_initialized() {
        return $this->is_initialized;
    }

    public function set_initialized() {
        $this->is_initialized = true;
    }
}

?>
