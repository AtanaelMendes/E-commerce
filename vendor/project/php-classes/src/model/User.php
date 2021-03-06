<?php
    namespace Rootdir\Model;

    use \Rootdir\DB\Sql;
    use \Rootdir\Model as BaseModel;
    use \Rootdir\Mailer;

    class User extends BaseModel {
        const SESSION = "user";
        const SECRET = "HcodePhp7_Secret";
        const SECRET_IV = "HcodePhp7_Secret_IV";
        const ERROR = "UserError";
        const ERROR_REGISTER = "UserErrorRegister";
        const SUCCESS = "UserSucesss";

        public static function getFromSession() {
            $user = new User();
            if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]["iduser"] > 0) {
                $user->setData($_SESSION[User::SESSION]);
            }
            return $user;
        }

        /**
         * se true está logado, false não
         *
         * @param bool $isAdmin
         * @return boolean
         */
        public static function checkLogin(bool $isAdmin = true) :bool {
            if ($isAdmin) {
                return (
                    !empty($_SESSION[self::SESSION])
                    && (int)$_SESSION[self::SESSION]["iduser"] > 0
                    && (bool)$_SESSION[User::SESSION]["inadmin"]
                );
            }
            return (!empty($_SESSION[self::SESSION]) && (int)$_SESSION[self::SESSION]["iduser"] > 0);
        }

        /**
         * Realiza login do suauario
         *
         * @param string $login
         * @param string $passwor
         * @return void
         */
        public static function login(string $login, string $password)
        {
            $result = self::select(
                "SELECT usr.*, per.*
                FROM    tb_users usr
                        INNER JOIN tb_persons per USING (idperson) WHERE usr.deslogin = :login",
                ["login" => $login]
            );

            if (count($result) <= 0) {
                self::setMsgError('Usuário não existe ou senha inválida');
            }

            $data = $result[0];

            // gera uma password hash
            // password_hash($password, PASSWORD_DEFAULT, ['cont' => 12]);

            if (password_verify($password, $data["despassword"])) {
                $user = new User();
                $user->setData($data);
                $_SESSION[User::SESSION] = $user->expose();
                return $user;
            } else {
                self::setMsgError('Usuário não existe ou senha inválida');
            }
        }

        /**
         * verifica se o usuario esta logado
         *
         * @param boolean $isAdmin
         * @return void
         */
        public static function verifyLogin(bool $isAdmin = true) {
            if (!self::checkLogin($isAdmin)) {
                if ($isAdmin) {
                    header("Location: /gestao/login");
                } else {
                    header("Location: /login");
                }
                exit;
            }
        }

        public static function logout() {
            unset($_SESSION[User::SESSION]);
        }

        public static function listAll() {
            return self::select(
                "SELECT usr.*, per.*
                FROM    tb_users usr
                        INNER JOIN tb_persons per
                        USING (idperson)
                ORDER BY per.desperson"
            );
        }

        public function save() {
            $result = self::select(
                "CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
                "desperson" => utf8_decode($this->getdesperson()),
                "deslogin" => utf8_decode($this->getdeslogin()),
                "despassword" => self::encriptPassword($this->getdespassword()),
                "desemail" => $this->getdesemail(),
                "nrphone" => $this->getnrphone(),
                "inadmin" => $this->getinadmin()
            ]);

            $this->setData($result[0]);
        }

        public function update() {
            $result = self::select(
                "CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
                "iduser" => $this->getiduser(),
                "desperson" => utf8_decode($this->getdesperson()),
                "deslogin" => utf8_decode($this->getdeslogin()),
                "despassword" => self::encriptPassword($this->getdespassword()),
                "desemail" => $this->getdesemail(),
                "nrphone" => $this->getnrphone(),
                "inadmin" => $this->getinadmin()
            ]);

            $this->setData($result[0]);
        }

        public function get(int $iduser) {
            $result = self::select(
                "SELECT *
                FROM    tb_users usr
                        INNER JOIN tb_persons per USING(idperson)
                WHERE   usr.iduser = :iduser", [
                "iduser" => $iduser
            ]);

            $this->setData($result[0]);
        }

        public function delete() {
            self::select("CALL sp_users_delete(:iduser)", [
                "iduser" => $this->getiduser()
            ]);
        }

        public static function getForgot(string $email, bool $isAdmin = true) {
            $result = self::select("SELECT * FROM tb_persons p INNER JOIN tb_users u USING(idpertson) WERE p.desemail = :email", ["email" => $email]);
            if (count($result) === 0) {
                throw new \Exception('Não foi possível recuperar a senha!');
            } else {
                $user = $result[0];
                $result2 = self::select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", [
                    "iduser" => $user["iduser"],
                    "desip" => $_SERVER["REMOTE_ADDR"]
                ]);
                if (count($result2) === 0) {
                    throw new \Exception('Não foi possível recuperar a senha!');
                } else {
                    $hashRecovery = $result2[0];
                    $code = base64_encode(
                        openssl_encrypt($hashRecovery["idrecovery"], "AES-128-CBC", pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV))
                    );
                    $link = "http://www.atanael.com.br/gestao/forgot/reset?code=$code";
                    if (!$isAdmin) {
                        $link = "http://www.atanael.com.br/forgot/reset?code=$code";
                    }
                    $mailer = new Mailer($user["desemail"], $user["desperson"], "Redefinir senha E-commerce", "forgot", [
                        "name" => $user["desperson"],
                        "link" => $link
                    ]);
                    $mailer->send();
                    return $user;
                }
            }
        }

        public static function validForgotDecrypt($code)
        {
            $code = base64_decode($code);
            $idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

            $results = self::select("
                SELECT *
                FROM tb_userspasswordsrecoveries a
                INNER JOIN tb_users b USING(iduser)
                INNER JOIN tb_persons c USING(idperson)
                WHERE
                    a.idrecovery = :idrecovery
                    AND
                    a.dtrecovery IS NULL
                    AND
                    DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
                ", [":idrecovery"=>$idrecovery]
            );

            if (count($results) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.");
            } else {
                return $results[0];
            }
        }

        public static function setFogotUsed($idrecovery) {
            $sql = new Sql();
            $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
                "idrecovery" => $idrecovery
            ));
        }

        public function setPassword($password) {
            $sql = new Sql();
            $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
                "password"=> self::encriptPassword($password),
                "iduser"=> $this->getiduser()
            ));
        }

        public static function setMsgError($msg) {
            $_SESSION[self::ERROR] = $msg;
        }

        public static function getMsgError() :string {
            $msg =  (!empty($_SESSION[self::ERROR]) ? $_SESSION[self::ERROR] : "");
            self::clearMsgError();
            return $msg;
        }

        public static function clearMsgError() {
            $_SESSION[self::ERROR] = null;
        }

        public static function setMsgErrorRegister($msg) {
            $_SESSION[self::ERROR_REGISTER] = $msg;
        }

        public static function getMsgErrorRegister() :string {
            $msg =  (!empty($_SESSION[self::ERROR_REGISTER]) ? $_SESSION[self::ERROR_REGISTER] : "");
            self::clearMsgErrorRegister();
            return $msg;
        }

        public static function clearMsgErrorRegister() {
            $_SESSION[self::ERROR_REGISTER] = null;
        }

        public static function setSuccess($msg) {
            $_SESSION[self::SUCCESS] = $msg;
        }

        public static function getSuccess() :string {
            $msg =  (!empty($_SESSION[self::SUCCESS]) ? $_SESSION[self::SUCCESS] : "");
            self::clearSuccess();
            return $msg;
        }

        public static function clearSuccess() {
            $_SESSION[self::SUCCESS] = null;
        }

        public static function checkLoginExist(string $login) :bool {
            $results = self::select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
                ':deslogin'=>$login
            ]);
            return (count($results) > 0);
        }

        /**
         * verifica o formulário de cadastro de usuário
         *
         * @param arrary $request
         * @return void
         */
        public static function verifyRequestCad(array $request) {
            if (empty($request["name"])  || strlen($request["name"]) < 3) {
                User::setMsgErrorRegister("Nome é obrigatorio e mínimo de 3 letras");
                header("Location: /login");
                exit;
            }
            $emailRegex = "/^[a-z0-9.]+@[a-z0-9]+\.[a-z]+\.([a-z])+?$/i";
            $isemail = preg_match($emailRegex, $request["email"]);
            if (empty($request["email"])  || !$isemail) {
                User::setMsgErrorRegister("Informe um E-mail válido");
                header("Location: /login");
                exit;
            }
            if (User::checkLoginExist($request["email"])) {
                User::setMsgErrorRegister("Este E-mail já está em uso");
                header("Location: /login");
                exit;
            }
            if (empty($request["password"])  || strlen($request["password"]) < 6) {
                User::setMsgErrorRegister("Senha é obrigatorio, mínimo de 6 caracteres");
                header("Location: /login");
                exit;
            }
        }

        public static function verifyRequestEditProfile(array $request, User $user) {
            if (empty($request["desperson"]) || strlen($request["desperson"]) < 3) {
                User::setMsgError("Nome é obrigatorio e mínimo 3 letras");
                header("Location: /profile");
                exit;
            }
            $emailRegex = "/^[a-z0-9.]+@[a-z0-9]+\.[a-z]+\.([a-z])+?$/i";
            $isemail = preg_match($emailRegex, $request["desemail"]);
            if (empty($request["desemail"]) || !$isemail) {
                User::setMsgError("O E_mail é obrigatorio");
                header("Location: /profile");
                exit;
            }
            if ($request["desemail"] !== $user->getdesemail()) {
                if (User::checkLoginExist($request["desemail"])) {
                    User::setMsgError("Este E-mail já está em uso");
                    header("Location: /profile");
                    exit;
                }
            }
        }

        public function getOrders() {
            $result = self::select(
                "SELECT     *
                FROM        tb_orders a
                            INNER JOIN tb_ordersstatus b USING(idstatus)
                            INNER JOIN tb_carts c USING(idcart)
                            INNER JOIN tb_users d ON d.iduser = a.iduser
                            INNER JOIN tb_addresses e USING(idaddress)
                            INNER JOIN tb_persons f ON f.idperson = d.idperson
                WHERE       a.iduser = :iduser
            ", [
                "iduser" => $this->getiduser()
            ]);
            return $result;
        }

        public static function getPagination(int $page = 1, int $pageItems = 3, ?string $search = "") {
            $start = (($page - 1) * $pageItems);
            $bind = [];
            $sqlWhere = "";

            if (!empty($search)) {
                $sqlWhere = "WHERE (per.desperson LIKE :search2 OR per.desemail = :search3 OR usr.deslogin LIKE :search4)";
                $bind = [
                    "search1" => "%$search%",
                    "search2" => "%$search%",
                    "search3" => "%$search%",
                    "search4" => "%$search%"
                ];
            }

            $result = self::select(
                "SELECT usr.*,
                        per.*,
                        temptb.qtuser
                FROM    tb_users usr
                        INNER JOIN tb_persons per
                        USING (idperson)
                        LEFT OUTER JOIN (
                            SELECT COUNT(*) AS qtuser, iduser FROM tb_users
                            ".(!empty($search) ? "WHERE (deslogin LIKE :search1)" : "")."
                        ) temptb on (1 = 1)
                $sqlWhere
                ORDER BY per.desperson
                LIMIT {$start}, {$pageItems}", $bind
            );

            $pages = [];
            for ($pg=1; $pg <= ceil($result[0]["qtuser"] / $pageItems); $pg++) {
                array_push($pages, [
                    "href" => "/gestao/users?".http_build_query([
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

        private static function encriptPassword(string $password) : string {
            return password_hash($password, PASSWORD_DEFAULT, ['cont' => 12]);
        }
    }