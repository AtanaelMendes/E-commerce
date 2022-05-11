<?php
    namespace Rootdir\Model;

    use \Rootdir\DB\Sql;
    use \Rootdir\Model as BaseModel;

    class Category extends BaseModel {

        public static function listAll() {
            return self::select("SELECT * FROM tb_categories ORDER BY descategory");
        }

        public function save() {
            $result = self::select(
                "CALL sp_categories_save(:idcategory, :descategory)", [
                    "idcategory"=>$this->getidcategory(),
                    "descategory"=>$this->getdescategory()
            ]);
            $this->setData($result[0]);
            self::updateFile();
        }

        public function get(int $idcategory) {
            $result = self::select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
                "idcategory" => $idcategory
            ]);

            $this->setData($result[0]);
        }

        public function delete() {
            self::select("Call category_delete(:idcategory)", [
                "idcategory" => $this->getidcategory()
            ]);
            self::updateFile();
        }

        public static function updateFile() {
            $categories = self::listAll();
            $html = [];

            foreach ($categories as $row) {
                $html[] = '<li><a href="/categorias/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>';
            }

            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode(' ', $html)
            );
        }

        public function getProducts(bool $related = true) {
            $condition =  ($related ? " IN " : " NOT IN ");

            return self::select("
                SELECT  *
                FROM    tb_products
                WHERE   idproduct $condition (
                    SELECT  p.idproduct
                    FROM    tb_products p
                            INNER JOIN tb_productscategories pc
                            USING(idproduct)
                    WHERE   pc.idcategory = :idcategory
                )",
                ["idcategory" => $this->getidcategory()]
            );
        }

        public function addProduct(Product $product) {
            self::query("
                INSERT INTO tb_productscategories (idcategory, idproduct)
                VALUES (:idcategory, :idproduct)",
                [
                    "idcategory" => $this->getidcategory(),
                    "idproduct" => $product->getidproduct()
                ]
            );
        }

        public function removeProduct(Product $product) {
            self::query("
                DELETE  FROM tb_productscategories
                WHERE   idcategory = :idcategory
                AND     idproduct = :idproduct",
                [
                    "idcategory" => $this->getidcategory(),
                    "idproduct" => $product->getidproduct()
                ]
            );
        }

        private static function select(string $query, array $bind = []) : array {
            $DAO = new Sql();
            return $DAO->select($query, $bind);
        }

        private static function query(string $query, array $bind = []) : void {
            $DAO = new Sql();
            $DAO->query($query, $bind);
        }
    }