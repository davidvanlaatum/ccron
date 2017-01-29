<?php
namespace Tests\CCronBundle\Assert;

class GetSetData {
    protected $data;

    /**
     * GetSetData constructor.
     * @param $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    public function getFields() {
        return array_keys($this->data);
    }

    public function getExpectedValue($field) {
        if (!array_key_exists($field, $this->data)) {
            throw new \InvalidArgumentException("Invalid field $field");
        } else if (is_array($this->data[$field])) {
            if (!array_key_exists('expected', $this->data[$field])) {
                throw new \InvalidArgumentException("No expected value for $field");
            }
            return $this->data[$field]['expected'];
        } else {
            return $this->data[$field];
        }
    }

    public function hasField($field) {
        return array_key_exists($field, $this->data);
    }

    public function getValue($field) {
        if (!array_key_exists($field, $this->data)) {
            throw new \InvalidArgumentException("Invalid field $field");
        } else if (is_array($this->data[$field])) {
            if (!array_key_exists('value', $this->data[$field])) {
                throw new \InvalidArgumentException("No value for $field");
            }
            return $this->data[$field]['value'];
        } else {
            return $this->data[$field];
        }
    }
}
