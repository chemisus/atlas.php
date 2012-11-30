<?php

namespace atlas;

use \ArrayAccess, \Countable, \IteratorAggregate;

class Query implements ArrayAccess, Countable, IteratorAggregate {
    protected $value;
    
    private $repository;

    public function count() {
        return count($this->value);
    }
    
    public function __get($key) {
        return $this[$key];
    }

    public function __set($key, $value) {
        $this[$key] = $value;
    }

    public function offsetExists($key) {
        return is_array($this->value) ? isset($this->value[$key]) : isset($this->value->{$key});
    }

    public function offsetGet($key) {
        if (!isset($this[$key])) {
            return new Query(null, $this->repository ? $this->repository : $this);
        }
        
        if (is_array($this->value)) {
            $value = &$this->value[$key];
        }
        else {
            $value = &$this->value->{$key};
        }
        
        if (!is_array($value) && !is_object($value)) {
            return $value;
        }
        
        $query = new Query(null, $this->repository ? $this->repository : $this);
        
        $query->value = &$value;
        
        if (isset($query['@ref'])) {
            return $this->dereference($query['@ref']);
        }
        
        return $query;
    }

    public function offsetSet($key, $value) {
        if ($value instanceof Query) {
            $value = $value->value();
        }
        
        if (is_array($this->value)) {
            if ($key === null) {
                $this->value[] = $value;
            }
            else {
                $this->value[$key] = $value;
            }
        }
        else {
            $this->value->{$key} = $value;
        }
    }

    public function offsetUnset($key) {
        unset($this->value[$key]);
    }

    public function value() {
        return $this->value;
    }

    public function __construct($value=array(), $repository=null) {
        $this->value = $value;
        
        $this->repository = $repository;
    }
    
    public function reference() {
        return json_decode('{"@ref": "'.$this['@key'].'"}');
    }
    
    public function dereference($key) {
        if (isset($this[$key])) {
            return $this[$key];
        }
        
        if ($this->repository) {
            return $this->repository->dereference($key);
        }
        
        return null;
    }
    
    public function subquery() {
        if (is_array($this->value)) {
            return new Query(array(), $this->repository ? $this->repository : $this);
        }
        else if (is_object($this->value)) {
            return new Query(new \stdClass(), $this->repository ? $this->repository : $this);
        }
        
        return new Query();
    }
    
    public function map($map) {
        $array = $this->subquery();
        
        $emit = function ($key, $value) use ($array) {
            if ($key instanceof Query) {
                $key = $key->value();
            }
            
            if ($value instanceof Query) {
                $value = $value->value();
            }
            
            if (!isset($array[$key])) {
                $array[$key] = array();
            }
            
            $array[$key][] = $value;
        };
        
        foreach ($this->value as $key=>$value) {
            $map($emit, new Query($value), $key);
        }
        
        return $array;
    }
    
    public function reduce($maps, $reduce) {
        $array = $this->subquery();
        
        $emit = function ($key, $value) use ($array) {
            if ($key instanceof Query) {
                $key = $key->value();
            }
            
            if ($value instanceof Query) {
                $value = $value->value();
            }
            
            $array[$key][] = $value;
        };
        
        foreach ($maps as $key=>$value) {
            $reduce($emit, new Query($value), $key);
        }
        
        return $array;
    }

    public function getIterator() {
        return new QueryIterator($this->value, $this->repository);
    }
}
