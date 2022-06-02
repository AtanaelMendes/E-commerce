<?php
namespace Rootdir;

use Rootdir\Model\User;
use Rain\Tpl;

class PageController {

    private $tpl;
    private $options = [];
    private $defaults = [
        "header" => true,
		"footer" => true,
        "data" => []
    ];

    public function __construct($opts = [], $tpl_dir = "/views/") {

        $this->options = array_merge($this->defaults, $opts);
        $this->tpl = new Tpl;

        $config  = [
            "tpl_dir" => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
            "cache_dir" => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
            "debug" => false
        ];

        Tpl::configure($config);

        $user = User::getFromSession();

        $this->options["data"] = array_merge($this->options["data"], ["userlogged" => []]);

        
        if ($user) {
            $this->options["data"] = array_merge($this->options["data"], ["userlogged" => $user->expose()]);
        }

        $this->setData($this->options["data"]);

        if ($this->options["header"]) {
            $this->tpl->draw("header");
        }
    }

    public function setData($data = []) {

        foreach ($data as $key => $value) {
            $this->tpl->assign($key, $value);
        }

    }

    public function setTpl($name, $data = [], $returnHTML = false) {

        $this->setData($data);

        return $this->tpl->draw($name, $returnHTML);

    }

    public function __destruct() {
        if ($this->options["footer"]) {
            $this->tpl->draw("footer");
        }
    }
}