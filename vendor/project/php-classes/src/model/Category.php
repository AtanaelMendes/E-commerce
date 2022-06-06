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

        public function getPagination(int $page = 1, int $pageItems = 3) {
            $start = (($page - 1) * $pageItems);

            $result = self::select(
                "SELECT  a.*,
                        temptb.qtreg
                FROM    tb_products a
                        INNER JOIN tb_productscategories b USING(idproduct)
                        INNER JOIN tb_categories c USING(idcategory)
                        LEFT OUTER JOIN (
                            SELECT 	count(*) as qtreg, idcategory
                            FROM 	tb_productscategories bb
                            where idcategory = :idcategory1
                        ) temptb on (1=1)
                WHERE   c.idcategory = :idcategory2
                LIMIT $start, $pageItems", [
                    "idcategory1" => $this->getidcategory(),
                    "idcategory2" => $this->getidcategory()
                ]
            );

            $pages = [];
            for ($pg=1; $pg <= ceil($result[0]["qtreg"] / $pageItems); $pg++) {
                array_push($pages, [
                    "link" => "/categorias/".$this->getidcategory()."?page=".$pg,
                    "page" => $pg
                ]);
            }

            return [
                "data" => Product::checkList($result),
                "pages" => $pages
            ];
        }

        public static function getPaginationAdmin(int $page = 1, int $pageItems = 3, ?string $search = "") {
            $start = (($page - 1) * $pageItems);
            $bind = [];
            $sqlWhere = "";

            if (!empty($search)) {
                $sqlWhere = "WHERE (cat.descategory LIKE :search2)";
                $bind = [
                    "search1" => "%$search%",
                    "search2" => "%$search%"
                ];
            }

            $result = self::select(
                "SELECT cat.*,
                        temptb.qtcategories
                FROM    tb_categories cat
                        LEFT OUTER JOIN (
                            SELECT COUNT(*) AS qtcategories, idcategory FROM tb_categories
                            ".(!empty($search) ? "WHERE (descategory LIKE :search1)" : "")."
                        ) temptb on (1 = 1)
                {$sqlWhere}
                ORDER BY descategory
                LIMIT {$start}, {$pageItems}", $bind
            );

            $pages = [];
            for ($pg=1; $pg <= ceil($result[0]["qtcategories"] / $pageItems); $pg++) {
                array_push($pages, [
                    "href" => "/gestao/categorias?".http_build_query([
                        "page" => $pg,
                        "search" => $search
                    ]),
                    "text" => $pg
                ]);
            }
            
            return [
                "data" => $result,
                "pages" => $pages
            ];
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