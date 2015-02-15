<?php

class LocalVar {
    private $name;
    private $value;

    function __construct($in_name) {
        $this->name = $in_name;
    }

    public function set_value($in_value) {
        $this->value = $in_value;
    }
    
    public function get_value() {
        return $value;
    }
}

?>
