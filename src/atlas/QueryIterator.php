<?php

namespace atlas;

class QueryIterator implements \Iterator {
    private $array;
    
    private $repository;
    
    public function __construct($array, $repository=null) {
        $this->array = $array;
        
        $this->repository = $repository;
    }
    
    public function current() {
        $current = current($this->array);
        
        if (is_array($current) && isset($current['@ref'])) {
            return $this->repository->dereference($current['@ref']);
        }
        
        if (is_object($current) && isset($current->{'@ref'})) {
            return $this->repository->dereference($current->{'@ref'});
        }
        
        return $current;
    }

    public function key() {
        return key($this->array);
    }

    public function next() {
        return next($this->array);
    }

    public function rewind() {
        return reset($this->array);
    }

    public function valid() {
        return current($this->array);
    }
}
