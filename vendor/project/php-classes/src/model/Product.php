<?php
    namespace Rootdir\Model;

    use \Rootdir\DB\Sql;
    use \Rootdir\Model as BaseModel;

    class Product extends BaseModel {

        public static function listAll() {
            return self::select("SELECT * FROM tb_products ORDER BY desproduct");
        }

        public function save() {
            $result = self::select(
                "CALL sp_products_save(
                    :idproduct,
                    :desproduct,
                    :vlprice,
                    :vlwidth,
                    :vlheight,
                    :vllength,
                    :vlweight,
                    :desurl
                )", [
                    "idproduct" => $this->getidproduct(),
                    "desproduct" => $this->getdesproduct(),
                    "vlprice" => $this->getvlprice(),
                    "vlwidth" => $this->getvlwidth(),
                    "vlheight" => $this->getvlheight(),
                    "vllength" => $this->getvllength(),
                    "vlweight" => $this->getvlweight(),
                    "desurl" => $this->getdesurl()
            ]);
            $this->setData($result[0]);
            // self::updateFile();
        }

        public function get(int $idproduct) {
            $result = self::select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
                "idproduct" => $idproduct
            ]);

            $this->setData($result[0]);
        }

        public function delete() {
            self::select("Call product_delete(:idproduct)", [
                "idproduct" => $this->getidproduct()
            ]);
            // self::updateFile();
        }

        public static function updateFile() {
            $categories = self::listAll();
            $html = [];

            foreach ($categories as $row) {
                $html[] = '<li><a href="/categorias/'.$row['idproduct'].'">'.$row['descategory'].'</a></li>';
            }

            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode(' ', $html)
            );
        }

        public function addPhoto($photo) {
            $extension = explode(".", $photo["name"]);
            $extension = end($extension);
            $img = "";

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $img = imagecreatefromjpeg($photo["tmp_name"]);
                    break;
                case 'gif':
                    $img = imagecreatefromgif($photo["tmp_name"]);
                    break;
                case 'png':
                    $img = imagecreatefrompng($photo["tmp_name"]);
                    break;
            }

            $newImg = self::getFullPath().$this->getidproduct().".jpg";

            imagejpeg($img, $newImg);

            // imagedestroy($img);

            $this->checkPhoto();
        }

        private static function select(string $query, array $bind = []) : array {
            $DAO = new Sql();
            return $DAO->select($query, $bind);
        }

        private function checkPhoto() {
            $img ="";
            if (file_exists(self::getFullPath().$this->getidproduct().".jpg")) {
                $img = self::getPath().$this->getidproduct().".jpg";
            } else {
                $img =  self::getPath()."trooperhelm.jpg";
            }
            return $this->setdesphoto($img);
        }

        public function expose() {
            $this->checkPhoto();
            $values = parent::expose();
            return $values;
        }

        public static function getFullPath() {
            return $_SERVER["DOCUMENT_ROOT"]."/resources/site/img/produtos/";
        }

        public static function getPath() {
            return "/resources/site/img/produtos/";
        }
    }