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

        public static function dd($content) {
            header('Content-Type: application/json');
            if (is_array($content)) {
                array_unshift($content, debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
                echo json_encode($content, JSON_PRETTY_PRINT);
                die;
            }

            echo json_encode([debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), $content], JSON_PRETTY_PRINT);
            die;
        }
    }