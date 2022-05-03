<?php
    namespace Rootdir;

    class Model {
        private array $values = [];

        public function __call($name, $args) {
            $method = substr($name, 0, 3);
            $fieldName =substr($name, 3, strlen($name));
            
            switch ($method) {
                case 'get':
                    return $this->values[$fieldName] ?? null;
                    break;
                case 'set':
                    return $this->values[$fieldName] = $args[0];
                    break;
            }
        }

        public function setData(array $data = []) {
            foreach ($data as $key => $value) {
                $this->{"set".$key}($value);
            }
        }

        public function expose() {
            return $this->values;
        }
    }