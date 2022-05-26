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
                "SELECT * FROM tb_users WHERE deslogin = :login",
                ["login" => $login]
            );

            if (count($result) <= 0) {
                throw new \Exception('Usuário não existe ou senha inválida');
            }

            $data = $result[0];

            // gera uma password hash
            // password_hash($password, PASSWORD_DEFAULT, ['cont' => 12]);

            if (password_verify($password, $data["despassword"])) {
                $user = new User();
                $user->setData($data);
                $_SESSION[User::SESSION] = $user->expose();
                // var_dump(json_encode($user->expose()));
                // exit;
                return $user;
            } else {
                throw new \Exception('Usuário não existe ou senha inválida');
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
                "SELECT *
                FROM    tb_users usr
                        INNER JOIN tb_persons per USING (idperson) ORDER BY per.desperson"
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

        public static function getForgot(string $email) {
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

        public static function checkLoginExist(string $login) :bool {
            $results = self::select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
                ':deslogin'=>$login
            ]);
            return (count($results) > 0);
        }

        private static function select(string $query, array $bind = []) : array {
            $DAO = new Sql();
            return $DAO->select($query, $bind);
        }

        private static function encriptPassword(string $password) : string {
            return password_hash($password, PASSWORD_DEFAULT, ['cont' => 12]);
        }
    }