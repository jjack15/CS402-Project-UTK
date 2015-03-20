<?php

class LocalVar {
    private $name;
    private $value;
    private $type;

    function __construct($in_name, $in_type = "prim") {
        $this->name = $in_name;
        $this->type = $in_type;
    }

    public function get_name() {
        return $this->name;
    }
    
    public function set_value($in_value) {
        $this->value = $in_value;
    }
    
    public function get_value() {
        return $this->value;
    }
}

?>
