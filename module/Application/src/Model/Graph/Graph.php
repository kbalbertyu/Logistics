<?php
namespace Application\Model\Graph;

abstract class Graph implements GraphInterface {
    protected $caption;
    protected $xName;
    protected $yName;
    protected $dataSet;
    protected $numberPrefix = '';
    
    public function setData($property, $value) {
        $this->$property = $value;
        return $this;
    }
}