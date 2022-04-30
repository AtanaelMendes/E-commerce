<?php
    namespace Rootdir\Model;

    use \Rootdir\DB\Sql;
    use \Rootdir\Model as BaseModel;

    class User extends BaseModel {
        const SESSION = "user";

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
            if (
                empty($_SESSION[User::SESSION])
                || !(int)$_SESSION[User::SESSION]["iduser"] > 0
                || !(bool)$_SESSION[User::SESSION]["inadmin"] === $isAdmin
                ) {
                header("Location: /gestao/login");
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
                "desperson" => $this->getdesperson(),
                "deslogin" => $this->getdeslogin(),
                "despassword" => $this->getdespassword(),
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
                "desperson" => $this->getdesperson(),
                "deslogin" => $this->getdeslogin(),
                "despassword" => $this->getdespassword(),
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
            self::select("CALL sp_users_delete(:iduser)",[
                "iduser" => $this->getiduser()
            ]);
        }

        public static function select(string $query, array $bind = []) : array {
            $DAO = new Sql();
            return $DAO->select($query, $bind);
        }
    }