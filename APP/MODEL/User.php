<?php
class User {
    private $db; // PDO connection when available
    private $dbInstance;
    private $phpDataPath;

    public function __construct(){
        $this->dbInstance = Database::getInstance();
        if ($this->dbInstance->isPdo()) {
            $this->db = $this->dbInstance->getConnection();
        }
        $this->phpDataPath = __DIR__ . '/../../DATA/users.php';
    }

    public function create($email, $password, $name = null, $role = 'user'){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $created_at = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("INSERT INTO users (email,password,name,created_at,bio,avatar,phone,role) VALUES (:email,:password,:name,:created_at,:bio,:avatar,:phone,:role)");
        return $stmt->execute([
            ':email'=>$email,
            ':password'=>$hash,
            ':name'=>$name,
            ':created_at'=>$created_at,
            ':bio'=>'',
            ':avatar'=>'',
            ':phone'=>'',
            ':role'=>$role
        ]);
    }

    public function findByEmail($email){
        // If no PDO available, fallback to PHP data file
        if (empty($this->db)) {
            if (file_exists($this->phpDataPath)) {
                $users = include $this->phpDataPath;
                if (is_array($users)) {
                    foreach ($users as $u) {
                        if (strcasecmp($u['email'] ?? '', $email) === 0) return $u;
                    }
                }
            }
            return null;
        }

        // Use case-insensitive lookup to avoid mismatches due to casing
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Fallback to safe exact match if LOWER not supported
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    public function findById($id){
        if (empty($this->db)) {
            if (file_exists($this->phpDataPath)) {
                $users = include $this->phpDataPath;
                if (is_array($users)) {
                    foreach ($users as $u) {
                        if (($u['id'] ?? 0) == $id) return $u;
                    }
                }
            }
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data){
        $fields = [];
        $values = [':id' => $id];
        foreach ($data as $key => $value) {
            if (!in_array($key, ['id', 'email'])) {
                $fields[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }
        if (empty($fields)) return false;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function setPassword($id, $hash){
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute([':password' => $hash, ':id' => $id]);
    }

    public function addAction($userId, $action, $details = ''){
        if (!$this->dbInstance->isPdo()) {
            error_log("User::addAction requires a PDO connection.");
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $this->db->prepare("INSERT INTO user_actions (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip)");
        return $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => $details,
            ':ip' => $ip
        ]);
    }

    public function getHistory($userId, $limit = 50){
        if (!$this->dbInstance->isPdo()) {
            error_log("User::getHistory requires a PDO connection.");
            return [];
        }

        $stmt = $this->db->prepare("SELECT action, details, ip_address as ip, created_at FROM user_actions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(){
        if (!$this->dbInstance->isPdo()) {
            if (file_exists($this->phpDataPath)) {
                $users = include $this->phpDataPath;
                return is_array($users) ? $users : [];
            }
            return [];
        }

        $stmt = $this->db->prepare("SELECT * FROM users");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id){
        if (!$this->dbInstance->isPdo()) {
            error_log("User::delete requires a PDO connection.");
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
